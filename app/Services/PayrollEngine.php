<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollRule;
use App\Models\PayrollAssignment;
use App\Models\PayrollRunAdjustment;


class PayrollEngine
{

private function resolveRuleAmount($rule, $employee)
{
    if ($rule->formula_type === 'fixed') {
        return $rule->value;
    }

    if ($rule->formula_type === 'percentage') {
        return ($employee->salary * $rule->value) / 100;
    }

    return 0;
}

private function resolveAssignmentAmount($item, $employee)
{
    if ($item->formula_type === 'fixed') {
        return $item->value;
    }

    if ($item->formula_type === 'percentage') {
        return ($employee->salary * $item->value) / 100;
    }

    return 0;
}


public function build(Payroll $payroll)
{
    $employee = $payroll->employee;

    // 1. CLEAR OLD ITEMS
    $payroll->items()->delete();

    $totalEarnings = 0;
    $totalDeductions = 0;

    /*
    |-------------------------------------------------
    | 2. BASIC SALARY (CORE EARNING)
    |-------------------------------------------------
    */
    $this->addItem($payroll, [
        'code' => 'BASIC',
        'description' => 'Basic Salary',
        'type' => 'earning',
        'amount' => $employee->salary ?? 0,
    ]);

    $totalEarnings += $employee->salary ?? 0;

    /*
    |-------------------------------------------------
    | 3. GLOBAL PAYROLL RULES
    |-------------------------------------------------
    */
    $rules = PayrollRule::where('active', 1)->get();

    foreach ($rules as $rule) {

        $amount = $this->resolveRuleAmount($rule, $employee);

        $this->addItem($payroll, [
            'code' => strtoupper($rule->name),
            'description' => $rule->name,
            'type' => $rule->type,
            'amount' => $amount,
        ]);

        if ($rule->type === 'earning') {
            $totalEarnings += $amount;
        } else {
            $totalDeductions += $amount;
        }
    }

    /*
    |-------------------------------------------------
    | 4. EMPLOYEE ASSIGNMENTS (PERMANENT)
    |-------------------------------------------------
    */
    $assignments = PayrollAssignment::where('employee_id', $employee->id)
        ->where('active', 1)
        ->get();

    foreach ($assignments as $assignment) {

        $amount = $this->resolveAssignmentAmount($assignment, $employee);

        $this->addItem($payroll, [
            'code' => strtoupper($assignment->name),
            'description' => $assignment->name,
            'type' => $assignment->type,
            'amount' => $amount,
        ]);

        if ($assignment->type === 'earning') {
            $totalEarnings += $amount;
        } else {
            $totalDeductions += $amount;
        }
    }

    /*
    |-------------------------------------------------
    | 5. RUN ADJUSTMENTS (ONE-TIME PER PAYRUN)
    |-------------------------------------------------
    */
    $runAdjustments = PayrollRunAdjustment::where('payroll_run_id', $payroll->payroll_run_id)
        ->where('employee_id', $employee->id)
        ->where('active', 1)
        ->get();

    foreach ($runAdjustments as $adjustment) {

        $amount = $adjustment->value ?? 0;

        $this->addItem($payroll, [
            'code' => strtoupper($adjustment->name),
            'description' => $adjustment->name,
            'type' => $adjustment->type,
            'amount' => $amount,
        ]);

        if ($adjustment->type === 'earning') {
            $totalEarnings += $amount;
        } else {
            $totalDeductions += $amount;
        }
    }

    /*
    |-------------------------------------------------
    | 6. NAPSA (UNCHANGED)
    |-------------------------------------------------
    */
    $napsa = ($employee->salary * 0.05);

    $this->addItem($payroll, [
        'code' => 'NAPSA',
        'description' => 'NAPSA Contribution (5%)',
        'type' => 'deduction',
        'amount' => $napsa,
    ]);

    $totalDeductions += $napsa;

    /*
    |-------------------------------------------------
    | 7. ZRA TAX (UNCHANGED - YOUR LOGIC IS SAFE)
    |-------------------------------------------------
    */
    $tax = $this->calculateTax($employee->salary);

    $this->addItem($payroll, [
        'code' => 'PAYE',
        'description' => 'PAYE Tax',
        'type' => 'deduction',
        'amount' => $tax,
    ]);

    $totalDeductions += $tax;

    /*
    |-------------------------------------------------
    | 8. UPDATE PAYROLL TOTALS
    |-------------------------------------------------
    */
    $payroll->update([
        'total_income' => $totalEarnings,
        'total_deductions' => $totalDeductions,
        'net_pay' => $totalEarnings - $totalDeductions,
    ]);

    return $payroll;
}
    /*
    |--------------------------------------------------------------------------
    | ZRA TAX SIMPLIFIED (YOU CAN EXPAND LATER)
    |--------------------------------------------------------------------------
    */
public function calculateTax(float $salary): float
{
    $tax = 0;

    // ZRA tax bands
    $brackets = [
        ['min' => 0,    'max' => 5100, 'rate' => 0],
        ['min' => 5100, 'max' => 7100, 'rate' => 0.20],
        ['min' => 7100, 'max' => 9200, 'rate' => 0.30],
        ['min' => 9200, 'max' => null, 'rate' => 0.37],
    ];

    foreach ($brackets as $bracket) {

        if ($salary <= $bracket['min']) {
            continue;
        }

        $upper = $bracket['max'] ?? $salary;

        $taxable = min($salary, $upper) - $bracket['min'];

        if ($taxable > 0) {
            $tax += $taxable * $bracket['rate'];
        }
    }

    return round($tax, 2);
}

    /*
    |--------------------------------------------------------------------------
    | ADD PAYROLL ITEM
    |--------------------------------------------------------------------------
    */
    private function addItem(Payroll $payroll, array $data)
    {
        return PayrollItem::create([
            'payroll_id'  => $payroll->id,
            'code'        => $data['code'],
            'description' => $data['description'],
            'type'        => $data['type'],
            'amount'      => $data['amount'],
        ]);
    }
}