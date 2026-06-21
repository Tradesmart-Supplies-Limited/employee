<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\PayrollRun;
use Maatwebsite\Excel\Concerns\FromArray;

class PayrollAdjustmentTemplateExport implements FromArray
{
    protected $run;

    public function __construct(PayrollRun $run)
    {
        $this->run = $run;
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = [
            'employee_id',
            'employee_number',
            'employee_name',
            'amount'
        ];

        $employees = Employee::orderBy('first_name')->get();

        foreach ($employees as $employee) {

            $rows[] = [
                $employee->id,
                $employee->employee_id,
                trim(
                    $employee->first_name .
                    ' ' .
                    $employee->last_name
                ),
                ''
            ];
        }

        return $rows;
    }
}