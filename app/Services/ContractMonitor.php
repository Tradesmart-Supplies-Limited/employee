<?php

namespace App\Services;

use App\Mail\ContractEndingReminder;
use App\Models\ContractReminderLog;
use App\Models\ContractReminderSetting;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Watches contract and probation end dates for ACTIVE employees.
 *
 * How reminders work (fixes the old "exact day only" behaviour):
 *   - Thresholds, e.g. [30, 14, 7, 1] days before the end date.
 *   - Each run, an employee is due when days_left <= a threshold that hasn't
 *     been sent yet for that end date. If the job didn't run for a few days,
 *     the next run catches up and sends the tightest threshold only.
 *   - An end date that has passed while the employee is still Active is an
 *     "expired" alert (threshold 0), sent once.
 *   - Renewing a contract (new end date) starts the cycle again.
 *   - Runs at most once per day automatically (scheduler OR first admin
 *     dashboard visit), and can be run on demand from the settings page.
 */
class ContractMonitor
{
    public const DEFAULT_THRESHOLDS = [30, 14, 7, 1];

    public function setting(): ContractReminderSetting
    {
        return ContractReminderSetting::first() ?? ContractReminderSetting::create([
            'name'                   => 'Default',
            'recipients'             => config('company.hr_emails', []),
            'use_days_before'        => true,
            'days_before'            => self::DEFAULT_THRESHOLDS,
            'use_monthly_fixed'      => false,
            'monthly_fixed_days'     => [1],
            'monthly_lookahead_days' => 60,
            'track_contracts'        => true,
            'track_probation'        => true,
            'include_expired'        => true,
            'notify_supervisor'      => false,
            'is_active'              => true,
        ]);
    }

    /**
     * Every tracked end date in a window, soonest first.
     * Each item: employee, type (contract|probation), end (Carbon), days_left (int),
     * state (expired | critical (≤7) | soon (≤30) | upcoming).
     */
    public function items(?Carbon $today = null, int $aheadDays = 90, int $expiredDays = 60, ?ContractReminderSetting $s = null): Collection
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $s ??= $this->setting();

        $employees = Employee::with(['supervisorEmployee.user', 'supervisorUser'])
            ->whereRaw('LOWER(employment_status) = ?', ['active'])
            ->where(fn ($q) => $q->whereNotNull('contract_end')->orWhereNotNull('probation_end'))
            ->get();

        $items = collect();

        foreach ($employees as $e) {
            foreach (['contract' => 'contract_end', 'probation' => 'probation_end'] as $type => $col) {
                if (($type === 'contract' && $s->track_contracts === false) || ($type === 'probation' && $s->track_probation === false)) {
                    continue;
                }
                if (! $e->$col) {
                    continue;
                }

                $end = Carbon::parse($e->$col)->startOfDay();
                $days = (int) $today->diffInDays($end, false);   // negative = already passed

                if ($days > $aheadDays || $days < -$expiredDays) {
                    continue;
                }

                $items->push((object) [
                    'employee'  => $e,
                    'type'      => $type,
                    'end'       => $end,
                    'days_left' => $days,
                    'state'     => match (true) {
                        $days < 0   => 'expired',
                        $days <= 7  => 'critical',
                        $days <= 30 => 'soon',
                        default     => 'upcoming',
                    },
                ]);
            }
        }

