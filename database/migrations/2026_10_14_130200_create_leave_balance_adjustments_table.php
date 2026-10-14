<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ledger: every change to an employee's leave balance, with who / why.
        Schema::create('leave_balance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_request_id')->nullable()->constrained('leave_requests')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // approval | restore | manual | year_start | edit | opening
            $table->string('type', 20);
            $table->decimal('days', 6, 1);            // + credit / − debit
            $table->decimal('balance_before', 6, 1);
            $table->decimal('balance_after', 6, 1);
            $table->unsignedSmallInteger('year');
            $table->string('reason', 500)->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'year']);
            $table->index(['type', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balance_adjustments');
    }
};
