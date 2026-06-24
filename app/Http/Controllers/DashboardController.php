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
     * Dashboard
     */
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | EMPLOYEE STATS
        |--------------------------------------------------------------------------
        */
        $totalEmployees   = Employee::count();
        $activeEmployees  = Employee::where('employment_status', 'Active')->count();

        // Unique departments and branches from the employees table
        $departments = Employee::whereNotNull('department')
            ->distinct('department')
            ->count('department');

        $branches = Employee::whereNotNull('branch')
            ->distinct('branch')
            ->count('branch');

        // New hires this calendar month
        $newThisMonth = Employee::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Contracts expiring in the next 30 days
        $contractsExpiringSoon = Employee::whereNotNull('contract_end')
            ->whereDate('contract_end', '>=', now())
            ->whereDate('contract_end', '<=', now()->addDays(30))
            ->count();

        /*
        |--------------------------------------------------------------------------
        | LEAVE STATS
        |--------------------------------------------------------------------------
        */
        $pendingLeave  = LeaveRequest::where('status', 'Pending')->count();
        $approvedLeave = LeaveRequest::where('status', 'Approved')
            ->whereMonth('created_at', now()->month)
            ->count();

        // Employees currently on leave (approved, dates span today)
        $onLeaveToday = LeaveRequest::where('status', 'Approved')
            ->whereDate('date_from', '<=', now())
            ->whereDate('date_to', '>=', now())
            ->count();

        /*
        |--------------------------------------------------------------------------
        | PAYROLL STATS
        |--------------------------------------------------------------------------
        */
        $latestRun = PayrollRun::with('payrolls')
            ->latest()
            ->first();

        $payrollSummary = null;
        if ($latestRun) {
            $payrollSummary = [
                'period'           => $latestRun->alias ?? $latestRun->period,
                'status'           => $latestRun->status,
                'employee_count'   => $latestRun->payrolls->count(),
                'total_earnings'   => $latestRun->total_income   ?? $latestRun->payrolls->sum('total_income'),
                'total_deductions' => $latestRun->total_deductions ?? $latestRun->payrolls->sum('total_deductions'),
                'net_pay'          => $latestRun->net_pay         ?? $latestRun->payrolls->sum('net_pay'),
                'finalized_at'     => $latestRun->finalized_at,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | DEPARTMENT BREAKDOWN
        |--------------------------------------------------------------------------
        */
        $departmentBreakdown = Employee::where('employment_status', 'Active')
            ->whereNotNull('department')
            ->selectRaw('department, COUNT(*) as count')
            ->groupBy('department')
            ->orderByDesc('count')
            ->limit(6)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | RECENT LEAVE REQUESTS
        |--------------------------------------------------------------------------
        */
        $recentLeave = LeaveRequest::latest()
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | RECENT EMPLOYEES
        |--------------------------------------------------------------------------
        */
        $recentEmployees = Employee::latest()
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | HR ALERTS
        |--------------------------------------------------------------------------
        */
        $alerts = collect();

        if ($contractsExpiringSoon > 0) {
            $alerts->push([
                'message'  => "{$contractsExpiringSoon} employee contract(s) expiring within 30 days",
                'severity' => 'danger',
                'label'    => 'Urgent',
            ]);
        }

        if ($pendingLeave > 0) {
            $alerts->push([
                'message'  => "{$pendingLeave} leave request(s) awaiting approval",
                'severity' => 'warning',
                'label'    => 'Pending',
            ]);
        }

        if ($newThisMonth > 0) {
            $alerts->push([
                'message'  => "{$newThisMonth} new employee(s) added this month",
                'severity' => 'primary',
                'label'    => 'New',
            ]);
        }

        if ($latestRun && $latestRun->status === 'Draft') {
            $alerts->push([
                'message'  => 'Payroll run ' . $latestRun->alias . ' is still in Draft',
                'severity' => 'warning',
                'label'    => 'Payroll',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | COMPILE STATS ARRAY
        |--------------------------------------------------------------------------
        */
        $stats = [
            'employees'              => $totalEmployees,
            'active_employees'       => $activeEmployees,
            'departments'            => $departments,
            'branches'               => $branches,
            'new_this_month'         => $newThisMonth,
            'contracts_expiring'     => $contractsExpiringSoon,
            'pending_leave'          => $pendingLeave,
            'approved_leave_month'   => $approvedLeave,
            'on_leave_today'         => $onLeaveToday,
        ];

        return view('dashboard.index', compact(
            'stats',
            'payrollSummary',
            'departmentBreakdown',
            'recentLeave',
            'recentEmployees',
            'alerts'
        ));
    }

    /**
     * Departments
     */
    public function departments()
    {
        return view('dashboard.departments.index');
    }

    /**
     * Leave
     */
    public function leave()
    {
        return view('dashboard.leave.index');
    }

    /**
     * Attendance
     */
    public function attendance()
    {
        return view('dashboard.attendance.index');
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
}