<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Models\Payroll;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRule;
use App\Models\PayrollRunAdjustment;
use App\Services\PayrollEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PayrollAdjustmentTemplateExport;
use App\Imports\PayrollAdjustmentImport;

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

    /*
    |--------------------------------------------------------------------------
    | PAYSLIPS VIEW
    |--------------------------------------------------------------------------
    */
    public function payslips($runId)
    {
        $run = PayrollRun::with(['payrolls.employee', 'payrolls.items'])
            ->findOrFail($runId);

        $companyName = 'TRADESMART SUPPLIES LIMITED';
        $companyLogo = 'http://misc.tradesmartzm.com/logo.png';
        

        return view('dashboard.payroll.runs.payslips', compact('run', 'companyName' ,'companyLogo'));
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
            'period' => 'required',
            'alias'  => 'nullable|string|max:255',
        ]);

        $run = PayrollRun::create([
            'period'     => $request->period,
            'alias'      => $request->alias,
            'created_by' => auth()->id(),
            'status'     => 'Draft',
        ]);

        return redirect()->route('payroll.runs.show', $run->id);
    }
    /*
    |--------------------------------------------------------------------------
    | SHOW RUN
    |--------------------------------------------------------------------------
    */
    public function show(PayrollRun $run)
    {
        $run->load('payrolls.employee', 'payrolls.items');
        $rules = PayrollRule::where(
            'requires_assignment',
            true
        )->get();


        return view('dashboard.payroll.runs.show', compact('run','rules'));
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE PAYROLL FOR ALL ACTIVE EMPLOYEES
    |--------------------------------------------------------------------------
    */
    public function generate(PayrollRun $run, PayrollEngine $engine)
    {
        if ($run->status === 'Approved') {
            return back()->with('error', 'This payroll run has already been finalized.');
        }

        $employees = Employee::where('employment_status', 'Active')->get();

        foreach ($employees as $employee) {
            $payroll = Payroll::updateOrCreate(
                [
                    'payroll_run_id' => $run->id,
                    'employee_id'    => $employee->id,
                ],
                [
                    'pay_period'   => $run->period,
                    'company'      => 'TRADESMART SUPPLIES LIMITED',
                    'branch'       => $employee->branch,
                    'cost_centre'  => $employee->department,
                    'date_engaged' => $employee->contract_start,
                    'salary_rate'  => $employee->salary ?? 0,
                    'status'       => 'Draft',
                ]
            );

            $engine->build($payroll);
        }

        $run->update(['status' => 'Processed']);

        return back()->with('success', 'Payroll generated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | STORE ADJUSTMENT — saves to payroll_run_adjustments, then rebuilds
    |--------------------------------------------------------------------------
    |
    | Flow:
    |   1. Validate — tax_profile required for earnings, ignored for deductions
    |   2. Guard — cannot adjust a finalized run
    |   3. Save adjustment record
    |   4. engine->build() wipes and rewrites the entire payslip from scratch
    |      so PAYE, NAPSA, Basic Pay are all recalculated against the new base
    |
    */
    public function storeAdjustment(
        Request $request,
        PayrollRun $run,
        PayrollEngine $engine
    ) {
        if ($run->status === 'Approved') {
            return back()->with('error', 'Cannot adjust a finalized payroll run.');
        }

        $request->validate([
            'employee_id'  => 'required|exists:employees,id',
            'name'         => 'required|string|max:255',
            'type'         => 'required|in:earning,deduction,system',
            'formula_type' => 'required|in:fixed,percentage',
            'value'        => 'required|numeric|min:0',

            // tax_profile only matters for earnings:
            //   taxable     → PAYE + NAPSA  (Overtime, Bonus, Commission)
            //   napsa_only  → NAPSA only    (Gratuity, Payment in lieu of notice, Ex gratia)
            //   non_taxable → neither       (Reimbursements)
            'tax_profile' => [
                'nullable',
                'required_if:type,earning',
                'in:taxable,napsa_only,non_taxable',
            ],
            'rule_id' => 'nullable|string|max:255'
        ]);

        // Save the adjustment record — engine reads this on build()
        PayrollRunAdjustment::create([
            'payroll_run_id' => $run->id,
            'employee_id'    => $request->employee_id,
            'payroll_rule_id'        => $request->rule_id,
            'name'           => $request->name,
            'type'           => $request->type,
            'formula_type'   => $request->formula_type,
            'value'          => $request->value,
            'tax_profile'    => $request->type === 'earning'
                                    ? $request->tax_profile
                                    : null,   // deductions don't use tax_profile
            'active'         => true,
            
        ]);

        // Find or create the payroll record for this employee in this run
        $payroll = Payroll::where('payroll_run_id', $run->id)
            ->where('employee_id', $request->employee_id)
            ->first();

        if (! $payroll) {
            return back()->with(
                'error',
                'Generate payroll for this employee before adding adjustments.'
            );
        }

        // Rebuild — engine wipes all items and recalculates everything from scratch,
        // including PAYE and NAPSA on the new bases with this adjustment included.
        $engine->build($payroll);

        Log::info('Payroll adjustment added and payslip rebuilt', [
            'run_id'      => $run->id,
            'employee_id' => $request->employee_id,
            'name'        => $request->name,
            'type'        => $request->type,
            'tax_profile' => $request->tax_profile,
            'value'       => $request->value,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Adjustment saved and payroll recalculated.',
        ]);
    }

    public function downloadAdjustmentTemplate(
        PayrollRun $run, PayrollRule $rule
    ) {
        $file_name = "ADJ-" . $rule->code . "-" . $rule->name . "-" . $run->alias . ".xlsx";

        return Excel::download(
            new PayrollAdjustmentTemplateExport($run),
             $file_name
        );
    }

    public function importAdjustmentsExcel(
        Request $request,
        PayrollRun $run
    ) {
        $request->validate([
            'rule_id' => 'required|exists:payroll_rules,id',
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        if ($run->status === 'Approved') {

            return back()->with(
                'error',
                'Cannot modify a finalized payroll run.'
            );
        }

        Excel::import(
            new PayrollAdjustmentImport(
                $run,
                $request->rule_id
            ),
            $request->file('file')
        );

        return back()->with(
            'success',
            'Adjustments imported successfully.'
        );
    }

    


    /*
    |--------------------------------------------------------------------------
    | DELETE ADJUSTMENT — removes the record, then rebuilds
    |--------------------------------------------------------------------------
    */
    public function destroyAdjustment(
        PayrollRunAdjustment $adjustment,
        PayrollEngine $engine
    ) {
        $run = $adjustment->run;

        if ($run->status === 'Approved') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify a finalized payroll run.',
            ], 403);
        }

        $payroll = Payroll::where('payroll_run_id', $adjustment->payroll_run_id)
            ->where('employee_id', $adjustment->employee_id)
            ->first();

        $adjustment->delete();

        if ($payroll) {
            // Rebuild without the deleted adjustment
            $engine->build($payroll);
        }

        return response()->json([
            'success' => true,
            'message' => 'Adjustment removed and payroll recalculated.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | FINALIZE RUN
    |--------------------------------------------------------------------------
    */
    public function finalize(PayrollRun $run)
    {
        if ($run->payrolls()->count() === 0) {
            return back()->with('error', 'Generate payroll before finalizing.');
        }

        // Lock all payslips
        Payroll::where('payroll_run_id', $run->id)
            ->update(['status' => 'Approved']);

        // Aggregate from locked payslip records
        $totalIncome     = $run->payrolls()->sum('total_income');
        $totalDeductions = $run->payrolls()->sum('total_deductions');
        $totalNet        = $run->payrolls()->sum('net_pay');

        $run->update([
            'total_income'     => $totalIncome,
            'total_deductions' => $totalDeductions,
            'net_pay'          => $totalNet,
            'status'           => 'Approved',
            'finalized_at'     => now(),
            'finalized_by'     => auth()->id(),
        ]);

        return back()->with('success', 'Payroll run finalized successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE SUMMARY (partial view)
    |--------------------------------------------------------------------------
    */
    public function employeeSummary(PayrollRun $run, Payroll $payroll)
    {
        $payroll->load(['employee', 'items']);

        return view(
            'dashboard.payroll.runs.partials.employee-summary',
            compact('payroll')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE FIELD (inline edit on a payroll item)
    |--------------------------------------------------------------------------
    |
    | NOTE: This is a direct item override — it does NOT re-run the engine.
    | Use only for minor corrections (e.g. fixing a description or a one-off
    | amount override). Statutory deductions will NOT be recalculated.
    | For changes that affect tax, use storeAdjustment() instead.
    |
    */
    public function updateField(Request $request, PayrollItem $item)
    {
        $request->validate([
            'field' => 'required|in:description,amount,code',
            'value' => 'required',
        ]);

        $payroll = $item->payroll;

        if ($payroll->status === 'Approved') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit a finalized payslip.',
            ], 403);
        }

        Log::info('Payroll item field updated (direct override)', [
            'item_id'    => $item->id,
            'payroll_id' => $item->payroll_id,
            'field'      => $request->field,
            'old_value'  => $item->{$request->field},
            'new_value'  => $request->value,
        ]);

        $item->{$request->field} = $request->value;
        $item->save();

        // Recalculate totals from items (direct sum — engine not re-run)
        $this->syncPayrollTotals($payroll);

        return response()->json(['success' => true, 'message' => 'Updated successfully.']);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE ITEM (direct payroll item removal)
    |--------------------------------------------------------------------------
    |
    | Same caveat as updateField — use only for non-statutory line items.
    | Removing PAYE or NAPSA this way will leave totals inconsistent.
    |
    */
    public function deleteItem(PayrollItem $item)
    {
        $payroll = $item->payroll;

        if ($payroll && $payroll->status === 'Approved') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit a finalized payslip.',
            ], 403);
        }

        $item->delete();

        if ($payroll) {
            $this->syncPayrollTotals($payroll);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item deleted.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC PAYROLL TOTALS (private — sums items into payroll record)
    |--------------------------------------------------------------------------
    |
    | Only used after direct item edits (updateField / deleteItem).
    | All adjustment-driven changes go through engine->build() instead.
    |
    */
    private function syncPayrollTotals(Payroll $payroll): void
    {
        $income     = $payroll->items()->where('type', 'earning')->sum('amount');
        $deductions = $payroll->items()->where('type', 'deduction')->sum('amount');

        $payroll->update([
            'total_income'     => $income,
            'total_deductions' => $deductions,
            'net_pay'          => $income - $deductions,
        ]);
    }
}