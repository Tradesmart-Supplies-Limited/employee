<?php

namespace App\Services;

use App\Models\AttendanceSetting;
use App\Models\AttendanceShift;
use App\Models\Employee;
use App\Models\Rota;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Who should work when, and on which shift.
 *
 *  1. Rota departments (e.g. Operations): the PUBLISHED weekly rota decides.
 *     Day/Night = expected at those times · Backup = on call · Off = day off.
 *  2. Everyone else: the default shift on working days; Sundays and public
 *     holidays are off.
 *  Work on a Sunday or public holiday counts as overtime.
 */
class ShiftSchedule
{
    private ?Collection $shifts = null;
    private array $rotaCache = [];
    /** "Department|Y-m-d (Monday)" => published Rota */
    private array $publishedWeeks = [];

    public function __construct(private AttendanceSetting $settings) {}

    public function shifts(): Collection
    {
        return $this->shifts ??= AttendanceShift::ordered()->keyBy('id');
    }

    public function defaultShift(): ?AttendanceShift
    {
        return $this->shifts()->firstWhere('is_default', true) ?? $this->shifts()->first();
    }

    public static function dayType(Carbon $date, array $holidays): string
    {
        return isset($holidays[$date->toDateString()]) ? 'holiday' : ($date->isSunday() ? 'sunday' : ($date->isSaturday() ? 'saturday' : 'weekday'));
    }

    /**
     * Published rota assignments for a date range, rota departments only.
     * @return array<int, array<string, RotaAssignment>> [employee_id][Y-m-d]
     */
    public function rotaMap(Carbon $from, Carbon $to): array
    {
        $key = $from->toDateString() . '|' . $to->toDateString();
        if (isset($this->rotaCache[$key])) {
            return $this->rotaCache[$key];
        }
        $map = [];
        if ($this->settings->rota_enabled && ! empty($this->settings->rota_departments)) {
            // What was PUBLISHED counts (edits only apply once re-published).
            Rota::where('status', 'published')->whereIn('department', $this->settings->rota_departments)
                ->whereDate('week_start', '>=', $from->copy()->subDays(6)->toDateString())
                ->whereDate('week_start', '<=', $to->toDateString())
                ->get()
                ->each(function (Rota $rota) use (&$map, $from, $to) {
                    $this->publishedWeeks[$rota->department . '|' . $rota->week_start->toDateString()] = $rota;
                    foreach ($rota->published_snapshot ?? [] as $empId => $days) {
                        foreach ($days as $d => $code) {
                            if ($d < $from->toDateString() || $d > $to->toDateString() || $code === '') {
                                continue;
                            }
                            $map[$empId][$d] = (object) [
                                'kind'     => str_starts_with($code, 's:') ? 'shift' : $code,
                                'shift_id' => str_starts_with($code, 's:') ? (int) substr($code, 2) : null,
                                'rota'     => $rota,
                            ];
                        }
                    }
                });
        }

        return $this->rotaCache[$key] = $map;
    }

    /**
     * The plan for one person on one day.
     *
     * @return object{source:string, kind:?string, shift:?AttendanceShift, start:?string, end:?string,
     *                expected:bool, overtime:bool, day_type:string, label:string, rota:bool}
     */
    public function planFor(Employee $e, Carbon $date, array $holidays, ?array $rotaMap = null): object
    {
        $d = $date->toDateString();
        $dayType = self::dayType($date, $holidays);
        $offDay = in_array($dayType, ['sunday', 'holiday'], true);
        $usesRota = $this->settings->usesRota($e->department);
        if ($usesRota) {
            $own = $this->rotaMap($date, $date);      // also records which weeks are published
            $rotaMap ??= $own;
        }
        $rotaMap ??= [];
        $a = $usesRota ? ($rotaMap[$e->id][$d] ?? null) : null;

        if ($a) {
            if ($a->kind === 'shift' && ($shift = $this->shifts()[$a->shift_id] ?? null)) {
                [$s, $en] = $shift->timesOrWeekday($dayType);

                return (object) ['source' => 'rota', 'kind' => 'shift', 'shift' => $shift, 'start' => $s, 'end' => $en, 'expected' => true,
                    'overtime' => $offDay, 'day_type' => $dayType, 'label' => $shift->name . ($s ? " {$s}–{$en}" : ''), 'rota' => true, 'assignment' => $a];
            }
            if ($a->kind === 'backup') {
                $def = $this->defaultShift();
                [$s, $en] = $def ? $def->timesOrWeekday($dayType) : [null, null];

                return (object) ['source' => 'rota', 'kind' => 'backup', 'shift' => $def, 'start' => $s, 'end' => $en, 'expected' => false,
                    'overtime' => $offDay, 'day_type' => $dayType, 'label' => 'Backup (on call)', 'rota' => true, 'assignment' => $a];
            }

            return (object) ['source' => 'rota', 'kind' => 'off', 'shift' => null, 'start' => null, 'end' => null, 'expected' => false,
                'overtime' => $offDay, 'day_type' => $dayType, 'label' => 'Day off (rota)', 'rota' => true, 'assignment' => $a];
        }

        // A published rota is the plan for its week: not on it = not expected.
        if ($usesRota && isset($this->publishedWeeks[$e->department . '|' . self::weekStart($date)->toDateString()])) {
            return (object) ['source' => 'rota', 'kind' => 'off', 'shift' => null, 'start' => null, 'end' => null, 'expected' => false,
                'overtime' => $offDay, 'day_type' => $dayType, 'label' => 'Not on the rota', 'rota' => true, 'assignment' => null];
        }

        $def = $this->defaultShift();
        $working = $this->settings->isWorkingDay($date) && ! ($dayType === 'holiday' && $this->settings->count_holidays_off);
        if (! $working) {
            // Not expected. If they do come in (designated to work), it's overtime.
            return (object) ['source' => 'default', 'kind' => null, 'shift' => null, 'start' => null, 'end' => null, 'expected' => false,
                'overtime' => true, 'day_type' => $dayType, 'label' => $dayType === 'holiday' ? 'Public holiday' : 'Day off', 'rota' => false, 'assignment' => null];
        }
        [$s, $en] = $def ? $def->timesOrWeekday($dayType === 'holiday' ? 'weekday' : $dayType) : [$this->settings->work_start, $this->settings->work_end];

        return (object) ['source' => 'default', 'kind' => 'shift', 'shift' => $def, 'start' => $s, 'end' => $en, 'expected' => true,
            'overtime' => false, 'day_type' => $dayType, 'label' => ($def?->name ?? 'Work') . ($s ? " {$s}–{$en}" : ''), 'rota' => false, 'assignment' => null];
    }

    /** Monday of the week containing $date. */
    public static function weekStart(Carbon $date): Carbon
    {
        return $date->copy()->startOfDay()->startOfWeek(Carbon::MONDAY);
    }

    public function publishedRota(string $department, Carbon $monday): ?Rota
    {
        return Rota::where('department', $department)->whereDate('week_start', $monday->toDateString())->where('status', 'published')->first();
    }
}