        return $items->sortBy('days_left')->values();
    }

    /** Status of one employee, for badges on lists / profiles. */
    public function statusFor(Employee $e, ?Carbon $today = null): array
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $out = [];

        // Exited / suspended staff: no alarms about their old dates.
        if (strtolower((string) $e->employment_status) !== 'active') {
            return [];
        }

        foreach (['contract' => 'contract_end', 'probation' => 'probation_end'] as $type => $col) {
            if (! $e->$col) {
                continue;
            }
            $days = (int) $today->diffInDays(Carbon::parse($e->$col)->startOfDay(), false);
            if ($type === 'probation' && $days < -30) {
                continue; // probation long finished, not interesting
            }
            $out[$type] = [
                'days'  => $days,
                'end'   => Carbon::parse($e->$col),
                'state' => $days < 0 ? 'expired' : ($days <= 7 ? 'critical' : ($days <= 30 ? 'soon' : 'ok')),
            ];
        }

        return $out;
    }

    /** Run once per day at most (used by the dashboard and the scheduler). */
    public function runIfDue(): ?array
    {
        $s = $this->setting();

        if (! $s->is_active || ($s->last_run_at && $s->last_run_at->isToday())) {
            return null;
        }

        // Guard against two admins loading the dashboard at the same moment.
        if (! Cache::add('contract-monitor-running', true, 120)) {
            return null;
        }

        try {
            return $this->run();
        } finally {
            Cache::forget('contract-monitor-running');
        }
    }

    /**
     * Check every threshold and send what's due. Safe to call repeatedly:
     * the log prevents duplicates.
     *
     * @return array{alerts:int, digest:int, emails:int, errors:array}
     */
    public function run(?Carbon $today = null): array
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $s = $this->setting();
        $result = ['alerts' => 0, 'digest' => 0, 'emails' => 0, 'errors' => []];

        $recipients = $this->recipients($s);
        if (empty($recipients)) {
            $result['errors'][] = 'No recipients configured.';
        }

        $thresholds = collect($s->days_before ?: self::DEFAULT_THRESHOLDS)->map(fn ($d) => (int) $d)->filter(fn ($d) => $d > 0)->unique()->sort()->values();
        $maxAhead = (int) max($thresholds->max() ?? 0, $s->monthly_lookahead_days ?? 60);

        $items = $this->items($today, max($maxAhead, 1), 60, $s);

        // ---------- 1. Threshold alerts (with catch-up) ----------
        $due = collect();
        if ($s->use_days_before && $thresholds->isNotEmpty()) {
            foreach ($items as $it) {
                if ($it->days_left < 0) {
                    if (! $s->include_expired) {
                        continue;
                    }
                    $t = 0;
                } else {
                    $t = $thresholds->first(fn ($x) => $it->days_left <= $x);  // tightest threshold reached
                    if ($t === null) {
                        continue;
                    }
                }

                $alreadySent = ContractReminderLog::where('employee_id', $it->employee->id)
                    ->where('event', $it->type)
                    ->whereDate('end_date', $it->end->toDateString())
                    ->where('threshold', '<=', $t)
                    ->exists();

                if (! $alreadySent) {
                    $it->threshold = $t;
                    $due->push($it);
                }
            }
        }

        if ($due->isNotEmpty() && $recipients) {
            $label = $due->count() === 1
                ? $this->describe($due->first())
                : $due->count() . ' contract / probation dates need attention';

            if ($this->send($recipients, $due, $label, 'alert', $result)) {
                foreach ($due as $it) {
                    $this->log($it->employee->id, $s->id, $it->type, $it->end, $it->threshold, $today);
                }
                $result['alerts'] = $due->count();

                if ($s->notify_supervisor) {
                    $this->notifySupervisors($due, $recipients, $result);
                }
            }
        }

        // ---------- 2. Monthly digest on fixed days ----------
        if ($s->use_monthly_fixed && in_array($today->day, array_map('intval', $s->monthly_fixed_days ?? []), true) && $recipients) {
            $sentToday = ContractReminderLog::where('event', 'digest')->whereDate('run_date', $today->toDateString())->exists();
            $digest = $items->filter(fn ($it) => $it->days_left <= ($s->monthly_lookahead_days ?? 60) && ($it->days_left >= 0 || $s->include_expired));

            if (! $sentToday && $digest->isNotEmpty()) {
                if ($this->send($recipients, $digest, 'Monthly digest — next ' . ($s->monthly_lookahead_days ?? 60) . ' days', 'digest', $result)) {
                    $this->log($digest->first()->employee->id, $s->id, 'digest', null, null, $today, 'monthly_fixed');
                    $result['digest'] = $digest->count();
                }
            }
        }

        $s->update(['last_run_at' => now()]);

        return $result;
    }

    public function describe(object $it): string
    {
        $what = $it->type === 'probation' ? 'probation' : 'contract';
        $name = $it->employee->full_name;

        return match (true) {
            $it->days_left < 0   => "{$name}'s {$what} ended " . abs($it->days_left) . ' day(s) ago',
            $it->days_left === 0 => "{$name}'s {$what} ends today",
            default              => "{$name}'s {$what} ends in {$it->days_left} day(s)",
        };
    }

    /** Rows passed to the email (plain arrays, safe to queue). */
    public function rows(Collection $items): array
    {
        return $items->map(fn ($it) => [
            'name'       => $it->employee->full_name,
            'ein'        => $it->employee->employee_id,
            'position'   => $it->employee->position,
            'department' => $it->employee->department,
            'supervisor' => $it->employee->supervisor_name,
            'type'       => $it->type,
            'end'        => $it->end->toDateString(),
            'days_left'  => $it->days_left,
            'state'      => $it->state,
            'url'        => route('employees.show', $it->employee),
        ])->values()->all();
    }

    private function recipients(ContractReminderSetting $s): array
    {
        return collect($s->recipients ?? [])->map(fn ($e) => strtolower(trim($e)))
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))->unique()->values()->all();
    }

    private function send(array $to, Collection $items, string $label, string $kind, array &$result): bool
    {
        try {
            Mail::to($to)->send(new ContractEndingReminder($this->rows($items), $label, $kind));
            $result['emails']++;

            return true;
        } catch (\Throwable $e) {
            Log::error('ContractMonitor: email failed', ['error' => $e->getMessage()]);
            $result['errors'][] = $e->getMessage();

            return false;
        }
    }

    private function notifySupervisors(Collection $due, array $already, array &$result): void
    {
        $due->groupBy(fn ($it) => strtolower((string) ($it->employee->supervisorUser?->email ?? $it->employee->supervisorEmployee?->contact_email)))
            ->each(function ($group, $email) use ($already, &$result) {
                if ($email && filter_var($email, FILTER_VALIDATE_EMAIL) && ! in_array($email, $already, true)) {
                    $this->send([$email], $group, 'Your team: ' . $group->count() . ' contract / probation date(s) coming up', 'supervisor', $result);
                }
            });
    }

    private function log(int $employeeId, int $settingId, string $event, ?Carbon $end, ?int $threshold, Carbon $today, ?string $trigger = null): void
    {
        ContractReminderLog::create([
            'employee_id'                  => $employeeId,
            'contract_reminder_setting_id' => $settingId,
            'trigger_type'                 => $trigger ?? "{$event}_{$threshold}_" . $end?->format('Ymd'),
            'event'                        => $event,
            'end_date'                     => $end?->toDateString(),
            'threshold'                    => $threshold,
            'run_date'                     => $today->toDateString(),
            'sent_at'                      => now(),
        ]);
    }
}
