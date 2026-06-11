<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();

            // e.g. 2026-07 or July 2026
            $table->string('period');

            // Draft → Processing → Processed → Paid
            $table->string('status')->default('Draft');

            $table->decimal('total_income', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_pay', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};