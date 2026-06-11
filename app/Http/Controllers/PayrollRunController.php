<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Models\Payroll;
use App\Models\Employee;
use App\Services\PayrollEngine;
use Illuminate\Http\Request;

class PayrollRunController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST ALL PAYROLL RUNS
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $runs = PayrollRun::with('payrolls.employee')
            ->latest()
            ->get();

        return view('dashboard.payroll.runs.index', compact('runs'));
    }

    public function payslips($runId)
{
    $run = PayrollRun::with(['payrolls.employee', 'payrolls.items'])
        ->findOrFail($runId);

    return view('dashboard.payroll.runs.payslips', compact('run'));
}

    /*
    |--------------------------------------------------------------------------
    | CREATE NEW RUN
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        return view('dashboard.payroll.runs.create');
    }

    /*
    |--------------------------------------------------------------------------
    | STORE NEW RUN
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'period' => 'required'
        ]);

        $run = PayrollRun::create([
            'period' => $request->period,
            'status' => 'Draft'
        ]);

        return redirect()->route('payroll.runs.show', $run->id);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW RUN (ALL EMPLOYEES)
    |--------------------------------------------------------------------------
    */
    public function show(PayrollRun $run)
    {
        $run->load('payrolls.employee', 'payrolls.items');

        return view('dashboard.payroll.runs.show', compact('run'));
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE PAYROLL FOR ALL EMPLOYEES
    |--------------------------------------------------------------------------
    */
    public function generate(PayrollRun $run, PayrollEngine $engine)
    {
        $employees = Employee::all();

        foreach ($employees as $employee) {

            $payroll = Payroll::create([
                'employee_id' => $employee->id,
                'payroll_run_id' => $run->id,
                'pay_period' => $run->period,
                'company' => 'TRADESMART SUPPLIES LIMITED',
                'branch' => $employee->branch,
                'cost_centre' => $employee->department,
                'date_engaged' => $employee->contract_start,
                'salary_rate' => $employee->salary ?? 0,
                'status' => 'Draft'
            ]);

            // build items via engine
            $engine->build($payroll);
        }

        $run->update([
            'status' => 'Processed'
        ]);

        return back()->with('success', 'Payroll run generated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | FINALIZE RUN (CALCULATE TOTALS)
    |--------------------------------------------------------------------------
    */
    public function finalize(PayrollRun $run)
    {
        $income = $run->payrolls()->sum('total_income');
        $deductions = $run->payrolls()->sum('total_deductions');
        $net = $run->payrolls()->sum('net_pay');

        $run->update([
            'total_income' => $income,
            'total_deductions' => $deductions,
            'net_pay' => $net,
            'status' => 'Completed'
        ]);

        return back()->with('success', 'Payroll run finalized.');
    }
}