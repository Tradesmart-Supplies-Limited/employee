<?php

namespace App\Services;

use App\Mail\RotaPublishedMail;
use App\Mail\RotaPublishedHrMail;
use App\Models\AttendanceShift;
use App\Models\Employee;
use App\Models\Rota;
use App\Models\RotaAssignment;
use App\Models\User;
use App\Support\HrRecipients;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Weekly shift rotas (Mon → Sun) for rota departments, e.g. Operations.
 *
 * Cell codes: "s:{shift id}" = on that shift · "backup" = on call · "off" = day off · "" = nothing.
 * Draft → "Approve & publish" by HR or the department's supervisor → employees emailed.
 * Changes after publishing are re-published; only people whose week changed are emailed again.
 */
class RotaService
{
    public function __construct(private AttendanceService $att) {}

    public function enabled(): bool
    {
        return (bool) $this->att->settings()->rota_enabled;
    }

    /** Departments using a rota (empty when switched off). */
    public function departments(): array
    {
        return $this->enabled() ? array_values($this->att->settings()->rota_departments ?? []) : [];
    }

    // ---------------------------------------------------------- permissions

    /** HR/admin staff: every rota department. Supervisors: the departments of the people who report to them. */
    public function managedDepartments(?User $u): array
    {
        if (! $u) {
            return [];
        }
        $all = $this->departments();
        if (method_exists($u, 'isStaff') ? $u->isStaff() : $u->employee_id === null) {
            return $all;
        }
        $me = $u->employee;
        if (! $me) {
            return [];
        }
        $theirs = Employee::where('supervisor_employee_id', $me->id)->whereRaw('LOWER(employment_status) = ?', ['active'])
            ->pluck('department')->filter()->unique()->all();

        return array_values(array_intersect($all, $theirs));
    }

    public function canManage(?User $u, string $department): bool
    {
        return in_array($department, $this->managedDepartments($u), true);
    }

    // ---------------------------------------------------------- loading

    public static function monday(Carbon|string $date): Carbon
    {
        $d = $date instanceof Carbon ? $date->copy() : Carbon::parse($date, AttendanceService::tz());

        return ShiftSchedule::weekStart($d);
    }

    public function find(string $department, Carbon $monday): ?Rota
    {
        return Rota::where('department', $department)->whereDate('week_start', $monday->toDateString())->first();
    }

    /** Open the week's rota, creating it (copied from the week before, or from defaults) if needed. */
    public function openOrCreate(string $department, Carbon $monday, ?User $by, bool $copyPrevious = true): Rota
    {
        if ($r = $this->find($department, $monday)) {
            return $r;
        }

        return DB::transaction(function () use ($department, $monday, $by, $copyPrevious) {
            $rota = Rota::create(['department' => $department, 'week_start' => $monday->toDateString(), 'status' => 'draft', 'created_by' => $by?->id, 'updated_by' => $by?->id]);
            $prev = $copyPrevious ? $this->find($department, $monday->copy()->subWeek()) : null;
            if ($prev && $prev->assignments()->exists()) {
                $this->copyInto($rota, $prev);
            } else {
                $this->fillDefaults($rota);
            }

            return $rota->fresh();
        });
    }

    /** Copy another week's pattern into this rota (same weekday → same weekday). */
    public function copyInto(Rota $rota, Rota $from): int
    {
        $people = $this->people($rota)->pluck('id')->flip();
        $offset = Carbon::parse($from->week_start->toDateString())->diffInDays(Carbon::parse($rota->week_start->toDateString()));
        $rota->assignments()->delete();
        $n = 0;
        foreach ($from->assignments as $a) {
            if (! isset($people[$a->employee_id])) {
                continue; // left the department / no longer active
            }
            $rota->assignments()->create(['employee_id' => $a->employee_id, 'date' => Carbon::parse($a->date->toDateString())->addDays((int) round($offset))->toDateString(), 'kind' => $a->kind, 'shift_id' => $a->shift_id, 'note' => $a->note]);
            $n++;
        }
        $rota->touch();

        return $n;
    }

    /** New week with no history: default shift on working days, nothing on Sunday. */
    public function fillDefaults(Rota $rota): void
    {
        $def = AttendanceShift::default();
        $s = $this->att->settings();
        $holidays = $this->att->holidays($rota->days[0], $rota->days[6]);
        foreach ($this->people($rota) as $e) {
            foreach ($rota->days as $d) {
                if ($def && $s->isWorkingDay($d) && ! $d->isSunday() && ! isset($holidays[$d->toDateString()])) {
                    $rota->assignments()->create(['employee_id' => $e->id, 'date' => $d->toDateString(), 'kind' => 'shift', 'shift_id' => $def->id]);
                }
            }
        }
    }

