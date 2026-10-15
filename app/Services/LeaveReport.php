<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds every number on the Leave Management overview (and its CSV exports)
 * for one leave year, with optional department / branch / search filters.
 */
class LeaveReport
{
    public array $filters;
    public Collection $employees;   // rows for the balances table
    public Collection $requests;    // all requests in the year (filtered)
    public array $kpis = [];
    public array $monthly = [];
    public array $byType = [];
    public array $byDepartment = [];
    public Collection $upcoming;
    public Collection $onLeaveToday;
    public Collection $pendingQueue;

    public function __construct(array $filters = [])
    {
        $this->filters = array_merge([
            'year'       => now()->year,
            'department' => null,
            'branch'     => null,
            'q'          => null,
            'include_inactive' => false,
            'flag'       => null,  // low | negative | unused | on_leave | pending
            'sort'       => 'name',
            'dir'        => 'asc',
        ], array_filter($filters, fn ($v) => $v !== null && $v !== ''));

        $this->build();
    }

    private function build(): void
    {
        $f = $this->filters;
        $year = (int) $f['year'];
        $deductible = config('leave.deductible_types', ['Annual']);
        $low = (float) config('leave.low_balance_threshold', 3);
        $today = now()->startOfDay();

        $employees = Employee::with(['supervisorEmployee', 'supervisorUser'])
            ->tracked() // directors aren't counted in employee leave reports
            ->when(! $f['include_inactive'], fn ($q) => $q->where('employment_status', 'Active'))
            ->when($f['department'], fn ($q, $v) => $q->where('department', $v))
            ->when($f['branch'], fn ($q, $v) => $q->where('branch', $v))
            ->when($f['q'], fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('first_name', 'like', "%{$v}%")->orWhere('last_name', 'like', "%{$v}%")
                ->orWhere('employee_id', 'like', "%{$v}%")))
            ->get();

        $ids = $employees->pluck('id');

        $this->requests = LeaveRequest::whereIn('employee_id', $ids)
            ->whereYear('date_from', $year)
            ->get();

        $byEmp = $this->requests->groupBy('employee_id');

        // ---------- Employee rows ----------
        $rows = $employees->map(function (Employee $e) use ($byEmp, $deductible, $low, $today) {
            $r = $byEmp->get($e->id, collect());
            $approved = $r->where('status', 'Approved');
            $pending = $r->where('status', 'Pending');
            $ent = (float) $e->annual_leave_entitlement;
            $taken = (float) $approved->whereIn('leave_type', $deductible)->sum('total_days');
            $bal = (float) $e->leave_balance;

            $current = $approved->first(fn ($l) => $l->date_from->lte($today) && $l->date_to->gte($today));
            $next = $approved->filter(fn ($l) => $l->date_from->gt($today))->sortBy('date_from')->first();

            return (object) [
                'employee'      => $e,
                'name'          => $e->full_name,
                'department'    => $e->department,
                'entitlement'   => $ent,
                'balance'       => $bal,
                'taken'         => $taken,
                'other_taken'   => (float) $approved->whereNotIn('leave_type', $deductible)->sum('total_days'),
                'pending_days'  => (float) $pending->whereIn('leave_type', $deductible)->sum('total_days'),
                'approved'      => $approved->count(),
                'pending'       => $pending->count(),
                'rejected'      => $r->where('status', 'Rejected')->count(),
                'utilization'   => $ent > 0 ? round($taken / $ent * 100) : 0,
                'on_leave'      => $current,
                'next_leave'    => $next,
                'is_low'        => $bal <= $low && $bal >= 0,
                'is_negative'   => $bal < 0,
                'unused'        => $taken == 0.0,
            ];
        });

        $rows = match ($f['flag']) {
            'low'      => $rows->filter(fn ($r) => $r->is_low),
            'negative' => $rows->filter(fn ($r) => $r->is_negative),
            'unused'   => $rows->filter(fn ($r) => $r->unused),
            'on_leave' => $rows->filter(fn ($r) => $r->on_leave),
            'pending'  => $rows->filter(fn ($r) => $r->pending > 0),
            default    => $rows,
        };

        $sortKey = in_array($f['sort'], ['name', 'department', 'entitlement', 'balance', 'taken', 'other_taken', 'pending_days', 'approved', 'pending', 'rejected', 'utilization'], true) ? $f['sort'] : 'name';
        $rows = $rows->sortBy(fn ($r) => is_string($r->$sortKey) ? strtolower((string) $r->$sortKey) : $r->$sortKey, SORT_REGULAR, $f['dir'] === 'desc')->values();

        $this->employees = $rows;

        // ---------- KPIs ----------
        $req = $this->requests;
        $decided = $req->whereIn('status', ['Approved', 'Rejected']);
        $turnaround = $decided->filter(fn ($l) => $l->hr_acted_at)->map(fn ($l) => $l->created_at->diffInHours($l->hr_acted_at));
        $supTurn = $req->filter(fn ($l) => $l->supervisor_acted_at)->map(fn ($l) => $l->created_at->diffInHours($l->supervisor_acted_at));
        $allRows = $employees->count();

