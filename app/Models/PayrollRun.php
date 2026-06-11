<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    protected $fillable = [
        'period',
        'status',
        'total_income',
        'total_deductions',
        'net_pay',
    ];

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }
}