<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Services\PayrollEngine;


class PayrollController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST ALL PAYROLLS
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $payrolls = Payroll::with('employee')
            ->latest()
            ->paginate(10);

        return view('dashboard.payroll.index', compact('payrolls'));
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW CREATE FORM
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $employees = Employee::orderBy('first_name')->get();

        return view('dashboard.payroll.create', compact('employees'));
    }

    /*
    |--------------------------------------------------------------------------
    | STORE PAYROLL (INITIAL CREATION)
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'pay_period'  => 'required'
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        $payroll = Payroll::create([
            'employee_id'   => $employee->id,
            'pay_period'    => $request->pay_period,
            'company'       => 'TRADESMART SUPPLIES LIMITED',
            'branch'        => $employee->branch,
            'cost_centre'   => $employee->department,
            'date_engaged'  => $employee->contract_start,
            'salary_rate'   => $employee->salary ?? 0,
            'status'        => 'Draft',
        ]);

        return redirect()
            ->route('payroll.show', $payroll->id)
            ->with('success', 'Payroll created successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW SINGLE PAYROLL
    |--------------------------------------------------------------------------
    */
    public function show(Payroll $payroll)
    {
        $payroll->load(['employee', 'items']);

        return view('dashboard.payroll.show', compact('payroll'));
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT PAYROLL
    |--------------------------------------------------------------------------
    */
    public function edit(Payroll $payroll)
    {
        $employees = Employee::orderBy('first_name')->get();

        return view('dashboard.payroll.edit', compact('payroll', 'employees'));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PAYROLL
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, Payroll $payroll)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'pay_period'  => 'required',
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        $payroll->update([
            'employee_id'  => $employee->id,
            'pay_period'   => $request->pay_period,
            'branch'       => $employee->branch,
            'cost_centre'  => $employee->department,
            'date_engaged' => $employee->contract_start,
            'salary_rate'  => $employee->salary,
        ]);

        return redirect()
            ->route('payroll.index')
            ->with('success', 'Payroll updated successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE PAYROLL
    |--------------------------------------------------------------------------
    */
    public function destroy(Payroll $payroll)
    {
        $payroll->delete();

        return redirect()
            ->route('payroll.index')
            ->with('success', 'Payroll deleted successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | PROCESS PAYROLL (CALCULATE NET PAY)
    |--------------------------------------------------------------------------
    */

    public function process(Payroll $payroll, PayrollEngine $engine)
    {
        // 1. BUILD PAYROLL ITEMS AUTOMATICALLY
        $engine->build($payroll);

        // 2. CALCULATE TOTALS
        $income = $payroll->items()
            ->where('type', 'earning')
            ->sum('amount');

        $deductions = $payroll->items()
            ->where('type', 'deduction')
            ->sum('amount');

        $net = $income - $deductions;

        // 3. UPDATE PAYROLL
        $payroll->update([
            'total_income'     => $income,
            'total_deductions' => $deductions,
            'net_pay'          => $net,
            'status'           => 'Processed'
        ]);

        return back()->with('success', 'Payroll fully processed by engine.');
    }

    /*
    |--------------------------------------------------------------------------
    | PRINT PAYROLL (OPTIONAL BUT USEFUL)
    |--------------------------------------------------------------------------
    */
    public function print(Payroll $payroll)
    {
        $payroll->load(['employee', 'items']);

        return view('dashboard.payroll.print', compact('payroll'));
    }
}