<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollRule;
use App\Models\PayrollAssignment;
use App\Models\PayrollRunAdjustment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * PayrollEngine — fully rule-driven
 *
 * All payroll logic (allowance rates, statutory rates, caps, gross multiplier)
 * is sourced from the payroll_rules table at runtime.
 *
 * Penny-adjustment strategy
 * ─────────────────────────
 * The old approach mutated $basic after allowances were already computed as
 * percentages of it. Reducing basic by K 0.01 also shrank housing/lunch/
 * transport proportionally, so the net correction overshot and produced a
 * K 0.01 residual in the opposite direction.
 *
 * Fix: basic and all allowances are frozen after rounding. Any sub-K-0.05
 * residual between computedNet and targetNet is absorbed by a standalone
 * penny_adjustment item that carries no tax or allowance side-effects.
 */
class PayrollEngine
{
    // ── Internal state ────────────────────────────────────────────────────────
    private float $targetNet = 0;
    private float $grossPay  = 0;
    private float $basicPay  = 0;
    private float $grossMult = 0;
    private float $napsaCap  = 0;

    private Collection $allRules;
    private Collection $earningRules;
    private Collection $deductionRules;
    private Collection $systemRules;

    // =========================================================================
    // PUBLIC ENTRY POINT
    // =========================================================================
    public function build(Payroll $payroll): Payroll
    {
        $employee        = $payroll->employee;
        $this->targetNet = (float) ($employee->salary ?? 0);

        if ($this->targetNet <= 0) {
            Log::warning("PayrollEngine: Employee {$employee->id} has zero target net — skipped.");
            return $payroll;
        }

        // 1. Load rules
        $this->loadRules();

        // 2. Load per-employee data
        $assignments    = $this->loadAssignments($employee->id);
        $runAdjustments = $this->loadAdjustments($payroll->payroll_run_id, $employee->id);

        // 3. Gross multiplier + NAPSA cap
        $this->grossMult = $this->computeGrossMultiplier();
        $this->napsaCap  = $this->loadNapsaCap();

        // 4. Solve built-in gross via bisection
        $this->grossPay = $this->solveGross($assignments);
        $this->basicPay = $this->grossMult > 0
            ? $this->grossPay / $this->grossMult
            : $this->grossPay;

        // 5. Partition earnings by tax profile
        [$rulesTaxable, $rulesNapsaOnly, $rulesNonTaxable] = $this->partitionRuleEarnings();
        $assignmentEarnings = $this->sumAssignmentEarnings($assignments);
        [$adjTaxable, $adjNapsaOnly, $adjNonTaxable]       = $this->partitionAdjustmentEarnings($runAdjustments);

        // 6. Build tax bases
        $payeBase  = $this->grossPay + $rulesTaxable  + $assignmentEarnings + $adjTaxable;
        $napsaBase = $payeBase       + $rulesNapsaOnly + $adjNapsaOnly;

        // 7. Statutory deductions
        $paye  = $this->calculatePaye($payeBase);
        $napsa = $this->calculateStatutoryDeduction('pension', $napsaBase, $this->napsaCap);
        $nhima = $this->calculateStatutoryDeduction('health',  $this->basicPay);

        // 8. Non-statutory deductions
        $extraDeductions = $this->sumNonStatutoryDeductions($assignments, $runAdjustments);

        // 9. Round all values — basic and allowances are NOW FROZEN
        $r = $this->roundAll([
            'basic'  => $this->basicPay,
            'paye'   => $paye,
            'napsa'  => $napsa,
            'nhima'  => $nhima,
            'extraD' => $extraDeductions,
            'ruleE'  => $rulesTaxable + $rulesNapsaOnly + $rulesNonTaxable,
            'adjE'   => $adjTaxable   + $adjNapsaOnly   + $adjNonTaxable,
            'assignE'=> $assignmentEarnings,
        ]);

        // Allowances are computed ONCE from the frozen rounded basic
        $allowanceTotals = $this->resolveAllowanceTotals($r['basic']);
        $grossBuiltIn    = $r['basic'] + array_sum($allowanceTotals);

        $totalEarnings   = $grossBuiltIn + $r['ruleE'] + $r['adjE'] + $r['assignE'];
        $totalDeductions = $r['paye'] + $r['napsa'] + $r['nhima'] + $r['extraD'];
        $computedNet     = round($totalEarnings - $totalDeductions, 2);

        // 10. Penny correction — applied as a standalone adjustment,
        //     NOT by mutating basic (which would cascade into allowances).
        $variance        = round($this->targetNet - $computedNet, 2);
        $pennyAdjustment = 0.0;

        if ($variance !== 0.0 && abs($variance) <= 0.05) {
            $pennyAdjustment = $variance;   // e.g. +0.01 or -0.01
            $totalEarnings   = round($totalEarnings + $pennyAdjustment, 2);
            $computedNet     = round($totalEarnings - $totalDeductions, 2);
        }

        $finalNet = $computedNet;

        Log::debug('PayrollEngine: penny-correction', [
            'employee_id'     => $employee->id,
            'target_net'      => $this->targetNet,
            'computed_net_pre'=> round($grossBuiltIn + $r['ruleE'] + $r['adjE'] + $r['assignE'] - $totalDeductions, 2),
            'penny_adjustment'=> $pennyAdjustment,
            'final_net'       => $finalNet,
            'variance'        => abs($finalNet - $this->targetNet),
        ]);

        // 11. Wipe stale items
        $payroll->items()->delete();

        // 12. Write earnings
        $this->writeBuiltInEarnings($payroll, $r['basic'], $allowanceTotals);
        $this->writeRuleEarnings($payroll);
        $this->writeAssignmentEarnings($payroll, $assignments);
        $this->writeAdjustmentEarnings($payroll, $runAdjustments);

        // Write penny adjustment as a hidden rounding item (show_on_payslip = false)
        // if ($pennyAdjustment !== 0.0) {
            // $this->addItem($payroll, 'SYS_PENNY', 'Rounding Adjustment', 'earning', $pennyAdjustment);
        // }

        // 13. Write deductions
        $this->writeStatutoryDeductions($payroll, $r['paye'], $r['napsa'], $r['nhima']);
        $this->writeNonStatutoryDeductions($payroll, $assignments, $runAdjustments);

        // 14. Persist totals
        Log::info('PayrollEngine: build complete', [
            'employee_id'      => $employee->id,
            'target_net'       => $this->targetNet,
            'gross_pay'        => round($this->grossPay, 2),
            'basic_pay'        => $r['basic'],
            'paye_base'        => round($payeBase, 2),
            'napsa_base'       => round($napsaBase, 2),
            'total_earnings'   => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'net_pay'          => $finalNet,
            'variance'         => abs($finalNet - $this->targetNet),
        ]);

        $payroll->update([
            'total_income'     => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'net_pay'          => $finalNet,
        ]);

        return $payroll;
    }

