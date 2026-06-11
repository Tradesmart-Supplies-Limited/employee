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
        Schema::create('payroll_items', function (Blueprint $table) {

    $table->id();

    $table->foreignId('payroll_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->string('code');

    $table->string('description');

    $table->enum('type',[
        'earning',
        'deduction'
    ]);

    $table->decimal('amount',12,2)->default(0);

    $table->decimal('balance',12,2)->nullable();

    $table->decimal('days_hours',8,2)->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