    /** Active staff of the department (+ anyone already on this rota), A–Z. */
    public function people(Rota $rota): Collection
    {
        $ids = $rota->exists ? $rota->assignments()->pluck('employee_id')->unique() : collect();

        return Employee::query()
            ->where(fn ($q) => $q->where(fn ($w) => $w->where('department', $rota->department)->whereRaw('LOWER(employment_status) = ?', ['active']))
                ->orWhereIn('id', $ids))
            ->orderBy('first_name')->orderBy('last_name')->get();
    }

    /** [employee_id][Y-m-d] => code */
    public function cells(Rota $rota): array
    {
        $out = [];
        foreach ($rota->assignments as $a) {
            $out[$a->employee_id][$a->date->toDateString()] = $a->code;
        }

        return $out;
    }

    // ---------------------------------------------------------- saving

    /** Replace the grid with what the editor sent. */
    public function save(Rota $rota, array $cells, ?string $notes, ?User $by): void
    {
        $valid = AttendanceShift::where('is_active', true)->pluck('id')->map(fn ($id) => 's:' . $id)->merge(['backup', 'off'])->flip();
        $days = collect($rota->days)->map->toDateString()->flip();
        $people = $this->people($rota)->pluck('id')->flip();

        DB::transaction(function () use ($rota, $cells, $notes, $by, $valid, $days, $people) {
            $rota->assignments()->delete();
            foreach ($cells as $empId => $row) {
                if (! isset($people[(int) $empId]) || ! is_array($row)) {
                    continue;
                }
                foreach ($row as $date => $code) {
                    $code = (string) $code;
                    if ($code === '' || ! isset($days[$date]) || ! isset($valid[$code])) {
                        continue;
                    }
                    $rota->assignments()->create([
                        'employee_id' => (int) $empId,
                        'date'        => $date,
                        'kind'        => str_starts_with($code, 's:') ? 'shift' : $code,
                        'shift_id'    => str_starts_with($code, 's:') ? (int) substr($code, 2) : null,
                    ]);
                }
            }
            $rota->update(['notes' => $notes, 'updated_by' => $by?->id]);
            $rota->touch();
        });
    }

    // ---------------------------------------------------------- checks

    /** @return array<int, array{0:string,1:string}> [level (danger|warning|info), message] */
    public function warnings(Rota $rota): array
    {
        $out = [];
        $rota->loadMissing('assignments.employee', 'assignments.shift');
        $from = $rota->days[0];
        $to = $rota->days[6];
        $holidays = $this->att->holidays($from, $to);
        $leaves = $this->att->leaveMap($from, $to);
        $byEmp = $rota->assignments->groupBy('employee_id');

        foreach ($holidays as $d => $name) {
            if ($d >= $from->toDateString() && $d <= $to->toDateString()) {
                $out[] = ['info', Carbon::parse($d)->format('D d M') . " is a public holiday ({$name}). Anyone on a shift that day is paid overtime."];
            }
        }

        foreach ($byEmp as $empId => $list) {
            $e = $list->first()->employee;
            if (! $e) {
                continue;
            }
            foreach ($list as $a) {
                if ($a->kind === 'off') {
                    continue;
                }
                $d = $a->date->toDateString();
                foreach ($leaves[$empId] ?? [] as [$f, $t, $type]) {
                    if ($d >= $f && $d <= $t) {
                        $out[] = ['danger', "{$e->full_name} is on {$type} on " . $a->date->format('D d M') . ' but is rostered (' . ($a->kind === 'backup' ? 'backup' : $a->shift?->name) . ').'];
                    }
                }
            }
            if ($this->att->settings()->comp_off_required) {
                $sunday = $list->first(fn ($a) => $a->kind === 'shift' && $a->date->isSunday());
                $hasOff = $list->contains(fn ($a) => $a->kind === 'off' && ! $a->date->isSunday());
                if ($sunday && ! $hasOff) {
                    $out[] = ['warning', "{$e->full_name} works Sunday but has no day off during the week. Give them a day off to make up for it."];
                }
            }
        }

        foreach ($rota->days as $d) {
            if ($d->isSunday() || isset($holidays[$d->toDateString()])) {
                continue;
            }
            if (! $rota->assignments->contains(fn ($a) => $a->kind === 'shift' && $a->date->isSameDay($d))) {
                $out[] = ['warning', 'Nobody is on a shift on ' . $d->format('l d M') . '.'];
            }
        }

        return $out;
    }

    /** [Y-m-d][code] => count */
    public function counts(Rota $rota): array
    {
        $c = [];
        foreach ($rota->assignments as $a) {
            $d = $a->date->toDateString();
            $c[$d][$a->code] = ($c[$d][$a->code] ?? 0) + 1;
        }

        return $c;
    }

    // ---------------------------------------------------------- publishing

