<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payroll_rules', function (Blueprint $table) {
            $table->id();

            $table->string('name'); 
            // PAYE, NAPSA, Transport

            $table->enum('type', ['earning', 'deduction']);

            $table->enum('formula_type', ['fixed', 'percentage']);

            $table->decimal('value', 10, 2);
            // 500 OR 20

            $table->string('applies_to')->nullable();
            // basic_salary, gross, etc

            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_rules');
    }
};