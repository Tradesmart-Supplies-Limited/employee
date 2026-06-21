<?php

namespace App\Imports;

use App\Models\Payroll;
use App\Models\PayrollRule;
use App\Models\PayrollRun;
use App\Models\PayrollRunAdjustment;
use App\Services\PayrollEngine;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class PayrollAdjustmentImport implements ToCollection
{
    protected $run;
    protected $ruleId;

    public function __construct(
        PayrollRun $run,
        int $ruleId
    ) {
        $this->run = $run;
        $this->ruleId = $ruleId;
    }

    public function collection(Collection $rows)
    {
        $rule = PayrollRule::findOrFail(
            $this->ruleId
        );

        $engine = app(PayrollEngine::class);

        foreach ($rows->skip(1) as $row) {

            $employeeId = $row[0];
            $amount = $row[3];

            if (!$employeeId) {
                continue;
            }

            if (!$amount || $amount <= 0) {
                continue;
            }

            PayrollRunAdjustment::create([

                'payroll_run_id' => $this->run->id,

                'employee_id' => $employeeId,

                'name' => $rule->name,

                'type' => $rule->type,

                'formula_type' => $rule->formula_type,

                'value' => $amount,

                'tax_profile' => $rule->tax_profile,

                'active' => true,
            ]);

            $payroll = Payroll::where(
                'payroll_run_id',
                $this->run->id
            )
            ->where(
                'employee_id',
                $employeeId
            )
            ->first();

            if ($payroll) {
                $engine->build($payroll);
            }
        }
    }
}