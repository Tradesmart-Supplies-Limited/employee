<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * HR home: greeting, today's numbers, quick actions, what needs attention,
     * attendance, leave, upcoming dates, workforce insights and recent activity.
     */
    public function index(Request $request)
    {
        $tz = \App\Services\AttendanceService::tz();
        $now = now($tz);
        $today = $now->copy()->startOfDay();
        $user = $request->user() ?? auth()->user();

        // Background jobs that also run from here (work without the Windows scheduler).
        $monitor = app(\App\Services\ContractMonitor::class);
        foreach ([fn () => $monitor->runIfDue(), fn () => app(\App\Services\AttendanceService::class)->runIfDue(), fn () => app(\App\Services\Announcements::class)->emailDue()] as $job) {
            try {
                $job();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Dashboard background job failed: ' . $e->getMessage());
            }
        }

        // Managers get a dashboard of their own department.
        if ($user && ! $user->hasFullAccess()) {
            return $this->managerDashboard($user, $now, $today);
        }

        /* ---------------- people ---------------- */
        $employees = Employee::all();
        $active = $employees->filter(fn ($e) => strtolower((string) $e->employment_status) === 'active');
        $newThisMonth = $active->filter(fn ($e) => $e->contract_start && Carbon::parse($e->contract_start)->isSameMonth($today))->count();
        $exitedThisYear = $employees->filter(fn ($e) => strtolower((string) $e->employment_status) === 'exited' && $e->updated_at && $e->updated_at->year === $today->year)->count();

        /* ---------------- leave ---------------- */
        $leavePipeline = [
            'awaiting_supervisor' => LeaveRequest::stage(LeaveRequest::STAGE_SUPERVISOR)->count(),
            'awaiting_hr'         => LeaveRequest::stage(LeaveRequest::STAGE_HR)->count(),
            'approved_month'      => LeaveRequest::where('status', 'Approved')->whereMonth('hr_acted_at', $today->month)->whereYear('hr_acted_at', $today->year)->count(),
            'rejected_month'      => LeaveRequest::where('status', 'Rejected')->whereMonth('hr_acted_at', $today->month)->whereYear('hr_acted_at', $today->year)->count(),
            'assigned_to_me'      => LeaveRequest::stage(LeaveRequest::STAGE_SUPERVISOR)->where('supervisor_user_id', $user?->id)->count(),
        ];
        $leaveInProgress = LeaveRequest::with(['supervisorEmployee', 'supervisorUser', 'employee'])
            ->where('status', 'Pending')->orderByRaw('supervisor_decision IS NULL')->oldest()->limit(5)->get();
        $onLeaveNow = LeaveRequest::with('employee')->where('status', 'Approved')
            ->whereDate('date_from', '<=', $today->toDateString())->whereDate('date_to', '>=', $today->toDateString())
            ->orderBy('date_to')->get();

        /* ---------------- attendance ---------------- */
        $attendance = null;
        $lateToday = collect();
        try {
            $day = app(\App\Services\AttendanceService::class)->day($today);
            $attendance = $day['totals'] + ['holiday' => $day['holiday'], 'working' => $day['working']];
            $lateToday = $day['rows']->where('status', 'late')->sortByDesc(fn ($r) => $r->record?->minutes_late)->take(4)->values();
        } catch (\Throwable $e) {
            report($e);
        }
        $kiosks = \App\Models\AttendanceKiosk::where('is_active', true)->get();

        /* ---------------- contracts ---------------- */
        $endDates = $monitor->items(null, 30, 60);

        /* ---------------- approvals + attention list ---------------- */
        $pendingProfile = \App\Models\ProfileChangeRequest::where('status', 'pending')->count();
        $pendingOvertime = class_exists(\App\Models\OvertimeRequest::class) ? \App\Models\OvertimeRequest::where('status', 'Pending')->count() : 0;
        $portalIds = \App\Models\User::whereNotNull('employee_id')->pluck('employee_id')->flip();
        $noPortal = $active->reject(fn ($e) => isset($portalIds[$e->id]))->count();
        $incomplete = $active->filter(fn ($e) => ! $e->nrc_no || ! $e->primary_phone || ! ($e->date_of_birth ?: $e->dob) || ! $e->department)->count();

        $latestRun = PayrollRun::latest()->first();

        $attention = collect();
        $add = function (int $n, string $sev, string $icon, string $msg, ?string $url, string $cta) use ($attention) {
            if ($n > 0) {
                $attention->push(compact('n', 'sev', 'icon', 'msg', 'url', 'cta'));
            }
        };
        $expired = $endDates->where('type', 'contract')->where('days_left', '<', 0)->count();
        $ending = $endDates->where('type', 'contract')->where('days_left', '>=', 0)->count();
        $probation = $endDates->where('type', 'probation')->count();
        $offline = $kiosks->reject->is_online->count();
        $workHours = $now->hour >= 6 && $now->hour < 18 && ($attendance['working'] ?? false);

        $add($leavePipeline['awaiting_hr'], 'warning', 'bi-calendar-check', 'leave request(s) need your final decision', route('leave.index'), 'Decide');
        $add($leavePipeline['assigned_to_me'], 'primary', 'bi-person-check', 'leave request(s) from your direct reports', route('leave.index'), 'Review');
        $add($pendingProfile, 'primary', 'bi-person-lines-fill', 'employee detail change(s) waiting for approval', route('profile-changes.index'), 'Review');
        $add($pendingOvertime, 'primary', 'bi-alarm', 'overtime request(s) to approve', \Illuminate\Support\Facades\Route::has('overtime.index') ? route('overtime.index') : null, 'Open');
        $add($expired, 'danger', 'bi-file-earmark-x', 'contract(s) ended but the employee is still Active', route('settings.contract-reminders.index'), 'Resolve');
        $add($ending, 'danger', 'bi-hourglass-split', 'contract(s) end within 30 days', route('settings.contract-reminders.index'), 'Plan');
        $add($probation, 'warning', 'bi-person-badge', 'probation period(s) end within 30 days', route('settings.contract-reminders.index'), 'Confirm');
        $add($workHours ? $offline : 0, 'warning', 'bi-tablet', 'attendance kiosk(s) offline', route('attendance.qr'), 'Check');
        $add(($attendance['missing_out'] ?? 0), 'secondary', 'bi-box-arrow-right', 'people did not clock out yesterday', route('attendance.register', ['date' => $today->copy()->subDay()->toDateString()]), 'Fix');
        $add($latestRun && $latestRun->status === 'Draft' ? 1 : 0, 'warning', 'bi-cash-stack', 'payroll run ' . ($latestRun->alias ?? '') . ' is still a draft', \Illuminate\Support\Facades\Route::has('payroll.runs.show') && $latestRun ? route('payroll.runs.show', $latestRun) : null, 'Open');
        $add($noPortal, 'secondary', 'bi-key', 'active employee(s) have no portal login', route('employees.index', ['attention' => 'no_portal']), 'Set up');
        $add($incomplete, 'secondary', 'bi-clipboard-x', 'employee record(s) missing NRC, phone, birth date or department', route('employees.index', ['attention' => 'incomplete']), 'Complete');

        /* ---------------- upcoming (next 30 days) ---------------- */
        $upcoming = collect();
        foreach ($active as $e) {
            if ($dob = $e->date_of_birth ?: $e->dob) {
                $b = Carbon::parse($dob)->setYear($today->year);
                if ($b->lt($today)) {
                    $b->addYear();
                }
                if ($today->diffInDays($b, false) <= 14) {
                    $upcoming->push(['date' => $b, 'icon' => 'bi-cake2', 'c' => '#db2777', 'title' => $e->full_name, 'sub' => 'Birthday' . ($b->isSameDay($today) ? ' today 🎉' : ''), 'url' => route('employees.show', $e)]);
                }
            }
            if ($e->contract_start) {
                $start = Carbon::parse($e->contract_start);
                $an = $start->copy()->setYear($today->year);
                if ($an->lt($today)) {
                    $an->addYear();
                }
                $yrs = $an->year - $start->year;
                if ($yrs >= 1 && $today->diffInDays($an, false) <= 14) {
                    $upcoming->push(['date' => $an, 'icon' => 'bi-award', 'c' => '#d97706', 'title' => $e->full_name, 'sub' => $yrs . ' year' . ($yrs > 1 ? 's' : '') . ' with the company', 'url' => route('employees.show', $e)]);
                }
            }
        }
        foreach ($endDates->where('days_left', '>=', 0) as $it) {
            $upcoming->push(['date' => $it->end, 'icon' => $it->type === 'probation' ? 'bi-person-badge' : 'bi-file-earmark-text', 'c' => $it->days_left <= 7 ? '#dc2626' : '#ea580c', 'title' => $it->employee->full_name, 'sub' => ucfirst($it->type) . ' ends', 'url' => route('employees.show', $it->employee)]);
        }
        try {
            foreach (app(\App\Services\LeaveDayCalculator::class)->holidays($today->year, $today->copy()->addDays(30)->year) as $d => $name) {
                $hd = Carbon::parse($d, $tz);
                if ($hd->gte($today) && $hd->lte($today->copy()->addDays(30))) {
                    $upcoming->push(['date' => $hd, 'icon' => 'bi-flag', 'c' => '#16a34a', 'title' => $name, 'sub' => 'Public holiday', 'url' => route('leave.manage.holidays')]);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
        LeaveRequest::with('employee')->where('status', 'Approved')
            ->whereDate('date_from', '>', $today->toDateString())->whereDate('date_from', '<=', $today->copy()->addDays(7)->toDateString())
            ->get()->each(fn ($l) => $upcoming->push(['date' => Carbon::parse($l->date_from), 'icon' => 'bi-airplane', 'c' => '#7c3aed', 'title' => $l->employee_name, 'sub' => $l->leave_type . ' leave starts (' . $l->total_days . 'd)', 'url' => route('leave.show', $l)]));
        $upcoming = $upcoming->sortBy(fn ($u) => $u['date']->timestamp)->take(10)->values();

        /* ---------------- workforce ---------------- */
        $byDept = $active->groupBy(fn ($e) => $e->department ?: 'No department')->map->count()->sortDesc();
        $workforce = [
            'male'    => $active->where('gender', 'Male')->count(),
            'female'  => $active->where('gender', 'Female')->count(),
            'natures' => $active->map(fn ($e) => \App\Support\EmployeeFields::NATURES[strtoupper((string) $e->employment_nature)] ?? 'Not set')->countBy()->sortDesc(),
            'avg_age' => ($ages = $active->map(fn ($e) => ($d = $e->date_of_birth ?: $e->dob) ? Carbon::parse($d)->age : null)->filter())->isNotEmpty() ? round($ages->avg()) : null,
            'branches' => $active->pluck('branch')->filter()->unique()->count(),
        ];

        $payrollSummary = null;
        if ($latestRun) {
            $latestRun->loadMissing('payrolls');
            $payrollSummary = [
                'run'      => $latestRun,
                'period'   => $latestRun->alias ?? $latestRun->period,
                'status'   => $latestRun->status,
                'count'    => $latestRun->payrolls->count(),
                'net_pay'  => $latestRun->net_pay ?? $latestRun->payrolls->sum('net_pay'),
                'earnings' => $latestRun->total_income ?? $latestRun->payrolls->sum('total_income'),
            ];
        }

        /* ---------------- activity feed ---------------- */
        $activity = collect()
            ->merge(Employee::latest()->limit(4)->get()->map(fn ($e) => ['at' => $e->created_at, 'icon' => 'bi-person-plus', 'c' => '#16a34a', 'text' => $e->full_name . ' added to employees', 'url' => route('employees.show', $e)]))
            ->merge(LeaveRequest::latest()->limit(4)->get()->map(fn ($l) => ['at' => $l->created_at, 'icon' => 'bi-calendar-plus', 'c' => '#7c3aed', 'text' => $l->employee_name . ' applied for ' . $l->leave_type . ' leave (' . $l->total_days . 'd)', 'url' => route('leave.show', $l)]))
            ->merge(\App\Models\ProfileChangeRequest::with('employee')->latest()->limit(3)->get()->map(fn ($r) => ['at' => $r->created_at, 'icon' => 'bi-person-lines-fill', 'c' => '#0891b2', 'text' => ($r->employee?->full_name ?? 'An employee') . ' requested a detail change (' . $r->status . ')', 'url' => route('profile-changes.index')]))
            ->merge(\App\Models\Announcement::latest()->limit(3)->get()->map(fn ($a) => ['at' => $a->created_at, 'icon' => 'bi-megaphone', 'c' => '#185FA5', 'text' => 'Announcement: ' . $a->title, 'url' => route('announcements.show', $a)]))
            ->filter(fn ($x) => $x['at'])->sortByDesc(fn ($x) => $x['at']->timestamp)->take(8)->values();

        return view('dashboard.index', [
            'now'          => $now,
            'greeting'     => $now->hour < 12 ? 'Good morning' : ($now->hour < 17 ? 'Good afternoon' : 'Good evening'),
            'quote'        => \App\Support\ZambianWisdom::today(),
            'quotes'       => \App\Support\ZambianWisdom::QUOTES,
            'user'         => $user,
            'stats'        => [
                'active'      => $active->count(),
                'total'       => $employees->count(),
                'new_month'   => $newThisMonth,
                'exited_year' => $exitedThisYear,
                'departments' => $active->pluck('department')->filter()->unique()->count(),
                'on_leave'    => $onLeaveNow->count(),
                'approvals'   => $leavePipeline['awaiting_hr'] + $pendingProfile + $pendingOvertime,
                'contracts'   => $ending + $expired,
            ],
            'attendance'      => $attendance,
            'lateToday'       => $lateToday,
            'kiosks'          => $kiosks,
            'leavePipeline'   => $leavePipeline,
            'leaveInProgress' => $leaveInProgress,
            'onLeaveNow'      => $onLeaveNow,
            'attention'       => $attention,
            'counts'          => ['profile' => $pendingProfile, 'overtime' => $pendingOvertime, 'leave_hr' => $leavePipeline['awaiting_hr']],
            'upcoming'        => $upcoming,
            'byDept'          => $byDept,
            'workforce'       => $workforce,
            'payrollSummary'  => $payrollSummary,
            'activity'        => $activity,
            'announcements'   => \App\Models\Announcement::live()->withCount('reads')->orderByDesc('pinned')->latest()->limit(3)->get(),
        ]);
    }

    /**
     * Departments
     */
    /**
     * Leave
     */
    public function leave()
    {
        return view('dashboard.leave.index');
    }

    /**
     * Payroll
     */
    public function payroll()
    {
        return view('dashboard.payroll.runs.index');
    }

    /**
     * Reports
     */
    public function reports()
    {
        return view('dashboard.reports.index');
    }

    /**
     * Settings
     */
    public function settings()
    {
        return view('dashboard.settings.index');
    }

    /** Manager home: their department's attendance today, leave to review, who's away, the rota. */
    private function managerDashboard(\App\Models\User $user, Carbon $now, Carbon $today)
    {
        $depts = $user->managedDepartments() ?? [];
        $att = app(\App\Services\AttendanceService::class);

        $rows = collect();
        foreach ($depts as $d) {
            try {
                $rows = $rows->merge($att->day($today, $d)['rows']);
            } catch (\Throwable $e) {
                report($e);
            }
        }
        $totals = $att->totals($rows);
        $attention = $rows->filter(fn ($r) => in_array($r->status, ['late', 'absent', 'not_in'], true))
            ->sortBy(fn ($r) => ['absent' => 0, 'late' => 1, 'not_in' => 2][$r->status])->values();

        $leave = fn () => \App\Support\DeptScope::viaEmployee(LeaveRequest::query(), $user);
        $toReview = $leave()->with('employee')->where('status', 'Pending')->whereNull('supervisor_decision')->oldest()->limit(8)->get();
        $withHr = $leave()->where('status', 'Pending')->whereNotNull('supervisor_decision')->count();
        $away = $leave()->with('employee')->where('status', 'Approved')
            ->whereDate('date_from', '<=', $today->toDateString())->whereDate('date_to', '>=', $today->toDateString())->orderBy('date_to')->get();
        $upcoming = $leave()->with('employee')->whereIn('status', ['Approved', 'Pending'])
            ->whereDate('date_from', '>', $today->toDateString())->whereDate('date_from', '<=', $today->copy()->addDays(14)->toDateString())
            ->orderBy('date_from')->get();

        $rotas = collect();
        if ($user->hasPermission('rota.manage')) {
            $rs = app(\App\Services\RotaService::class);
            $monday = \App\Services\RotaService::monday($today);
            foreach ($rs->managedDepartments($user) as $d) {
                foreach ([0, 1] as $w) {
                    $wk = $monday->copy()->addWeeks($w);
                    $r = $rs->find($d, $wk);
                    $rotas->push((object) ['department' => $d, 'week' => $wk, 'rota' => $r, 'pending' => $r ? $rs->hasPendingChanges($r) : false]);
                }
            }
        }

        $team = \App\Support\DeptScope::employees(Employee::query(), $user)->tracked()
            ->whereRaw('LOWER(employment_status) = ?', ['active'])->count();

        return view('dashboard.manager', [
            'user'      => $user,
            'depts'     => $depts,
            'team'      => $team,
            'today'     => $today,
            'greeting'  => $now->hour < 12 ? 'Good morning' : ($now->hour < 17 ? 'Good afternoon' : 'Good evening'),
            'quote'     => \App\Support\ZambianWisdom::today(),
            'totals'    => $totals,
            'attention' => $attention,
            'toReview'  => $toReview,
            'withHr'    => $withHr,
            'away'      => $away,
            'upcoming'  => $upcoming,
            'rotas'     => $rotas,
        ]);
    }
}
