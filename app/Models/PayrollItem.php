<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_id',
        'code',
        'description',
        'type',
        'amount',
        'balance',
        'days_hours'
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
}