        $this->kpis = [
            'headcount'         => $allRows,
            'entitlement'       => (float) $employees->sum('annual_leave_entitlement'),
            'balance_total'     => (float) $employees->sum('leave_balance'),
            'avg_balance'       => $allRows ? round($employees->avg('leave_balance'), 1) : 0,
            'taken'             => (float) $req->where('status', 'Approved')->whereIn('leave_type', $deductible)->sum('total_days'),
            'taken_all'         => (float) $req->where('status', 'Approved')->sum('total_days'),
            'pending_days'      => (float) $req->where('status', 'Pending')->sum('total_days'),
            'requests'          => $req->count(),
            'approved'          => $req->where('status', 'Approved')->count(),
            'pending'           => $req->where('status', 'Pending')->count(),
            'rejected'          => $req->where('status', 'Rejected')->count(),
            'awaiting_supervisor' => $req->filter(fn ($l) => $l->stage === LeaveRequest::STAGE_SUPERVISOR)->count(),
            'awaiting_hr'       => $req->filter(fn ($l) => $l->stage === LeaveRequest::STAGE_HR)->count(),
            'approval_rate'     => $decided->count() ? round($req->where('status', 'Approved')->count() / $decided->count() * 100) : null,
            'avg_turnaround_h'  => $turnaround->count() ? round($turnaround->avg(), 1) : null,
            'avg_supervisor_h'  => $supTurn->count() ? round($supTurn->avg(), 1) : null,
            'low_balance'       => $rows->where('is_low', true)->count(),
            'negative_balance'  => $rows->where('is_negative', true)->count(),
            'unused'            => $rows->where('unused', true)->count(),
            'utilization'       => $employees->sum('annual_leave_entitlement') > 0
                ? round($req->where('status', 'Approved')->whereIn('leave_type', $deductible)->sum('total_days') / $employees->sum('annual_leave_entitlement') * 100)
                : 0,
        ];

        // ---------- Monthly (by first day of leave) ----------
        for ($m = 1; $m <= 12; $m++) {
            $inMonth = $req->filter(fn ($l) => $l->date_from->month === $m);
            $this->monthly[$m] = [
                'label'    => Carbon::create($year, $m, 1)->format('M'),
                'approved' => (float) $inMonth->where('status', 'Approved')->sum('total_days'),
                'pending'  => (float) $inMonth->where('status', 'Pending')->sum('total_days'),
                'rejected' => (float) $inMonth->where('status', 'Rejected')->sum('total_days'),
            ];
        }

        // ---------- By type ----------
        $this->byType = $req->groupBy('leave_type')->map(fn ($g, $type) => [
            'type'       => $type,
            'requests'   => $g->count(),
            'approved'   => (float) $g->where('status', 'Approved')->sum('total_days'),
            'pending'    => (float) $g->where('status', 'Pending')->sum('total_days'),
            'rejected'   => $g->where('status', 'Rejected')->count(),
            'deductible' => in_array($type, $deductible, true),
        ])->sortByDesc('approved')->values()->all();

        // ---------- By department ----------
        $this->byDepartment = $rows->groupBy(fn ($r) => $r->department ?: 'Unassigned')->map(fn ($g, $dept) => [
            'department'  => $dept,
            'headcount'   => $g->count(),
            'taken'       => $g->sum('taken'),
            'entitlement' => $g->sum('entitlement'),
            'avg_balance' => round($g->avg('balance'), 1),
            'pending'     => $g->sum('pending'),
            'utilization' => $g->sum('entitlement') > 0 ? round($g->sum('taken') / $g->sum('entitlement') * 100) : 0,
        ])->sortByDesc('taken')->values()->all();

        // ---------- Who's away ----------
        $base = LeaveRequest::whereIn('employee_id', $ids);
        $this->onLeaveToday = (clone $base)->where('status', 'Approved')
            ->whereDate('date_from', '<=', $today)->whereDate('date_to', '>=', $today)
            ->orderBy('date_to')->get();
        $this->upcoming = (clone $base)->whereIn('status', ['Approved', 'Pending'])
            ->whereDate('date_from', '>', $today)->whereDate('date_from', '<=', $today->copy()->addDays(30))
            ->orderBy('date_from')->get();
        $this->pendingQueue = (clone $base)->where('status', 'Pending')
            ->with(['supervisorEmployee', 'supervisorUser'])
            ->orderByRaw('supervisor_decision IS NULL')->oldest()->get();
    }

    /** Distinct departments / branches for the filter dropdowns. */
    public static function filterOptions(): array
    {
        return [
            'departments' => Employee::whereNotNull('department')->where('department', '!=', '')->distinct()->orderBy('department')->pluck('department'),
            'branches'    => Employee::whereNotNull('branch')->where('branch', '!=', '')->distinct()->orderBy('branch')->pluck('branch'),
            'years'       => collect(range(now()->year + 1, now()->year - 4)),
        ];
    }
}
