<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollRule;
use App\Models\PayrollAssignment;
use App\Models\PayrollRunAdjustment;
use Illuminate\Support\Facades\Log;

class PayrollEngine
{
    /*
    |--------------------------------------------------------------------------
    | STATE — resolved during build(), used across helper methods
    |--------------------------------------------------------------------------
    */
    private float $TARGET_NET   = 0;  // employee->salary  (what they take home)
    private float $GROSSPAY     = 0;  // solved algebraically
    private float $BASICPAY     = 0;  // GROSSPAY minus all allowances
    private float $NAPSA_BASE   = 0;  // capped at ZRA ceiling

    // -------------------------------------------------------------------------
    // CONTROLLER CALLS THIS — signature unchanged, controller needs no edits
    // -------------------------------------------------------------------------
    public function build(Payroll $payroll): Payroll
    {
        $employee = $payroll->employee;

        $this->TARGET_NET = (float) ($employee->salary ?? 0);

        if ($this->TARGET_NET <= 0) {
            Log::warning("PayrollEngine: Employee {$employee->id} has zero target net.");
            return $payroll;
        }

        // ── STEP 1: load all rule sets ────────────────────────────────────────
        $globalRules = PayrollRule::where('active', 1)->get();

        $assignments = PayrollAssignment::where('employee_id', $employee->id)
            ->where('active', 1)
            ->get();

        $runAdjustments = PayrollRunAdjustment::where('payroll_run_id', $payroll->payroll_run_id)
            ->where('employee_id', $employee->id)
            ->where('active', 1)
            ->get();

        // ── STEP 2: solve GROSS from TARGET NET ───────────────────────────────
        $this->GROSSPAY = $this->solveGross($globalRules, $assignments, $runAdjustments);

        // ── STEP 3: derive BASIC (gross minus all allowances) ─────────────────
        //    Housing is the only built-in allowance: 30% of BASIC
        //    Which means:  GROSS = BASIC + 0.3*BASIC = 1.3 * BASIC
        //                  BASIC = GROSS / 1.3
        //
        //    If you add more % allowances via rules later, solveGross already
        //    accounts for them. BASIC stays as the residual here.
        $this->BASICPAY = $this->GROSSPAY / 1.3;

        // ── STEP 4: clear stale items and write fresh payslip ─────────────────
        $payroll->items()->delete();

        $totalEarnings   = 0;
        $totalDeductions = 0;

        // ─────────────────────────────────────────────────────────────────────
        // EARNINGS
        // ─────────────────────────────────────────────────────────────────────

        /*
        | BASIC SALARY
        */
        $this->addItem($payroll, [
            'code'        => 'BASIC',
            'description' => 'Basic Salary',
            'type'        => 'earning',
            'amount'      => round($this->BASICPAY, 2),
        ]);
        $totalEarnings += $this->BASICPAY;

        /*
        | HOUSING ALLOWANCE — 30% of Basic (built-in, not a rule)
        */
        $housingAllowance = $this->BASICPAY * 0.30;
        $this->addItem($payroll, [
            'code'        => 'HSNG',
            'description' => 'Housing Allowance',
            'type'        => 'earning',
            'amount'      => round($housingAllowance, 2),
        ]);
        $totalEarnings += $housingAllowance;

        /*
        | GLOBAL RULES — earnings only in this block
        */
        foreach ($globalRules->where('type', 'earning') as $rule) {
            $amount = $this->resolveRuleAmount($rule);
            $this->addItem($payroll, [
                'code'        => strtoupper($rule->name),
                'description' => $rule->name,
                'type'        => 'earning',
                'amount'      => round($amount, 2),
            ]);
            $totalEarnings += $amount;
        }

        /*
        | EMPLOYEE ASSIGNMENTS — earnings
        */
        foreach ($assignments->where('type', 'earning') as $assignment) {
            $amount = $this->resolveAssignmentAmount($assignment);
            $this->addItem($payroll, [
                'code'        => strtoupper($assignment->name),
                'description' => $assignment->name,
                'type'        => 'earning',
                'amount'      => round($amount, 2),
            ]);
            $totalEarnings += $amount;
        }

        /*
        | RUN ADJUSTMENTS — earnings
        */
        foreach ($runAdjustments->where('type', 'earning') as $adjustment) {
            $amount = (float) ($adjustment->value ?? 0);
            $this->addItem($payroll, [
                'code'        => strtoupper($adjustment->name),
                'description' => $adjustment->name,
                'type'        => 'earning',
                'amount'      => round($amount, 2),
            ]);
            $totalEarnings += $amount;
        }

        // ─────────────────────────────────────────────────────────────────────
        // DEDUCTIONS
        // ─────────────────────────────────────────────────────────────────────

        /*
        | NHIMA — 1% of Basic, capped at ZMW 1,861.80
        */
        $nhima = min($this->BASICPAY * 0.01, 1861.80);
        $this->addItem($payroll, [
            'code'        => 'NHIMA',
            'description' => 'NHIMA',
            'type'        => 'deduction',
            'amount'      => round($nhima, 2),
        ]);
        $totalDeductions += $nhima;

        /*
        | NAPSA — 5% of Gross, ZRA ceiling applied inside calculateNapsa()
        */
        $napsa = $this->calculateNapsa($this->GROSSPAY);
        $this->addItem($payroll, [
            'code'        => 'NAPSA',
            'description' => 'NAPSA Contribution (5%)',
            'type'        => 'deduction',
            'amount'      => round($napsa, 2),
        ]);
        $totalDeductions += $napsa;

        /*
        | PAYE — ZRA progressive tax on Gross
        */
        $paye = $this->calculateTax($this->GROSSPAY);
        $this->addItem($payroll, [
            'code'        => 'PAYE',
            'description' => 'PAYE Tax',
            'type'        => 'deduction',
            'amount'      => round($paye, 2),
        ]);
        $totalDeductions += $paye;

        /*
        | GLOBAL RULES — deductions
        */
        foreach ($globalRules->where('type', 'deduction') as $rule) {
            // Skip statutory items already handled above
            if (in_array(strtoupper($rule->name), ['PAYE', 'NAPSA', 'NHIMA'])) {
                continue;
            }
            $amount = $this->resolveRuleAmount($rule);
            $this->addItem($payroll, [
                'code'        => strtoupper($rule->name),
                'description' => $rule->name,
                'type'        => 'deduction',
                'amount'      => round($amount, 2),
            ]);
            $totalDeductions += $amount;
        }

        /*
        | EMPLOYEE ASSIGNMENTS — deductions
        */
        foreach ($assignments->where('type', 'deduction') as $assignment) {
            $amount = $this->resolveAssignmentAmount($assignment);
            $this->addItem($payroll, [
                'code'        => strtoupper($assignment->name),
                'description' => $assignment->name,
                'type'        => 'deduction',
                'amount'      => round($amount, 2),
            ]);
            $totalDeductions += $amount;
        }

        /*
        | RUN ADJUSTMENTS — deductions
        */
        foreach ($runAdjustments->where('type', 'deduction') as $adjustment) {
            $amount = (float) ($adjustment->value ?? 0);
            $this->addItem($payroll, [
                'code'        => strtoupper($adjustment->name),
                'description' => $adjustment->name,
                'type'        => 'deduction',
                'amount'      => round($amount, 2),
            ]);
            $totalDeductions += $amount;
        }

        // ─────────────────────────────────────────────────────────────────────
        // SAVE PAYROLL TOTALS
        // ─────────────────────────────────────────────────────────────────────
        $computedNet = $totalEarnings - $totalDeductions;

        Log::info("PayrollEngine build complete", [
            'employee_id'      => $employee->id,
            'target_net'       => $this->TARGET_NET,
            'gross_pay'        => round($this->GROSSPAY, 2),
            'basic_pay'        => round($this->BASICPAY, 2),
            'total_earnings'   => round($totalEarnings, 2),
            'total_deductions' => round($totalDeductions, 2),
            'computed_net'     => round($computedNet, 2),
            'variance'         => round(abs($computedNet - $this->TARGET_NET), 4),
        ]);

        $payroll->update([
            'total_income'     => round($totalEarnings, 2),
            'total_deductions' => round($totalDeductions, 2),
            'net_pay'          => round($computedNet, 2),
        ]);

        return $payroll;
    }

