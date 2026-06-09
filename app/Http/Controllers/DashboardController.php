<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Dashboard
     */
    public function index()
    {
        $stats = [
            'employees' => 0,
            'branches' => 0,
            'departments' => 0,
        ];

        return view('dashboard.index', compact('stats'));
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
        return view('dashboard.payroll.index');
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