    // =========================================================================
    // RULE LOADING
    // =========================================================================

    private function loadRules(): void
    {
        $this->allRules = PayrollRule::where('requires_assignment', '!=', true)->get();

        $this->earningRules   = $this->allRules->where('type', 'earning')->where('active', true);
        $this->deductionRules = $this->allRules->where('type', 'deduction')->where('active', true);
        $this->systemRules    = $this->allRules->where('type', 'system');
    }

    private function loadAssignments(int $employeeId): Collection
    {
        return PayrollAssignment::where('employee_id', $employeeId)->where('active', 1)->get();
    }

    private function loadAdjustments(int $runId, int $employeeId): Collection
    {
        return PayrollRunAdjustment::where('payroll_run_id', $runId)
            ->where('employee_id', $employeeId)
            ->where('active', 1)
            ->get();
    }

    // =========================================================================
    // GROSS MULTIPLIER
    // =========================================================================

    private function computeGrossMultiplier(): float
    {
        $pctSum = $this->earningRules
            ->where('affects_gross', true)
            ->where('formula_type', 'percentage')
            ->where('applies_to', 'BASICPAY')
            ->where('category', 'allowance')
            ->sum('value');

        return 1 + ($pctSum / 100);
    }

    private function loadNapsaCap(): float
    {
        $cap = $this->systemRules->where('code', 'SYS01')->first();
        return $cap ? (float) $cap->value : PHP_FLOAT_MAX;
    }

    /**
     * Compute allowance line amounts from a given basic.
     * Called ONCE after rounding — never called again to avoid cascade drift.
     *
     * @return array<string, float>  [ ruleCode => roundedAmount ]
     */
    private function resolveAllowanceTotals(float $basic): array
    {
        $totals = [];
        foreach (
            $this->earningRules
                ->where('affects_gross', true)
                ->where('category', 'allowance')
            as $rule
        ) {
            if ($rule->formula_type === 'percentage' && $rule->applies_to === 'BASICPAY') {
                $totals[$rule->code] = round($basic * ($rule->value / 100), 2);
            }
        }
        return $totals;
    }

