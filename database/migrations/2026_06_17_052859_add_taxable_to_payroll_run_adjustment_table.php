<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | TAX PROFILE on payroll_run_adjustments
    |
    |  'taxable'       Earning goes into PAYE base AND NAPSA base
    |                  e.g. Overtime bonus, Commission, Acting allowance
    |
    |  'napsa_only'    Earning goes into NAPSA base only, exempt from PAYE
    |                  e.g. Gratuity, Payment in lieu of notice, Ex gratia
    |
    |  'non_taxable'   Exempt from both PAYE and NAPSA
    |                  e.g. Tool reimbursement, Medical reimbursement
    |
    | Deduction adjustments ignore this column — it only matters for earnings.
    |--------------------------------------------------------------------------
    */
    public function up(): void
    {
        Schema::table('payroll_run_adjustments', function (Blueprint $table) {
            $table->enum('tax_profile', ['taxable', 'napsa_only', 'non_taxable'])
                  ->default('taxable')
                  ->after('value')
                  ->comment('Controls which tax bases this earning adjustment contributes to');
        });

        // Back-fill existing earning adjustments as fully taxable (safe default)
        DB::table('payroll_run_adjustments')
            ->where('type', 'earning')
            ->update(['tax_profile' => 'taxable']);
    }

    public function down(): void
    {
        Schema::table('payroll_run_adjustments', function (Blueprint $table) {
            $table->dropColumn('tax_profile');
        });
    }
};