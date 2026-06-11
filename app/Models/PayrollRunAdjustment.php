<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollRunAdjustment extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'name',
        'type',
        'formula_type',
        'value',
        'active'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function run()
    {
        return $this->belongsTo(PayrollRun::class);
    }
}