    // =========================================================================
    // BISECTION — solve Gross from target net
    // =========================================================================

    private function solveGross(Collection $assignments): float
    {
        $fixedDeductions = $assignments
            ->where('type', 'deduction')
            ->where('formula_type', 'fixed')
            ->sum('value');

        $adjustedTarget = $this->targetNet + $fixedDeductions;

        $low = $adjustedTarget;
        $high = $adjustedTarget * 3;
        $mid  = $adjustedTarget;

        for ($i = 0; $i < 60; $i++) {
            $mid    = ($low + $high) / 2;
            $netMid = $this->netOfGross($mid);

            if (abs($netMid - $adjustedTarget) < 0.0001) {
                break;
            }

            $netMid < $adjustedTarget ? $low = $mid : $high = $mid;
        }

        return $mid;
    }

    /**
     * Net produced by a gross value using statutory rules only.
     * Used exclusively during bisection — no side-effects.
     */
    private function netOfGross(float $gross): float
    {
        $basic = $this->grossMult > 0 ? $gross / $this->grossMult : $gross;
        $napsa = $this->calculateStatutoryDeduction('pension', $gross, $this->napsaCap);
        $nhima = $this->calculateStatutoryDeduction('health',  $basic);
        $paye  = $this->calculatePaye($gross);

        return $gross - $napsa - $nhima - $paye;
    }

    // =========================================================================
    // STATUTORY DEDUCTION RESOLVER
    // =========================================================================

    private function calculateStatutoryDeduction(
        string $category,
        float  $base,
        float  $cap = PHP_FLOAT_MAX
    ): float {
        $rule = $this->deductionRules
            ->where('is_statutory', true)
            ->where('category', $category)
            ->first();

        if (! $rule) {
            return 0;
        }

        return $rule->formula_type === 'percentage'
            ? min($base * ($rule->value / 100), $cap)
            : min((float) $rule->value, $cap);
    }

    // =========================================================================
    // EARNINGS PARTITIONING
    // =========================================================================

    private function partitionRuleEarnings(): array
    {
        $taxable = $napsaOnly = $nonTax = 0;

        $additionalEarnings = $this->earningRules
            ->where('affects_gross', false)
            ->where('category', '!=', 'basic');

        foreach ($additionalEarnings as $rule) {
            $amount = $this->resolveRuleAmount($rule);
            match ($rule->tax_profile) {
                'taxable'    => $taxable   += $amount,
                'napsa_only' => $napsaOnly += $amount,
                default      => $nonTax    += $amount,
            };
        }

        return [$taxable, $napsaOnly, $nonTax];
    }

    private function sumAssignmentEarnings(Collection $assignments): float
    {
        $total = 0;
        foreach ($assignments->where('type', 'earning') as $item) {
            $total += $this->resolveAssignmentAmount($item);
        }
        return $total;
    }

    private function partitionAdjustmentEarnings(Collection $adjustments): array
    {
        $taxable = $napsaOnly = $nonTax = 0;

        foreach ($adjustments->where('type', 'earning') as $adj) {
            $amount = (float) ($adj->value ?? 0);
            if ($adj->isPAYEable()) {
                $taxable   += $amount;
            } elseif ($adj->isNAPSAable()) {
                $napsaOnly += $amount;
            } else {
                $nonTax    += $amount;
            }
        }

        return [$taxable, $napsaOnly, $nonTax];
    }

    // =========================================================================
    // DEDUCTION HELPERS
    // =========================================================================

    private function sumNonStatutoryDeductions(
        Collection $assignments,
        Collection $adjustments
    ): float {
        $total = 0;

        foreach ($this->deductionRules->where('is_statutory', false) as $rule) {
            $total += $this->resolveRuleAmount($rule);
        }
        foreach ($assignments->where('type', 'deduction') as $item) {
            $total += $this->resolveAssignmentAmount($item);
        }
        foreach ($adjustments->where('type', 'deduction') as $adj) {
            $total += (float) ($adj->value ?? 0);
        }

        return $total;
    }

    // =========================================================================
    // WRITE HELPERS
    // =========================================================================

    private function writeBuiltInEarnings(Payroll $payroll, float $basic, array $allowanceTotals): void
    {
        $basicRule = $this->earningRules->where('category', 'basic')->first();
        $this->addItem($payroll, $basicRule?->code ?? 'P00', $basicRule?->name ?? 'Basic Pay', 'earning', $basic);

        foreach ($this->earningRules->where('affects_gross', true)->where('category', 'allowance') as $rule) {
            $this->addItem($payroll, $rule->code, $rule->name, 'earning', $allowanceTotals[$rule->code] ?? 0);
        }
    }