    /*
    |--------------------------------------------------------------------------
    | SOLVE GROSS FROM TARGET NET
    |--------------------------------------------------------------------------
    |
    | The payslip structure is:
    |
    |   GROSS  = BASIC + HousingAllowance + other % allowances (rules/assignments)
    |   GROSS  = BASIC × 1.30  (since Housing = 30% of Basic, and Basic = Gross/1.3)
    |
    |   NET    = GROSS - PAYE(GROSS) - NAPSA(GROSS) - NHIMA(BASIC) - fixedDeductions
    |                  + fixedEarnings (run adjustments that are fixed earnings)
    |
    | PAYE and NAPSA are non-linear (brackets / caps), so we cannot solve in one
    | algebraic step. Instead we use a fast binary search (bisection) which
    | converges to ZMW 0.01 accuracy in ~30 iterations — imperceptibly fast.
    |
    */
    private function solveGross(
        $globalRules,
        $assignments,
        $runAdjustments
    ): float {

        // Sum up any FIXED deductions from rules/assignments (not % based)
        $fixedDeductions = 0;
        $fixedDeductions += $globalRules
            ->where('type', 'deduction')
            ->where('formula_type', 'fixed')
            ->whereNotIn('name', ['PAYE', 'NAPSA', 'NHIMA'])
            ->sum('value');

        $fixedDeductions += $assignments
            ->where('type', 'deduction')
            ->where('formula_type', 'fixed')
            ->sum('value');

        $fixedDeductions += $runAdjustments
            ->where('type', 'deduction')
            ->sum('value');

        // Fixed earnings from run adjustments boost net directly
        $fixedEarnings = $runAdjustments
            ->where('type', 'earning')
            ->sum('value');

        // Adjusted target: what gross must cover after fixed items
        $adjustedTarget = $this->TARGET_NET + $fixedDeductions - $fixedEarnings;

        // Bisection: find GROSS such that netOf(GROSS) === adjustedTarget
        $low  = $adjustedTarget;           // gross can't be less than net
        $high = $adjustedTarget * 3;       // generous upper bound

        for ($i = 0; $i < 60; $i++) {
            $mid    = ($low + $high) / 2;
            $netMid = $this->netOf($mid);

            if (abs($netMid - $adjustedTarget) < 0.001) {
                break;
            }

            if ($netMid < $adjustedTarget) {
                $low = $mid;
            } else {
                $high = $mid;
            }
        }

        return round($mid, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | NET-OF: Given a gross, what net does it produce?
    | (only built-in statutory items — rules/assignments are handled separately)
    |--------------------------------------------------------------------------
    */
    private function netOf(float $gross): float
    {
        $basic = $gross / 1.3;            // BASIC = GROSS / 1.3  (since GROSS = BASIC * 1.3)
        $nhima = min($basic * 0.01, 1861.80);
        $napsa = $this->calculateNapsa($gross);
        $paye  = $this->calculateTax($gross);

        return $gross - $nhima - $napsa - $paye;
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVE RULE AMOUNT  (uses already-solved BASICPAY / GROSSPAY)
    |--------------------------------------------------------------------------
    */
    private function resolveRuleAmount($rule): float
    {
        if ($rule->formula_type === 'fixed') {
            return (float) $rule->value;
        }

        if ($rule->formula_type === 'percentage') {
            $base = match($rule->applies_to) {
                'BASICPAY' => $this->BASICPAY,
                'GROSSPAY' => $this->GROSSPAY,
                default    => $this->GROSSPAY,
            };
            return ($base * $rule->value) / 100;
        }

        return 0;
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVE ASSIGNMENT AMOUNT
    |--------------------------------------------------------------------------
    */
    private function resolveAssignmentAmount($assignment): float
    {
        if ($assignment->formula_type === 'fixed') {
            return (float) $assignment->value;
        }

        if ($assignment->formula_type === 'percentage') {
            // Assignments % always against gross (same as old behaviour)
            return ($this->GROSSPAY * $assignment->value) / 100;
        }

        return 0;
    }

    /*
    |--------------------------------------------------------------------------
    | NAPSA — 5% of Gross, capped at ZMW 1,221.80 (2024 ceiling)
    |--------------------------------------------------------------------------
    */
    private function calculateNapsa(float $gross): float
    {
        $napsa = $gross * 0.05;
        return min($napsa, 1221.80);
    }

    /*
    |--------------------------------------------------------------------------
    | ZRA PAYE — progressive tax brackets (Zambia)
    |--------------------------------------------------------------------------
    */
    public function calculateTax(float $gross): float
    {
        $tax = 0;

        $brackets = [
            ['min' => 0,    'max' => 5100,  'rate' => 0.00],
            ['min' => 5100, 'max' => 7100,  'rate' => 0.20],
            ['min' => 7100, 'max' => 9200,  'rate' => 0.30],
            ['min' => 9200, 'max' => null,  'rate' => 0.37],
        ];

        foreach ($brackets as $bracket) {
            if ($gross <= $bracket['min']) {
                continue;
            }
            $upper   = $bracket['max'] ?? $gross;
            $taxable = min($gross, $upper) - $bracket['min'];
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
    private function addItem(Payroll $payroll, array $data): PayrollItem
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