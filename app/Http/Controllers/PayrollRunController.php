<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Models\Payroll;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRunAdjustment;
use App\Services\PayrollEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

if ($run->status === 'Approved') {
    return back()->with(
        'error',
        'This payroll run has already been finalized.'
    );
}

    $employees = Employee::where('employment_status', 'Active')
        ->get();

    foreach ($employees as $employee) {

        $payroll = Payroll::updateOrCreate(

            [
                'payroll_run_id' => $run->id,
                'employee_id' => $employee->id,
            ],

            [
                'pay_period' => $run->period,
                'company' => 'TRADESMART SUPPLIES LIMITED',
                'branch' => $employee->branch,
                'cost_centre' => $employee->department,
                'date_engaged' => $employee->contract_start,
                'salary_rate' => $employee->salary ?? 0,
                'status' => 'Draft',
            ]
        );

        $engine->build($payroll);
    }

    $run->update([
        'status' => 'Processed'
    ]);

    return back()->with(
        'success',
        'Payroll regenerated successfully.'
    );
}

    /*
    |--------------------------------------------------------------------------
    | FINALIZE RUN (CALCULATE TOTALS)
    |--------------------------------------------------------------------------
    */
    // public function finalize(PayrollRun $run)
    // {


    //     $run->load('payrolls.items');

    //     $totalIncome = 0;
    //     $totalDeductions = 0;
    //     $totalNet = 0;

    //     if ($run->payrolls()->count() == 0) {
    //         return back()->with(
    //             'error',
    //             'Generate payroll before finalizing.'
    //         );
    //     }

    //     foreach ($run->payrolls as $payroll) {

    //         // 🔥 RECALCULATE FROM ITEMS (source of truth)
    //         $income = $payroll->items
    //             ->where('type', 'earning')
    //             ->sum('amount');

    //         $deductions = $payroll->items
    //             ->where('type', 'deduction')
    //             ->sum('amount');

    //         $net = $income - $deductions;

    //         // 🔥 UPDATE PAYROLL
    //         $payroll->update([
    //             'total_income' => $income,
    //             'total_deductions' => $deductions,
    //             'net_pay' => $net,
    //             'status' => 'Processed'
    //         ]);

    //         // 🔥 RUN TOTALS
    //         $totalIncome += $income;
    //         $totalDeductions += $deductions;
    //         $totalNet += $net;
    //     }

    //     // 🔥 UPDATE RUN
    //     $run->update([
    //         'total_income' => $totalIncome,
    //         'total_deductions' => $totalDeductions,
    //         'net_pay' => $totalNet,
    //         'status' => 'Processed'
    //     ]);

    //     return back()->with('success', 'Payroll run Processed successfully.');
    // }

public function finalize(PayrollRun $run)
{
    if ($run->payrolls()->count() == 0) {
        return back()->with(
            'error',
            'Generate payroll before finalizing.'
        );
    }

    // Lock all payslips
    Payroll::where('payroll_run_id', $run->id)
        ->update([
            'status' => 'Approved'
        ]);

    // Calculate run summary from payroll records
    $totalIncome = $run->payrolls()->sum('total_income');
    $totalDeductions = $run->payrolls()->sum('total_deductions');
    $totalNet = $run->payrolls()->sum('net_pay');

    // Lock payroll run
    $run->update([
        'total_income' => $totalIncome,
        'total_deductions' => $totalDeductions,
        'net_pay' => $totalNet,
        'status' => 'Approved',
        'finalized_at' => now(),
        'finalized_by' => auth()->id(),
    ]);

    return back()->with(
        'success',
        'Payroll run finalized successfully.'
    );
}

// public function storeAdjustment(
//     Request $request,
//     PayrollRun $run,
//     PayrollEngine $engine
// )
// {
//     $request->validate([
//         'employee_id' => 'required',
//         'name' => 'required',
//         'type' => 'required|in:earning,deduction',
//         'formula_type' => 'required|in:fixed,percentage',
//         'value' => 'required|numeric',
//     ]);

//     PayrollRunAdjustment::create([
//         'payroll_run_id' => $run->id,
//         'employee_id' => $request->employee_id,
//         'name' => $request->name,
//         'type' => $request->type,
//         'formula_type' => $request->formula_type,
//         'value' => $request->value,
//         'active' => true,
//     ]);

//     // Rebuild employee payroll
//     $payroll = Payroll::where('payroll_run_id', $run->id)
//         ->where('employee_id', $request->employee_id)
//         ->first();

//     if ($payroll) {
//         $engine->build($payroll);
//     }

//     return back()->with(
//         'success',
//         'Adjustment added and payroll recalculated.'
//     );
// }

public function employeeSummary(
    PayrollRun $run,
    Payroll $payroll
)
{
    $payroll->load([
        'employee',
        'items'
    ]);

    return view(
        'dashboard.payroll.runs.partials.employee-summary',
        compact('payroll')
    );
}

private function recalculatePayroll($payroll)
{
    $income = $payroll->items()->where('type', 'earning')->sum('amount');
    $deductions = $payroll->items()->where('type', 'deduction')->sum('amount');

    $payroll->update([
        'total_income' => $income,
        'total_deductions' => $deductions,
        'net_pay' => $income - $deductions,
    ]);
}


public function updateField(Request $request, PayrollItem $item)
{
    $request->validate([
        'field' => 'required',
        'value' => 'required'
    ]);

    Log::info('Payroll item field updated', [
        'item_id' => $item->id,
        'payroll_id' => $item->payroll_id,
        'field' => $request->field,
        'value' => $request->value,
    ]);

    $item->{$request->field} = $request->value;
    $item->save();

    // optional: recalc payroll totals
    $this->recalculatePayroll($item->payroll);

    return response()->json(['success' => true, 'message' => $request->value + ' Updated Successfully']);
}

public function deleteAdjustment(PayrollItem $adjustment)
{
    $payroll = Payroll::where('payroll_run_id', $adjustment->payroll_run_id)
        ->where('employee_id', $adjustment->employee_id)
        ->first();

    $adjustment->delete();

    if ($payroll) {
        app(\App\Services\PayrollEngine::class)->build($payroll);
    }

    // recalc totals
    $this->recalculatePayroll($payroll);


    return response()->json([
        'success' => true,
        'message' => 'Deleted successfully'
    ]);
}

public function storeItem(Request $request, Payroll $payroll)
{
    $request->validate([
        'description' => 'required',
        'type' => 'required|in:earning,deduction',
        'amount' => 'required|numeric',
    ]);

    $item = $payroll->items()->create([
        'code' => $request->description,
        'description' => $request->description,
        'type' => $request->type,
        'amount' => $request->amount,
    ]);

    // recalc totals
    $this->recalculatePayroll($payroll);

    return response()->json([
        'success' => true,
        'item' => $item,
        'message' => 'Item added successfully'
    ]);
}




}