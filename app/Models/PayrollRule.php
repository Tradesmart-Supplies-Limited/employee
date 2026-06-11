<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollRule extends Model
{
    protected $fillable = [
        'name',
        'type',
        'formula_type',
        'value',
        'applies_to',
        'active'
    ];
}