    /**
     * Approve & publish. First time: everyone on the rota is emailed their week.
     * Later: only people whose week changed.
     *
     * @return array{notified:int, changed:int, first:bool}
     */
    public function publish(Rota $rota, User $by): array
    {
        if (! $this->canManage($by, $rota->department)) {
            throw new RuntimeException('You can only publish rotas for your own department.');
        }
        $rota->load('assignments');
        $now = $this->cells($rota);
        $before = $rota->published_snapshot ?? [];
        $first = ! $rota->is_published || ! $rota->published_snapshot;

        $ids = collect(array_keys($now))->merge(array_keys($before))->unique();
        $changed = $ids->filter(fn ($id) => ($now[$id] ?? []) != ($before[$id] ?? []))->values();

        $rota->forceFill([
            'status'             => 'published',
            'version'            => $rota->version + 1,
            'published_snapshot' => $now,
            'published_by'       => $by->id,
            'published_at'       => now(),
        ])->save();
        $this->att->refreshSettings(); // drop any cached rota look-ups

        $notified = $this->notify($rota, $first ? collect(array_keys($now)) : $changed, $first, $by, $before);

        return ['notified' => $notified, 'changed' => $changed->count(), 'first' => $first];
    }

    /** Published, but the grid has been edited since: needs re-publishing to take effect. */
    public function hasPendingChanges(Rota $rota): bool
    {
        return $rota->is_published && $this->cells($rota->loadMissing('assignments')) != ($rota->published_snapshot ?? []);
    }

    /** Back to draft (hidden from attendance and the portal until published again). */
    public function unpublish(Rota $rota, User $by): void
    {
        $rota->update(['status' => 'draft', 'updated_by' => $by->id]);
        $this->att->refreshSettings();
    }

    /** One line per day for an employee: label + kind, used in emails and the portal. */
    public function weekFor(Rota $rota, int $employeeId, ?array $snapshot = null): array
    {
        $cells = $snapshot ?? $this->cells($rota);
        $shifts = AttendanceShift::all()->keyBy('id');
        $holidays = $this->att->holidays($rota->days[0], $rota->days[6]);
        $rows = [];
        foreach ($rota->days as $d) {
            $code = $cells[$employeeId][$d->toDateString()] ?? '';
            $rows[] = ['date' => $d, 'code' => $code] + $this->describe($code, $d, $holidays, $shifts);
        }

        return $rows;
    }

    /** Human label + times for a cell. */
    public function describe(string $code, Carbon $d, array $holidays, $shifts = null): array
    {
        $shifts ??= AttendanceShift::all()->keyBy('id');
        $dayType = ShiftSchedule::dayType($d, $holidays);
        if (str_starts_with($code, 's:') && ($sh = $shifts[(int) substr($code, 2)] ?? null)) {
            [$s, $e] = $sh->timesOrWeekday($dayType);

            return ['label' => $sh->name, 'time' => $s ? "{$s}–{$e}" : '', 'color' => $sh->color, 'kind' => 'shift', 'overtime' => in_array($dayType, ['sunday', 'holiday'], true), 'holiday' => $holidays[$d->toDateString()] ?? null];
        }

        return match ($code) {
            'backup' => ['label' => 'Backup (on call)', 'time' => '', 'color' => '#ca8a04', 'kind' => 'backup', 'overtime' => false, 'holiday' => $holidays[$d->toDateString()] ?? null],
            'off'    => ['label' => 'Day off', 'time' => '', 'color' => '#64748b', 'kind' => 'off', 'overtime' => false, 'holiday' => $holidays[$d->toDateString()] ?? null],
            default  => ['label' => '—', 'time' => '', 'color' => '#cbd5e1', 'kind' => 'none', 'overtime' => false, 'holiday' => $holidays[$d->toDateString()] ?? null],
        };
    }

    private function notify(Rota $rota, Collection $employeeIds, bool $first, User $by, array $before): int
    {
        $n = 0;
        $employees = Employee::whereIn('id', $employeeIds)->get();
        foreach ($employees as $e) {
            if (! $to = $e->contact_email) {
                continue;
            }
            $week = $this->weekFor($rota, $e->id);
            $old = $first ? null : $this->weekFor($rota, $e->id, $before);
            try {
                Mail::to($to)->queue(new RotaPublishedMail($rota, $e, $week, $first, $old));
                $n++;
            } catch (\Throwable $ex) {
                Log::error('Rota email failed: ' . $ex->getMessage());
            }
        }

        // HR keeps a copy of every published rota (the publisher excluded).
        foreach (HrRecipients::all([$by->email]) as $hr) {
            try {
                Mail::to($hr)->queue(new RotaPublishedHrMail($rota, $by->name, $first, $employeeIds->count(), $n));
            } catch (\Throwable $ex) {
                Log::error('Rota HR email failed: ' . $ex->getMessage());
            }
        }

        return $n;
    }

    // ---------------------------------------------------------- employee view

    /** Published rota weeks covering $from (Mon) for an employee's department. */
    public function publishedFor(Employee $e, Carbon $monday): ?Rota
    {
        if (! in_array($e->department, $this->departments(), true)) {
            return null;
        }

        return Rota::where('department', $e->department)->whereDate('week_start', $monday->toDateString())->where('status', 'published')->first();
    }
}
