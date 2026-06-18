<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | TAX PROFILE — three possible values
    |
    |  'taxable'       Standard earnings:  PAYE ✅  NAPSA ✅  NHIS ❌
    |                  e.g. Overtime, Bonus, Commission
    |
    |  'napsa_only'    Exempt from PAYE:   PAYE ❌  NAPSA ✅  NHIS ❌
    |                  e.g. Gratuity, Payment in lieu of notice, Ex gratia
    |
    |  'non_taxable'   Fully exempt:       PAYE ❌  NAPSA ❌  NHIS ❌
    |                  e.g. Reimbursements, Tool allowances
    |
    | Deduction rules are not affected by tax_profile (column is ignored for
    | them) — only earning rules use this field.
    |--------------------------------------------------------------------------
    */
    public function up(): void
    {
        Schema::table('payroll_rules', function (Blueprint $table) {
            $table->enum('tax_profile', ['taxable', 'napsa_only', 'non_taxable'])
                  ->default('taxable')
                  ->after('applies_to')
                  ->comment('Controls which tax bases this earning contributes to');
        });

        // Back-fill any existing earning rules as fully taxable (safe default)
        DB::table('payroll_rules')
            ->where('type', 'earning')
            ->update(['tax_profile' => 'taxable']);
    }

    public function down(): void
    {
        Schema::table('payroll_rules', function (Blueprint $table) {
            $table->dropColumn('tax_profile');
        });
    }
};