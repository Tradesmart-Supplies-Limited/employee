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
    | BUILT-IN ALLOWANCE RATES  (% of Basic Pay)
    |
    |   Housing   = 30.00% of Basic   — PAYE taxable, NAPSA taxable
    |   Lunch     =  5.85% of Basic   — PAYE taxable, NAPSA taxable
    |   Transport =  7.00% of Basic   — PAYE taxable, NAPSA taxable
    |
    |   GROSS_MULT = 1 + 0.30 + 0.0585 + 0.07 = 1.4285
    |   BASIC      = GROSS / 1.4285
    |--------------------------------------------------------------------------
    */
    private const HOUSING_RATE   = 0.30;
    private const LUNCH_RATE     = 0.0585;
    private const TRANSPORT_RATE = 0.07;
    private const GROSS_MULT     = 1 + self::HOUSING_RATE + self::LUNCH_RATE + self::TRANSPORT_RATE;

    /*
    |--------------------------------------------------------------------------
    | STATUTORY RATES  (Zambia — ZRA / NAPSA / NHIS)
    |--------------------------------------------------------------------------
    */
    private const NAPSA_RATE = 0.05;
    private const NAPSA_CAP  = 1861.80;
    private const NHIS_RATE  = 0.01;
    private const NHIS_CAP   = 999999999999999999999;

    /*
    |--------------------------------------------------------------------------
    | TAX PROFILES  (mirrors PayrollRule constants)
    |
    |  taxable      → PAYE ✅  NAPSA ✅   e.g. Overtime, Bonus
    |  napsa_only   → PAYE ❌  NAPSA ✅   e.g. Gratuity, PILON, Ex gratia
    |  non_taxable  → PAYE ❌  NAPSA ❌   e.g. Reimbursements
    |--------------------------------------------------------------------------
    */
    private const PROFILE_TAXABLE     = 'taxable';
    private const PROFILE_NAPSA_ONLY  = 'napsa_only';
    private const PROFILE_NON_TAXABLE = 'non_taxable';

    /*
    |--------------------------------------------------------------------------
    | INTERNAL STATE
    |--------------------------------------------------------------------------
    */
    private float $TARGET_NET      = 0;
    private float $GROSSPAY        = 0;  // Built-in gross (Basic + 3 allowances)
    private float $BASICPAY        = 0;
    private float $PAYE_BASE       = 0;  // Gross + taxable rule earnings
    private float $NAPSA_BASE      = 0;  // Gross + taxable + napsa_only rule earnings

    // =========================================================================
    // PUBLIC ENTRY POINT — controller signature unchanged
    // =========================================================================
    public function build(Payroll $payroll): Payroll
    {
        $employee = $payroll->employee;

        $this->TARGET_NET = (float) ($employee->salary ?? 0);

        if ($this->TARGET_NET <= 0) {
            Log::warning("PayrollEngine: Employee {$employee->id} has zero target net — skipped.");
            return $payroll;
        }

        // ── 1. Load rule sets ────────────────────────────────────────────────
        $globalRules = PayrollRule::where('active', 1)->get();

        $assignments = PayrollAssignment::where('employee_id', $employee->id)
            ->where('active', 1)
            ->get();

        $runAdjustments = PayrollRunAdjustment::where('payroll_run_id', $payroll->payroll_run_id)
            ->where('employee_id', $employee->id)
            ->where('active', 1)
            ->get();

        // ── 2. Solve built-in Gross (bisection accounts for rule earnings too) ─
        $this->GROSSPAY = $this->solveGross($globalRules, $assignments, $runAdjustments);
        $this->BASICPAY = $this->GROSSPAY / self::GROSS_MULT;

        // ── 3. Resolve all rule/assignment earning amounts (post-solve) ────────
        //    Partition by tax profile so we know what to add to each tax base.
        //
        //    taxableRuleEarnings   → added to PAYE base AND NAPSA base
        //    napsaOnlyRuleEarnings → added to NAPSA base only
        //    nonTaxableEarnings    → added to neither base
        //
        $taxableRuleEarnings   = $this->sumRuleEarningsByProfile($globalRules, self::PROFILE_TAXABLE);
        $napsaOnlyRuleEarnings = $this->sumRuleEarningsByProfile($globalRules, self::PROFILE_NAPSA_ONLY);

        // Assignments: treated as 'taxable' unless you add tax_profile there too.
        // For now assignments follow the same taxable treatment as built-ins.
        $assignmentEarnings = $this->sumAssignmentEarnings($assignments);

        // Run adjustments carry a tax_profile column (add via migration if needed).
        // For now we read it if present, defaulting to 'taxable'.
        [$adjTaxable, $adjNapsaOnly, $adjNonTaxable] = $this->partitionAdjustmentEarnings($runAdjustments);

        // ── 4. Build tax bases ───────────────────────────────────────────────
        //
        //    PAYE  base = GROSSPAY + taxable rule earnings + taxable adjustments
        //                          + assignment earnings
        //    NAPSA base = PAYE base + napsa_only rule earnings + napsa_only adjustments
        //
        $this->PAYE_BASE  = $this->GROSSPAY
                          + $taxableRuleEarnings
                          + $assignmentEarnings
                          + $adjTaxable;

        $this->NAPSA_BASE = $this->PAYE_BASE
                          + $napsaOnlyRuleEarnings
                          + $adjNapsaOnly;

        // non_taxable earnings are in gross but affect neither tax base —
        // they simply increase take-home pay directly.

        // ── 5. Calculate statutory deductions on correct bases ───────────────
        $paye  = $this->calculateTax($this->PAYE_BASE);
        $napsa = min($this->NAPSA_BASE * self::NAPSA_RATE, self::NAPSA_CAP);
        $nhis  = min($this->BASICPAY   * self::NHIS_RATE,  self::NHIS_CAP);

        // ── 6. Sum all extra deductions (rules + assignments + adjustments) ───
        $extraDeductions = $this->sumAllDeductions($globalRules, $assignments, $runAdjustments);

        // ── 7. Sum ALL earnings for the payslip total ────────────────────────
        $allRuleEarnings = $taxableRuleEarnings + $napsaOnlyRuleEarnings
                         + $this->sumRuleEarningsByProfile($globalRules, self::PROFILE_NON_TAXABLE);

        $allAdjEarnings  = $adjTaxable + $adjNapsaOnly + $adjNonTaxable;

        // Built-in allowances
        $lunch     = $this->BASICPAY * self::LUNCH_RATE;
        $transport = $this->BASICPAY * self::TRANSPORT_RATE;
        $housing   = $this->BASICPAY * self::HOUSING_RATE;

       

        // ── 8. Penny-adjust Basic so net lands EXACTLY on TARGET_NET ─────────
        $roundedBasic      = round($this->BASICPAY, 2);
        $roundedLunch      = round($lunch, 2);
        $roundedTransport  = round($transport, 2);
        $roundedHousing    = round($housing, 2);
        $roundedPaye       = round($paye, 2);
        $roundedNapsa      = round($napsa, 2);
        $roundedNhis       = round($nhis, 2);
        $roundedExtraD     = round($extraDeductions, 2);
        $roundedRuleE      = round($allRuleEarnings, 2);
        $roundedAdjE       = round($allAdjEarnings, 2);
        $roundedAssignE    = round($assignmentEarnings, 2);

        $roundedEarnings   = $roundedBasic + $roundedLunch + $roundedTransport
                           + $roundedHousing + $roundedRuleE + $roundedAdjE + $roundedAssignE;
        $roundedDeductions = $roundedPaye + $roundedNapsa + $roundedNhis + $roundedExtraD;
        $roundedNet        = $roundedEarnings - $roundedDeductions;

         Log::info('PAYROLL DEBUG', [
            'target_net' => $this->TARGET_NET,
            'rounded_net' => $roundedNet,
            'difference' => $this->TARGET_NET - $roundedNet,
            'adjustment_earnings' => $roundedAdjE,
        ]);

        // $pennyDiff     = round($this->TARGET_NET - $roundedNet, 2);
        // $adjustedBasic = $roundedBasic + $pennyDiff;

        $variance = abs($this->TARGET_NET - $roundedNet);

        // Only correct tiny rounding errors (NOT real payroll changes)
        if ($variance <= 0.05) {

            $pennyDiff = round($this->TARGET_NET - $roundedNet, 2);
            $adjustedBasic = $roundedBasic + $pennyDiff;

        } else {

            // Real payroll difference (overtime, bonus, allowances, etc.)
            $pennyDiff = 0;
            $adjustedBasic = $roundedBasic;
        }

        $finalEarnings   = $adjustedBasic + $roundedLunch + $roundedTransport
                         + $roundedHousing + $roundedRuleE + $roundedAdjE + $roundedAssignE;
        $finalDeductions = $roundedPaye + $roundedNapsa + $roundedNhis + $roundedExtraD;
        $finalNet        = $finalEarnings - $finalDeductions;

        // ── 9. Wipe stale items ───────────────────────────────────────────────
        $payroll->items()->delete();

        // =====================================================================
        // WRITE EARNINGS
        // =====================================================================
        $this->addItem($payroll, 'P00', 'Basic Pay',           'earning', $adjustedBasic);
        $this->addItem($payroll, '010', 'Lunch Allowance',     'earning', $roundedLunch);
        $this->addItem($payroll, '023', 'Transport Allowance', 'earning', $roundedTransport);
        $this->addItem($payroll, 'P06', 'Housing Allowance',   'earning', $roundedHousing);

        // Global rule earnings — grouped and labelled by tax profile
        foreach ($globalRules->where('type', 'earning') as $rule) {
            $amount = round($this->resolveRuleAmount($rule), 2);
            $this->addItem($payroll, strtoupper($rule->name), $rule->name, 'earning', $amount);
        }

        // Assignment earnings
        foreach ($assignments->where('type', 'earning') as $item) {
            $amount = round($this->resolveAssignmentAmount($item), 2);
            $this->addItem($payroll, strtoupper($item->name), $item->name, 'earning', $amount);
        }

        // Run adjustment earnings
        foreach ($runAdjustments->where('type', 'earning') as $adj) {
            $amount = round((float) ($adj->value ?? 0), 2);
            $this->addItem($payroll, strtoupper($adj->name), $adj->name, 'earning', $amount);
        }

        // =====================================================================
        // WRITE DEDUCTIONS
        // =====================================================================
        $statutory = ['PAYE', 'NAPSA', 'NHIS', 'D00', 'D02', 'D11'];

        $this->addItem($payroll, 'D00', 'P.A.Y.E (Income Tax)', 'deduction', $roundedPaye);
        $this->addItem($payroll, 'D02', 'NAPSA Contr',          'deduction', $roundedNapsa);
        $this->addItem($payroll, 'D11', 'NHIS Contr',           'deduction', $roundedNhis);

        foreach ($globalRules->where('type', 'deduction') as $rule) {
            if (in_array(strtoupper($rule->name), $statutory)) continue;
            $this->addItem($payroll, strtoupper($rule->name), $rule->name, 'deduction',
                round($this->resolveRuleAmount($rule), 2));
        }
        foreach ($assignments->where('type', 'deduction') as $item) {
            $this->addItem($payroll, strtoupper($item->name), $item->name, 'deduction',
                round($this->resolveAssignmentAmount($item), 2));
        }
        foreach ($runAdjustments->where('type', 'deduction') as $adj) {
            $this->addItem($payroll, strtoupper($adj->name), $adj->name, 'deduction',
                round((float) ($adj->value ?? 0), 2));
        }

        // =====================================================================
        // SAVE TOTALS
        // =====================================================================
        Log::info('PayrollEngine: build complete', [
            'employee_id'         => $employee->id,
            'target_net'          => $this->TARGET_NET,
            'gross_pay'           => round($this->GROSSPAY, 2),
            'basic_pay'           => $adjustedBasic,
            'paye_base'           => round($this->PAYE_BASE, 2),
            'napsa_base'          => round($this->NAPSA_BASE, 2),
            'penny_adjustment'    => $pennyDiff,
            'total_earnings'      => $finalEarnings,
            'total_deductions'    => $finalDeductions,
            'computed_net'        => $finalNet,
            'variance'            => abs($finalNet - $this->TARGET_NET),
        ]);

        $payroll->update([
            'total_income'     => $finalEarnings,
            'total_deductions' => $finalDeductions,
            'net_pay'          => $finalNet,
        ]);

        return $payroll;
    }

    // =========================================================================
    // SOLVE GROSS — bisection on BASE SALARY ONLY
    // =========================================================================
    /*
    | KEY DESIGN RULE:
    |   employee->salary = what the employee nets from their BASE package alone
    |                      (Basic + Housing + Lunch + Transport)
    |
    |   Adjustments (Overtime, Gratuity, Bonus etc.) are ADDITIONAL earnings
    |   on top of the base salary. They increase net pay beyond TARGET_NET.
    |   They are never used to replace Basic Pay.
    |
    | This means solveGross() ignores adjustments entirely — it only finds
    | the built-in gross that produces TARGET_NET by itself.
    |
    | The tax impact of adjustments is calculated separately in build()
    | AFTER the base gross is solved, using incremental tax differences.
    */
    private function solveGross($globalRules, $assignments, $runAdjustments): float
    {
        // Only fixed deductions that come from global rules or assignments
        // affect the base net. Run adjustment deductions are also additional,
        // but we keep them in the base solve so the payslip balances correctly.
        $fixedDeductions = 0;
        $fixedDeductions += $globalRules->where('type', 'deduction')
                                ->where('formula_type', 'fixed')->sum('value');
        $fixedDeductions += $assignments->where('type', 'deduction')
                                ->where('formula_type', 'fixed')->sum('value');
        $fixedDeductions += $runAdjustments->where('type', 'deduction')->sum('value');

        // Adjust the target to account for fixed deductions (they reduce net)
        $adjustedTarget = $this->TARGET_NET + $fixedDeductions;

        // Bisect: find built-in gross where netOf(gross) === adjustedTarget
        $low = $adjustedTarget;
        $high = $adjustedTarget * 3;
        $mid  = $adjustedTarget;

        for ($i = 0; $i < 60; $i++) {
            $mid    = ($low + $high) / 2;
            $netMid = $this->netOf($mid);

            if (abs($netMid - $adjustedTarget) < 0.001) {
                break;
            }

            $netMid < $adjustedTarget ? $low = $mid : $high = $mid;
        }

        return $mid;
    }

    /*
    | netOf: net produced by the BASE built-in gross alone (no adjustments).
    | Statutory deductions are computed on the base gross only here.
    | Adjustment tax impact is handled incrementally in build().
    */
    private function netOf(float $gross): float
    {
        $basic = $gross / self::GROSS_MULT;
        $nhis  = min($basic * self::NHIS_RATE,  self::NHIS_CAP);
        $napsa = min($gross * self::NAPSA_RATE, self::NAPSA_CAP);
        $paye  = $this->calculateTax($gross);

        return $gross - $nhis - $napsa - $paye;
    }

    // =========================================================================
    // TAX BASE HELPERS — called after GROSSPAY/BASICPAY are known
    // =========================================================================

    private function sumRuleEarningsByProfile($globalRules, string $profile): float
    {
        $total = 0;
        foreach ($globalRules->where('type', 'earning')->where('tax_profile', $profile) as $rule) {
            $total += $this->resolveRuleAmount($rule);
        }
        return $total;
    }

    private function sumAssignmentEarnings($assignments): float
    {
        $total = 0;
        foreach ($assignments->where('type', 'earning') as $item) {
            $total += $this->resolveAssignmentAmount($item);
        }
        return $total;
    }

    /*
    | Returns [taxableTotal, napsaOnlyTotal, nonTaxableTotal]
    |
    | Uses the model helpers isPAYEable(), isNAPSAable(), isFullyExempt()
    | so routing logic lives in the model, not spread across the engine.
    |
    |   tax_profile    PAYE base   NAPSA base
    |   taxable           +            +       e.g. Overtime, Acting allowance
    |   napsa_only        -            +       e.g. Gratuity, PILON, Ex gratia
    |   non_taxable       -            -       e.g. Reimbursements
    */
    private function partitionAdjustmentEarnings($runAdjustments): array
    {
        $taxable   = 0;
        $napsaOnly = 0;
        $nonTax    = 0;

        foreach ($runAdjustments->where('type', 'earning') as $adj) {
            $amount = (float) ($adj->value ?? 0);

            if ($adj->isPAYEable()) {
                $taxable += $amount;          // PAYE + NAPSA
            } elseif ($adj->isNAPSAable()) {
                $napsaOnly += $amount;        // NAPSA only
            } else {
                $nonTax += $amount;           // fully exempt
            }
        }

        return [$taxable, $napsaOnly, $nonTax];
    }

    private function sumAllDeductions($globalRules, $assignments, $runAdjustments): float
    {
        $statutory = ['PAYE', 'NAPSA', 'NHIS', 'D00', 'D02', 'D11'];
        $total = 0;

        foreach ($globalRules->where('type', 'deduction') as $rule) {
            if (in_array(strtoupper($rule->name), $statutory)) continue;
            $total += $this->resolveRuleAmount($rule);
        }
        foreach ($assignments->where('type', 'deduction') as $item) {
            $total += $this->resolveAssignmentAmount($item);
        }
        foreach ($runAdjustments->where('type', 'deduction') as $adj) {
            $total += (float) ($adj->value ?? 0);
        }

        return $total;
    }

    // =========================================================================
    // RESOLVE RULE / ASSIGNMENT AMOUNTS
    // =========================================================================
    private function resolveRuleAmount(PayrollRule $rule): float
    {
        if ($rule->formula_type === 'fixed') {
            return (float) $rule->value;
        }

        if ($rule->formula_type === 'percentage') {
            $base = match ($rule->applies_to) {
                'BASICPAY' => $this->BASICPAY,
                'GROSSPAY' => $this->GROSSPAY,
                default    => $this->GROSSPAY,
            };
            return ($base * $rule->value) / 100;
        }

        return 0;
    }

    private function resolveAssignmentAmount(PayrollAssignment $item): float
    {
        if ($item->formula_type === 'fixed') {
            return (float) $item->value;
        }

        if ($item->formula_type === 'percentage') {
            return ($this->GROSSPAY * $item->value) / 100;
        }

        return 0;
    }

    // =========================================================================
    // ZRA PAYE — progressive brackets
    // =========================================================================
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
            if ($gross <= $bracket['min']) continue;

            $upper   = $bracket['max'] ?? $gross;
            $taxable = min($gross, $upper) - $bracket['min'];

            if ($taxable > 0) {
                $tax += $taxable * $bracket['rate'];
            }
        }

        return $tax;
    }

    // =========================================================================
    // ADD PAYROLL ITEM
    // =========================================================================
    private function addItem(
        Payroll $payroll,
        string  $code,
        string  $description,
        string  $type,
        float   $amount
    ): PayrollItem {
        return PayrollItem::create([
            'payroll_id'  => $payroll->id,
            'code'        => $code,
            'description' => $description,
            'type'        => $type,
            'amount'      => $amount,
        ]);
    }
}