    private function writeRuleEarnings(Payroll $payroll): void
    {
        foreach (
            $this->earningRules->where('affects_gross', false)->where('category', '!=', 'basic')
            as $rule
        ) {
            $this->addItem($payroll, $rule->code, $rule->name, 'earning', round($this->resolveRuleAmount($rule), 2));
        }
    }

    private function writeAssignmentEarnings(Payroll $payroll, Collection $assignments): void
    {
        foreach ($assignments->where('type', 'earning') as $item) {
            $this->addItem($payroll, strtoupper($item->name), $item->name, 'earning',
                round($this->resolveAssignmentAmount($item), 2));
        }
    }

    private function writeAdjustmentEarnings(Payroll $payroll, Collection $adjustments): void
    {
        foreach ($adjustments->where('type', 'earning') as $adj) {

            // log all fields for $adj
            try {
                \Log::info('adj: ' . json_encode($adj));
            } catch (\Throwable $e) {
                // fallback to print_r if json_encode fails
                \Log::info('adj: ' . print_r($adj, true));
            }

            $rule = PayrollRule::where('id', $adj->payroll_rule_id)->first();


            $this->addItem($payroll, $rule->code, $adj->name, 'earning',
                round((float) ($adj->value ?? 0), 2));
        }
    }

    private function writeStatutoryDeductions(Payroll $payroll, float $paye, float $napsa, float $nhima): void
    {
        $amounts = ['tax' => $paye, 'pension' => $napsa, 'health' => $nhima];

        foreach ($this->deductionRules->where('is_statutory', true) as $rule) {
            $this->addItem($payroll, $rule->code, $rule->name, 'deduction', $amounts[$rule->category] ?? 0);
        }
    }

    private function writeNonStatutoryDeductions(
        Payroll    $payroll,
        Collection $assignments,
        Collection $adjustments
    ): void {
        foreach ($this->deductionRules->where('is_statutory', false) as $rule) {
            $this->addItem($payroll, $rule->code, $rule->name, 'deduction',
                round($this->resolveRuleAmount($rule), 2));
        }
        foreach ($assignments->where('type', 'deduction') as $item) {
            $this->addItem($payroll, strtoupper($item->name), $item->name, 'deduction',
                round($this->resolveAssignmentAmount($item), 2));
        }
        foreach ($adjustments->where('type', 'deduction') as $adj) {
            $rule = PayrollRule::where('id', $adj->payroll_rule_id)->first();

            $this->addItem($payroll, $rule->code, $adj->name, 'deduction',
                round((float) ($adj->value ?? 0), 2));
        }
    }

    // =========================================================================
    // AMOUNT RESOLVERS
    // =========================================================================

    private function resolveRuleAmount(PayrollRule $rule): float
    {
        if ($rule->formula_type === 'fixed') {
            return (float) $rule->value;
        }

        if ($rule->formula_type === 'percentage') {
            $base = match ($rule->applies_to) {
                'BASICPAY' => $this->basicPay,
                'GROSSPAY' => $this->grossPay,
                default    => $this->grossPay,
            };
            return $base * ($rule->value / 100);
        }

        return 0;
    }

    private function resolveAssignmentAmount(PayrollAssignment $item): float
    {
        if ($item->formula_type === 'fixed') {
            return (float) $item->value;
        }

        if ($item->formula_type === 'percentage') {
            $base = match ($item->applies_to ?? 'GROSSPAY') {
                'BASICPAY' => $this->basicPay,
                default    => $this->grossPay,
            };
            return $base * ($item->value / 100);
        }

        return 0;
    }

    // =========================================================================
    // ZRA PAYE — progressive brackets (legislated, not DB-driven)
    // =========================================================================

    public function calculatePaye(float $gross): float
    {
        $brackets = [
            ['min' =>    0, 'max' =>  5100, 'rate' => 0.00],
            ['min' => 5100, 'max' =>  7100, 'rate' => 0.20],
            ['min' => 7100, 'max' =>  9200, 'rate' => 0.30],
            ['min' => 9200, 'max' =>  null, 'rate' => 0.37],
        ];

        $tax = 0;

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
    // UTILITY
    // =========================================================================

    private function roundAll(array $values): array
    {
        return array_map(fn(float $v) => round($v, 2), $values);
    }

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
