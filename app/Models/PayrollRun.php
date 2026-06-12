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
        'finalized_at',
        'finalized_by',
    ];

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function finalizedBy()
{
    return $this->belongsTo(User::class, 'finalized_by');
}
}