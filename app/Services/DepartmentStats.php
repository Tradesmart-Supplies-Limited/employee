<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Departments are the "Department" field on employee records, so these
 * figures always match what's in the system. Nothing to set up separately.
 */
class DepartmentStats
{
    public const NONE = 'No department';

    public static function nameOf(Employee $e): string
    {
        return trim((string) $e->department) !== '' ? trim($e->department) : self::NONE;
    }

    public static function slug(string $name): string
    {
        return Str::slug($name) ?: 'department';
    }

    /** @return Collection<object> one row per department, biggest first */
    public function all(): Collection
    {
        $employees = Employee::with('supervisorEmployee', 'supervisorUser')->get();
        $today = AttendanceService::today();

        $onLeave = LeaveRequest::where('status', 'Approved')->whereNotNull('employee_id')
            ->whereDate('date_from', '<=', $today->toDateString())->whereDate('date_to', '>=', $today->toDateString())
            ->pluck('employee_id')->flip();

        $portal = User::whereNotNull('employee_id')->pluck('employee_id')->flip();

        $att = collect();
        try {
            $att = app(AttendanceService::class)->day($today)['rows']->groupBy(fn ($r) => self::nameOf($r->employee));
        } catch (\Throwable $e) {
            report($e);
        }

        return $employees->groupBy(fn ($e) => self::nameOf($e))
            ->map(fn ($group, $name) => $this->summarise($name, $group, $onLeave, $portal, $att[$name] ?? collect(), $today))
            ->sortByDesc('active')->values();
    }

    public function find(string $slug): ?object
    {
        return $this->all()->firstWhere('slug', $slug);
    }

    private function summarise(string $name, Collection $all, $onLeave, $portal, Collection $attRows, Carbon $today): object
    {
        $active = $all->filter(fn ($e) => strtolower((string) $e->employment_status) === 'active')->values();
        $ages = $active->map(fn ($e) => ($d = $e->date_of_birth ?: $e->dob) ? Carbon::parse($d)->age : null)->filter();
        // Start date = contract start (record creation dates are unreliable after a bulk import).
        $tenure = $active->filter(fn ($e) => $e->contract_start)->map(fn ($e) => Carbon::parse($e->contract_start)->floatDiffInYears($today))->filter(fn ($y) => $y >= 0);

        $manager = $active->map(fn ($e) => $e->supervisor_name)->filter()->countBy()->sortDesc();
        $managerName = $manager->keys()->first();

        $att = app(AttendanceService::class)->totals($attRows);

        $ending = $active->filter(fn ($e) => $e->contract_end && ($d = (int) $today->diffInDays(Carbon::parse($e->contract_end)->startOfDay(), false)) >= 0 && $d <= 30)->count();
        $birthdays = $active->filter(function ($e) use ($today) {
            $d = $e->date_of_birth ?: $e->dob;

            return $d && Carbon::parse($d)->month === $today->month;
        })->values();

        return (object) [
            'name'          => $name,
            'slug'          => self::slug($name),
            'employees'     => $all->sortBy(fn ($e) => strtolower($e->first_name . ' ' . $e->last_name))->values(),
            'active_list'   => $active,
            'total'         => $all->count(),
            'active'        => $active->count(),
            'inactive'      => $all->count() - $active->count(),
            'male'          => $active->where('gender', 'Male')->count(),
            'female'        => $active->where('gender', 'Female')->count(),
            'avg_age'       => $ages->isNotEmpty() ? round($ages->avg()) : null,
            'avg_tenure'    => $tenure->isNotEmpty() ? round($tenure->avg(), 1) : null,
            'positions'     => $active->pluck('position')->filter()->countBy()->sortDesc(),
            'branches'      => $active->pluck('branch')->filter()->unique()->values(),
            'natures'       => $active->map(fn ($e) => strtoupper((string) $e->employment_nature) ?: 'UNSET')->countBy(),
            'manager'       => $managerName,
            'manager_team'  => $managerName ? $manager->first() : 0,
            'on_leave'      => $active->filter(fn ($e) => isset($onLeave[$e->id]))->count(),
            'on_leave_list' => $active->filter(fn ($e) => isset($onLeave[$e->id]))->values(),
            'attendance'    => $att,
            'att_rows'      => $attRows->keyBy(fn ($r) => $r->employee->id),
            'ending_soon'   => $ending,
            'new_30'        => $active->filter(fn ($e) => $e->contract_start && Carbon::parse($e->contract_start)->between($today->copy()->subDays(30), $today->copy()->endOfDay()))->count(),
            'no_portal'     => $active->reject(fn ($e) => isset($portal[$e->id]))->count(),
            'birthdays'     => $birthdays,
        ];
    }

    /** Department names for pickers (real ones only, A–Z). */
    public static function names(): array
    {
        return Employee::whereNotNull('department')->where('department', '!=', '')->distinct()->orderBy('department')->pluck('department')->all();
    }
}
