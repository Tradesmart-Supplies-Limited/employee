<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollAssignment extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'type',
        'formula_type',
        'value',
        'active',
        'effective_from',
        'effective_to',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}