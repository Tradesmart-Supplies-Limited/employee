<?php

namespace App\Console\Commands;

use App\Mail\ContractEndingReminder;
use App\Models\ContractReminderLog;
use App\Models\ContractReminderSetting;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class SendContractReminders extends Command
{
    /**
     * php artisan reminders:contract
     */
    protected $signature = 'reminders:contract';

    protected $description = 'Check employee contract end dates and email configured recipients based on the active contract reminder settings.';

    public function handle(): int
    {
        $today = Carbon::today();
        $settings = ContractReminderSetting::where('is_active', true)->get();

        if ($settings->isEmpty()) {
            $this->info('No active contract reminder settings found.');
            return self::SUCCESS;
        }

        foreach ($settings as $setting) {
            if (empty($setting->recipients)) {
                $this->warn("Setting #{$setting->id} ({$setting->name}) has no recipients — skipping.");
                continue;
            }

            // ---------------------------------------------------------------
            // Trigger 1: "N day(s) before contract end"
            // ---------------------------------------------------------------
            if ($setting->use_days_before && ! empty($setting->days_before)) {
                foreach ($setting->days_before as $days) {
                    $targetDate = $today->copy()->addDays((int) $days)->toDateString();
                    $triggerType = "days_before_{$days}";

                    $employees = $this->findUnnotified(
                        Employee::whereNotNull('contract_end')
                            ->whereDate('contract_end', $targetDate)
                            ->get(),
                        $triggerType,
                        $today
                    );

                    if ($employees->isNotEmpty()) {
                        $this->dispatchReminder(
                            $setting,
                            $employees,
                            "{$days} day(s) before contract end",
                            $triggerType,
                            $today
                        );
                    }
                }
            }

            // ---------------------------------------------------------------
            // Trigger 2: fixed day(s) of month, e.g. every 1st and 14th
            // ---------------------------------------------------------------
            if ($setting->use_monthly_fixed && ! empty($setting->monthly_fixed_days)) {
                if (in_array($today->day, $setting->monthly_fixed_days)) {
                    $triggerType = 'monthly_fixed';
                    $horizon = $setting->monthly_lookahead_days ?? 60;

                    $employees = $this->findUnnotified(
                        Employee::whereNotNull('contract_end')
                            ->whereDate('contract_end', '>=', $today->toDateString())
                            ->whereDate('contract_end', '<=', $today->copy()->addDays($horizon)->toDateString())
                            ->orderBy('contract_end')
                            ->get(),
                        $triggerType,
                        $today
                    );

                    if ($employees->isNotEmpty()) {
                        $this->dispatchReminder(
                            $setting,
                            $employees,
                            'Monthly contract expiry digest',
                            $triggerType,
                            $today
                        );
                    }
                }
            }

            $setting->update(['last_run_at' => now()]);
        }

        return self::SUCCESS;
    }

    /**
     * Filter out employees already notified for this trigger today.
     */
    protected function findUnnotified(Collection $employees, string $triggerType, Carbon $today): Collection
    {
        if ($employees->isEmpty()) {
            return $employees;
        }

        $alreadyNotified = ContractReminderLog::where('trigger_type', $triggerType)
            ->whereDate('run_date', $today->toDateString())
            ->whereIn('employee_id', $employees->pluck('id'))
            ->pluck('employee_id')
            ->all();

        return $employees->reject(fn ($e) => in_array($e->id, $alreadyNotified))->values();
    }

    protected function dispatchReminder(
        ContractReminderSetting $setting,
        Collection $employees,
        string $label,
        string $triggerType,
        Carbon $today
    ): void {
        try {
            Mail::to($setting->recipients)->send(
                new ContractEndingReminder($employees, $label)
            );

            foreach ($employees as $employee) {
                ContractReminderLog::create([
                    'employee_id' => $employee->id,
                    'contract_reminder_setting_id' => $setting->id,
                    'trigger_type' => $triggerType,
                    'run_date' => $today->toDateString(),
                    'sent_at' => now(),
                ]);
            }

            $this->info(
                "Sent '{$label}' reminder for {$employees->count()} employee(s) to "
                . count($setting->recipients) . ' recipient(s).'
            );
        } catch (\Throwable $e) {
            $this->error('Failed to send contract reminder: ' . $e->getMessage());
        }
    }
}
