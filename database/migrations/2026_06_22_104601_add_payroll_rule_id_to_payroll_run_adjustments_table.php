<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payroll_run_adjustments', function (Blueprint $table) {

            // simple nullable column (no FK constraint)
            $table->unsignedBigInteger('payroll_rule_id')
                ->nullable()
                ->after('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_run_adjustments', function (Blueprint $table) {

            $table->dropColumn('payroll_rule_id');
        });
    }
};