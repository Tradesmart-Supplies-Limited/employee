<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollRunAdjustment extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'payroll_rule_id',
        'name',
        'type',           // 'earning' | 'deduction'
        'formula_type',   // 'fixed' | 'percentage'
        'value',
        'tax_profile',    // 'taxable' | 'napsa_only' | 'non_taxable'  (earnings only)
        'active',
    ];

    /*
    |--------------------------------------------------------------------------
    | TAX PROFILE CONSTANTS
    |--------------------------------------------------------------------------
    */
    const TAX_TAXABLE     = 'taxable';      // PAYE + NAPSA
    const TAX_NAPSA_ONLY  = 'napsa_only';   // NAPSA only — Gratuity, PILON, Ex gratia
    const TAX_NON_TAXABLE = 'non_taxable';  // Neither

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function run()
    {
        return $this->belongsTo(PayrollRun::class);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS — used by the engine to route into the correct tax base
    |--------------------------------------------------------------------------
    */
    public function isPAYEable(): bool
    {
        // Only earning adjustments with 'taxable' profile enter the PAYE base
        return $this->type === 'earning'
            && $this->tax_profile === self::TAX_TAXABLE;
    }

    public function isNAPSAable(): bool
    {
        // Both 'taxable' and 'napsa_only' earnings enter the NAPSA base
        return $this->type === 'earning'
            && in_array($this->tax_profile, [self::TAX_TAXABLE, self::TAX_NAPSA_ONLY]);
    }

    public function isFullyExempt(): bool
    {
        return $this->type === 'earning'
            && $this->tax_profile === self::TAX_NON_TAXABLE;
    }
}