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
    public function calculateTax($salary)
    {
        if ($salary <= 5100) return 0;

        if ($salary <= 7100) {
            return ($salary - 5100) * 0.20;
        }

        if ($salary <= 9200) {
            return 400 + ($salary - 7100) * 0.30;
        }

        return 1030 + ($salary - 9200) * 0.37;
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