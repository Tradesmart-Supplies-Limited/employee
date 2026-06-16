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
    | Source: verified against live payslip (September 2025)
    |
    |   Housing   = 30.00% of Basic
    |   Lunch     =  5.85% of Basic
    |   Transport =  7.00% of Basic
    |
    | GROSS_MULTIPLIER derives from these rates automatically:
    |   GROSS = BASIC × (1 + 0.30 + 0.0585 + 0.07)  =  BASIC × 1.4285
    |   BASIC = GROSS / 1.4285
    |--------------------------------------------------------------------------
    */
    private const HOUSING_RATE   = 0.30;
    private const LUNCH_RATE     = 0.0585;
    private const TRANSPORT_RATE = 0.07;
    private const GROSS_MULT     = 1 + self::HOUSING_RATE + self::LUNCH_RATE + self::TRANSPORT_RATE;
    // = 1.4285

    /*
    |--------------------------------------------------------------------------
    | STATUTORY DEDUCTION RULES  (Zambia — ZRA / NAPSA / NHIS)
    |
    |   NAPSA  = 5%  of Gross,  capped at K 1,221.80 / month
    |   NHIS   = 1%  of Basic,  capped at K 1,861.80 / month
    |   PAYE   = ZRA progressive brackets (on Gross)
    |--------------------------------------------------------------------------
    */
    private const NAPSA_RATE = 0.05;
    private const NAPSA_CAP  = 1221.80;
    private const NHIS_RATE  = 0.01;
    private const NHIS_CAP   = 1861.80;

    /*
    |--------------------------------------------------------------------------
    | INTERNAL STATE  — resolved in build(), shared across helpers
    |--------------------------------------------------------------------------
    */
    private float $TARGET_NET = 0;   // employee->salary  (what they take home)
    private float $GROSSPAY   = 0;   // solved by bisection
    private float $BASICPAY   = 0;   // GROSSPAY / GROSS_MULT

    // =========================================================================
    // PUBLIC ENTRY POINT
    // Controller calls:  $engine->build($payroll)
    // Signature is unchanged — controller needs zero edits.
    // =========================================================================
    public function build(Payroll $payroll): Payroll
    {
        $employee = $payroll->employee;

        $this->TARGET_NET = (float) ($employee->salary ?? 0);

        if ($this->TARGET_NET <= 0) {
            Log::warning("PayrollEngine: Employee {$employee->id} has zero target net — skipped.");
            return $payroll;
        }

        // ── 1. Load all rule sets ─────────────────────────────────────────────
        $globalRules = PayrollRule::where('active', 1)->get();

        $assignments = PayrollAssignment::where('employee_id', $employee->id)
            ->where('active', 1)
            ->get();

        $runAdjustments = PayrollRunAdjustment::where('payroll_run_id', $payroll->payroll_run_id)
            ->where('employee_id', $employee->id)
            ->where('active', 1)
            ->get();

        // ── 2. Solve Gross from Target Net (bisection) ────────────────────────
        //    Do NOT round here — keep full float precision for all downstream math.
        $this->GROSSPAY = $this->solveGross($globalRules, $assignments, $runAdjustments);

        // ── 3. Derive Basic in full precision ────────────────────────────────
        $this->BASICPAY = $this->GROSSPAY / self::GROSS_MULT;

        // ── 4. Pre-calculate all deductions in full precision ─────────────────
        //    Must be done BEFORE writing any items so the penny-adjustment
        //    to Basic Pay can be computed first.
        $paye  = $this->calculateTax($this->GROSSPAY);   // unrounded internally too
        $napsa = min($this->GROSSPAY * self::NAPSA_RATE, self::NAPSA_CAP);
        $nhis  = min($this->BASICPAY * self::NHIS_RATE,  self::NHIS_CAP);

        // Allowances in full precision
        $lunch     = $this->BASICPAY * self::LUNCH_RATE;
        $transport = $this->BASICPAY * self::TRANSPORT_RATE;
        $housing   = $this->BASICPAY * self::HOUSING_RATE;

        // Extra rule / assignment / adjustment amounts (full precision)
        $extraEarnings   = $this->sumExtras($globalRules, $assignments, $runAdjustments, 'earning');
        $extraDeductions = $this->sumExtras($globalRules, $assignments, $runAdjustments, 'deduction');

        // ── 5. Penny-adjust Basic Pay so net lands exactly on TARGET_NET ──────
        //
        //    After rounding every line to 2dp the display total can drift by a
        //    few cents.  We calculate what the rounded lines will sum to, then
        //    absorb the difference into Basic Pay — which is the only line that
        //    is a "residual" by design.
        //
        //    rounded net (without adjustment) = roundedEarnings - roundedDeductions
        //    adjustment = TARGET_NET - that rounded net
        //    new Basic  = Basic + adjustment
        //
        $roundedBasic     = round($this->BASICPAY, 2);
        $roundedLunch     = round($lunch, 2);
        $roundedTransport = round($transport, 2);
        $roundedHousing   = round($housing, 2);
        $roundedPaye      = round($paye, 2);
        $roundedNapsa     = round($napsa, 2);
        $roundedNhis      = round($nhis, 2);
        $roundedExtraE    = round($extraEarnings, 2);
        $roundedExtraD    = round($extraDeductions, 2);

        $roundedEarnings   = $roundedBasic + $roundedLunch + $roundedTransport
                           + $roundedHousing + $roundedExtraE;
        $roundedDeductions = $roundedPaye + $roundedNapsa + $roundedNhis + $roundedExtraD;
        $roundedNet        = $roundedEarnings - $roundedDeductions;

        // Absorb penny difference into Basic Pay
        $pennyDiff    = round($this->TARGET_NET - $roundedNet, 2);
        $adjustedBasic = $roundedBasic + $pennyDiff;   // e.g. 2375.52 → 2375.54

        // Recalculate earnings total with the adjusted Basic
        $finalEarnings   = $adjustedBasic + $roundedLunch + $roundedTransport
                         + $roundedHousing + $roundedExtraE;
        $finalDeductions = $roundedPaye + $roundedNapsa + $roundedNhis + $roundedExtraD;
        $finalNet        = $finalEarnings - $finalDeductions;

        // ── 6. Wipe old items — clean slate for regeneration ──────────────────
        $payroll->items()->delete();

        // =====================================================================
        // WRITE EARNINGS  (all values already rounded above)
        // =====================================================================
        $this->addItem($payroll, 'P00',  'Basic Pay',           'earning', $adjustedBasic);
        $this->addItem($payroll, '010',  'Lunch Allowance',     'earning', $roundedLunch);
        $this->addItem($payroll, '023',  'Transport Allowance', 'earning', $roundedTransport);
        $this->addItem($payroll, 'P06',  'Housing Allowance',   'earning', $roundedHousing);

        // Extra rule earnings
        $statutory = ['PAYE', 'NAPSA', 'NHIS', 'D00', 'D02', 'D11'];

        foreach ($globalRules->where('type', 'earning') as $rule) {
            $this->addItem($payroll, strtoupper($rule->name), $rule->name, 'earning',
                round($this->resolveRuleAmount($rule), 2));
        }
        foreach ($assignments->where('type', 'earning') as $item) {
            $this->addItem($payroll, strtoupper($item->name), $item->name, 'earning',
                round($this->resolveAssignmentAmount($item), 2));
        }
        foreach ($runAdjustments->where('type', 'earning') as $adj) {
            $this->addItem($payroll, strtoupper($adj->name), $adj->name, 'earning',
                round((float) ($adj->value ?? 0), 2));
        }

        // =====================================================================
        // WRITE DEDUCTIONS  (all values already rounded above)
        // =====================================================================
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
            'employee_id'      => $employee->id,
            'target_net'       => $this->TARGET_NET,
            'gross_pay'        => round($this->GROSSPAY, 2),
            'basic_pay'        => $adjustedBasic,
            'penny_adjustment' => $pennyDiff,
            'total_earnings'   => $finalEarnings,
            'total_deductions' => $finalDeductions,
            'computed_net'     => $finalNet,
            'variance'         => abs($finalNet - $this->TARGET_NET),
        ]);

        $payroll->update([
            'total_income'     => $finalEarnings,
            'total_deductions' => $finalDeductions,
            'net_pay'          => $finalNet,
        ]);

        return $payroll;
    }

    // =========================================================================
    // SOLVE GROSS FROM TARGET NET  (bisection)
    // =========================================================================
    /*
    | Why bisection?
    |   PAYE has progressive brackets and NAPSA has a cap — both are non-linear,
    |   so there is no single closed-form formula. Bisection converges to within
    |   K 0.001 in ~50 iterations (microseconds).
    |
    | How it works:
    |   netOf(gross) = gross - PAYE(gross) - NAPSA(gross) - NHIS(gross/MULT)
    |                        - fixedDeductions + fixedEarnings
    |   We find the gross where netOf(gross) === TARGET_NET.
    |
    | Fixed deductions/earnings from rules shift the target before solving,
    | because they don't affect the gross↔net relationship.
    */
    private function solveGross($globalRules, $assignments, $runAdjustments): float
    {
        // Fixed deductions that reduce net regardless of gross
        $fixedDeductions = 0;
        $fixedDeductions += $globalRules
            ->where('type', 'deduction')
            ->where('formula_type', 'fixed')
            ->sum('value');
        $fixedDeductions += $assignments
            ->where('type', 'deduction')
            ->where('formula_type', 'fixed')
            ->sum('value');
        $fixedDeductions += $runAdjustments
            ->where('type', 'deduction')
            ->sum('value');

        // Fixed earnings that boost net regardless of gross
        $fixedEarnings = $runAdjustments
            ->where('type', 'earning')
            ->sum('value');

        // Adjusted target: what statutory deductions must account for
        $adjustedTarget = $this->TARGET_NET + $fixedDeductions - $fixedEarnings;

        // Bisection bounds
        $low  = $adjustedTarget;        // gross cannot be less than net
        $high = $adjustedTarget * 3;    // generous ceiling

        $mid = $adjustedTarget;

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

        return $mid;   // full float precision — rounded only at display/persistence
    }

    /*
    | netOf: the net a given gross produces (statutory items only)
    | Used exclusively by solveGross() during bisection.
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
    // RESOLVE RULE / ASSIGNMENT AMOUNTS  (post-solve, uses state vars)
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
    // ZRA PAYE — PROGRESSIVE TAX BRACKETS (Zambia)
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

        return $tax;   // full float precision — caller rounds at persistence
    }

    // =========================================================================
    // SUM EXTRA EARNINGS OR DEDUCTIONS FROM RULES / ASSIGNMENTS / ADJUSTMENTS
    // (excludes built-in statutory items handled explicitly in build())
    // =========================================================================
    private function sumExtras($globalRules, $assignments, $runAdjustments, string $type): float
    {
        $statutory = ['PAYE', 'NAPSA', 'NHIS', 'D00', 'D02', 'D11'];
        $total = 0;

        foreach ($globalRules->where('type', $type) as $rule) {
            if (in_array(strtoupper($rule->name), $statutory)) continue;
            $total += $this->resolveRuleAmount($rule);
        }
        foreach ($assignments->where('type', $type) as $item) {
            $total += $this->resolveAssignmentAmount($item);
        }
        foreach ($runAdjustments->where('type', $type) as $adj) {
            $total += (float) ($adj->value ?? 0);
        }

        return $total;
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
            'amount'      => round($amount, 2),
        ]);
    }
}