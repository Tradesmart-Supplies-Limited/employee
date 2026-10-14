<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /** Payslips become visible to the employee once the run is finalized. */
    public const RELEASED_STATUSES = ['Approved', 'Paid'];

    public function index(Request $request)
    {
        $employee = $request->user()->employee;

        $leaveThisYear = $employee->leaveRequests()
            ->where('status', 'Approved')
            ->whereYear('date_from', now()->year)
            ->sum('total_days');

        $pendingLeave = $employee->leaveRequests()->where('status', 'Pending')->count();

        $pendingOvertime = $employee->overtimeRequests()->where('status', 'Pending')->count();

        $overtimeThisMonth = $employee->overtimeRequests()
            ->where('status', 'Approved')
            ->whereYear('work_date', now()->year)
            ->whereMonth('work_date', now()->month)
            ->sum('hours');

        $latestPayslip = Payroll::with('run')
            ->where('employee_id', $employee->id)
            ->whereIn('status', self::RELEASED_STATUSES)
            ->latest('id')
            ->first();

        $recentLeave    = $employee->leaveRequests()->with(['supervisorEmployee', 'supervisorUser'])->latest()->take(5)->get();

        // Leave balance + the newest request still moving through the workflow.
        $leaveBalance = [
            'balance'     => (float) $employee->leave_balance,
            'entitlement' => (float) $employee->annual_leave_entitlement,
            'taken'       => $employee->leaveTakenInYear(),
            'pending'     => $employee->leavePendingDays(),
        ];

        $activeLeave = $employee->leaveRequests()
            ->with(['supervisorEmployee', 'supervisorUser'])
            ->where('status', 'Pending')
            ->latest()
            ->first();

        // If this employee supervises others: requests waiting on them.
        $teamAwaiting = LeaveRequest::with('employee')
            ->where('supervisor_employee_id', $employee->id)
            ->stage(LeaveRequest::STAGE_SUPERVISOR)
            ->oldest()
            ->get();
        $recentOvertime = $employee->overtimeRequests()->latest()->take(5)->get();

        $contractDaysLeft = $employee->contract_end
            ? (int) now()->startOfDay()->diffInDays($employee->contract_end, false)
            : null;

        // Announcements (pinned + newest) and today's attendance.
        $announcements = collect();
        $readIds = [];
        $today = null;
        $shiftToday = $shiftTomorrow = null;
        try {
            $ann = app(\App\Services\Announcements::class);
            $ann->emailDue();
            $announcements = \App\Models\Announcement::visibleTo($employee)->take(4);
            $readIds = array_flip($ann->readIds($employee));
            $t = \App\Services\AttendanceService::today();
            $today = app(\App\Services\AttendanceService::class)->matrix($t, $t, null, $employee->id)['cells'][$employee->id][$t->toDateString()] ?? null;
            $att = app(\App\Services\AttendanceService::class);
            $shiftToday = $att->planFor($employee, $t);
            $shiftTomorrow = $att->planFor($employee, $t->copy()->addDay());
        } catch (\Throwable $e) {
            report($e);
        }

        return view('portal.dashboard', compact(
            'announcements',
            'readIds',
            'today',
            'shiftToday',
            'shiftTomorrow',
            'employee',
            'leaveThisYear',
            'pendingLeave',
            'pendingOvertime',
            'overtimeThisMonth',
            'latestPayslip',
            'recentLeave',
            'leaveBalance',
            'activeLeave',
            'teamAwaiting',
            'recentOvertime',
            'contractDaysLeft'
        ));
    }
}
