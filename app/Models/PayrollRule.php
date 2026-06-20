<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollRule extends Model
{
    protected $fillable = [

    'name',
    'code',

    'type',
    'category',

    'formula_type',
    'value',
    'applies_to',

    'tax_profile',

    'is_statutory',
    'is_recurring',
    'requires_assignment',

    'affects_gross',
    'affects_net',
    'show_on_payslip',

    'is_pensionable',

    'sort_order',
    'description',

    'active'
];

    /*
    |--------------------------------------------------------------------------
    | TAX PROFILE CONSTANTS — use these in code instead of raw strings
    |--------------------------------------------------------------------------
    */
    const TAX_TAXABLE     = 'taxable';      // PAYE + NAPSA
    const TAX_NAPSA_ONLY  = 'napsa_only';   // NAPSA only (Gratuity, PILON, Ex gratia)
    const TAX_NON_TAXABLE = 'non_taxable';  // Neither

    /*
    |--------------------------------------------------------------------------
    | CONVENIENCE SCOPES
    |--------------------------------------------------------------------------
    */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function scopeEarnings($query)
    {
        return $query->where('type', 'earning');
    }

    public function scopeDeductions($query)
    {
        return $query->where('type', 'deduction');
    }

    public function scopeTaxable($query)
    {
        return $query->where('tax_profile', self::TAX_TAXABLE);
    }

    public function scopeNapsaOnly($query)
    {
        return $query->where('tax_profile', self::TAX_NAPSA_ONLY);
    }

    public function scopeNonTaxable($query)
    {
        return $query->where('tax_profile', self::TAX_NON_TAXABLE);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS — readable booleans for the engine
    |--------------------------------------------------------------------------
    */
    public function isPAYEable(): bool
    {
        return $this->type === 'earning'
            && $this->tax_profile === self::TAX_TAXABLE;
    }

    public function isNAPSAable(): bool
    {
        return $this->type === 'earning'
            && in_array($this->tax_profile, [self::TAX_TAXABLE, self::TAX_NAPSA_ONLY]);
    }
}