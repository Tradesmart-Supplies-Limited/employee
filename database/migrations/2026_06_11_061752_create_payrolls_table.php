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
        Schema::create('payrolls', function (Blueprint $table) {
    $table->id();

    $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

    $table->string('pay_period'); // November 2025

    $table->string('company')->nullable();
    $table->string('branch')->nullable();
    $table->string('cost_centre')->nullable();

    $table->date('date_engaged')->nullable();

    $table->decimal('salary_rate', 12, 2)->default(0);

    /*
    |--------------------------------------------------------------------------
    | YTD
    |--------------------------------------------------------------------------
    */

    $table->decimal('total_income_ytd',12,2)->default(0);
    $table->decimal('net_tax_ytd',12,2)->default(0);
    $table->decimal('tax_ytd',12,2)->default(0);
    $table->decimal('napsa_ytd',12,2)->default(0);

    $table->decimal('leave_days',8,2)->default(0);
    $table->decimal('leave_days_value',12,2)->default(0);

    /*
    |--------------------------------------------------------------------------
    | TOTALS
    |--------------------------------------------------------------------------
    */

    $table->decimal('total_income',12,2)->default(0);
    $table->decimal('total_deductions',12,2)->default(0);
    $table->decimal('net_pay',12,2)->default(0);
    

    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    $table->enum('status',[
        'Draft',
        'Processed',
        'Approved',
        'Paid'
    ])->default('Draft');

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
