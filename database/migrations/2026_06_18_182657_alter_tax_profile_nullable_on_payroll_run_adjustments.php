<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_run_adjustments', function (Blueprint $table) {
            $table->enum('tax_profile', ['taxable', 'napsa_only', 'non_taxable'])
                  ->nullable()
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_run_adjustments', function (Blueprint $table) {
            $table->enum('tax_profile', ['taxable', 'napsa_only', 'non_taxable'])
                  ->default('taxable')
                  ->change();
        });
    }
};