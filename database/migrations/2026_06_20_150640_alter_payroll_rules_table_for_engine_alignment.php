<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_rules', function (Blueprint $table) {

            /*
            |------------------------------------------------------
            | FIX 1: TYPE COLUMN (MAIN ERROR SOURCE)
            |------------------------------------------------------
            | Your error:
            | SQLSTATE 1265 Data truncated for column 'type'
            |
            | FIX: enforce valid enum values used by engine
            */
            $table->enum('type', ['earning', 'deduction', 'system'])
                ->default('earning')
                ->change();

            /*
            |------------------------------------------------------
            | FIX 2: CATEGORY EXPANSION
            |------------------------------------------------------
            | Must support: allowance, tax, pension, cap, etc.
            */
            $table->string('category')->nullable()->change();

            /*
            |------------------------------------------------------
            | FIX 3: VALUE SAFETY
            |------------------------------------------------------
            | Ensure decimal precision for payroll calculations
            */
            $table->decimal('value', 15, 4)->default(0)->change();

            /*
            |------------------------------------------------------
            | FIX 4: BOOLEAN SAFETY (MySQL compatibility)
            |------------------------------------------------------
            */
            $table->boolean('is_statutory')->default(false)->change();
            $table->boolean('is_recurring')->default(true)->change();
            $table->boolean('requires_assignment')->default(false)->change();
            $table->boolean('affects_gross')->default(false)->change();
            $table->boolean('affects_net')->default(true)->change();
            $table->boolean('show_on_payslip')->default(true)->change();
            $table->boolean('is_pensionable')->default(false)->change();
            $table->boolean('active')->default(true)->change();

            /*
            |------------------------------------------------------
            | FIX 5: ENGINE SAFETY INDEX
            |------------------------------------------------------
            */
            $table->integer('sort_order')->default(100)->change();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_rules', function (Blueprint $table) {

            $table->enum('type', ['earning', 'deduction'])
                ->default('earning')
                ->change();

            $table->string('category')->nullable()->change();

            $table->decimal('value', 10, 2)->default(0)->change();

            $table->boolean('is_statutory')->default(0)->change();
            $table->boolean('is_recurring')->default(1)->change();
            $table->boolean('requires_assignment')->default(0)->change();
            $table->boolean('affects_gross')->default(0)->change();
            $table->boolean('affects_net')->default(1)->change();
            $table->boolean('show_on_payslip')->default(1)->change();
            $table->boolean('is_pensionable')->default(0)->change();
            $table->boolean('active')->default(1)->change();

            $table->integer('sort_order')->default(100)->change();
        });
    }
};