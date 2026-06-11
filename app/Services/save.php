<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\PayrollItem;

class PayrollEngine
{
    public function build(Payroll $payroll)
    {
        $employee = $payroll->employee;

        // 1. CLEAR OLD ITEMS
        $payroll->items()->delete();

        // 2. BASIC SALARY
        $this->addItem($payroll, [
            'code' => 'BASIC',
            'description' => 'Basic Salary',
            'type' => 'earning',
            'amount' => $employee->salary ?? 0,
        ]);

        // 3. TRANSPORT ALLOWANCE (example rule)
        $this->addItem($payroll, [
            'code' => 'TRANS',
            'description' => 'Transport Allowance',
            'type' => 'earning',
            'amount' => 500,
        ]);

        // 4. NAPSA (5%)
        $napsa = ($employee->salary * 0.05);

        $this->addItem($payroll, [
            'code' => 'NAPSA',
            'description' => 'NAPSA Contribution (5%)',
            'type' => 'deduction',
            'amount' => $napsa,
        ]);

        // 5. TAX (ZRA SIMPLIFIED)
        $tax = $this->calculateTax($employee->salary);

        $this->addItem($payroll, [
            'code' => 'PAYE',
            'description' => 'PAYE Tax',
            'type' => 'deduction',
            'amount' => $tax,
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