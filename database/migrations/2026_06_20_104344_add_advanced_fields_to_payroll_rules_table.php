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
        Schema::table('payroll_rules', function (Blueprint $table) {

            $table->string('code')->nullable()->after('name');

            $table->string('category')->nullable()->after('type');

            $table->boolean('is_statutory')
                ->default(false);

            $table->boolean('is_recurring')
                ->default(true);

            $table->boolean('requires_assignment')
                ->default(false);

            $table->boolean('affects_gross')
                ->default(true);

            $table->boolean('affects_net')
                ->default(true);

            $table->boolean('show_on_payslip')
                ->default(true);

            $table->boolean('is_pensionable')
                ->default(false);

            $table->integer('sort_order')
                ->default(0);

            $table->text('description')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_rules', function (Blueprint $table) {
            $table->dropColumn([
        'code',
            'category',
            'is_statutory',
            'is_recurring',
            'requires_assignment',
            'affects_gross',
            'affects_net',
            'show_on_payslip',
            'is_pensionable',
            'sort_order',
            'description', ]);
        });
    }
};