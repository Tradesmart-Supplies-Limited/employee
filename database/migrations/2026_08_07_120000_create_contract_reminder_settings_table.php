<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_reminder_settings', function (Blueprint $table) {
            $table->id();

            $table->string('name')->default('Default');

            // Who receives the reminder emails (JSON array of email addresses)
            $table->json('recipients')->nullable();

            // "N days before contract end" trigger
            $table->boolean('use_days_before')->default(true);
            $table->json('days_before')->nullable(); // e.g. [14, 7]

            // "Fixed day(s) of month" trigger — e.g. every 1st and 14th
            $table->boolean('use_monthly_fixed')->default(false);
            $table->json('monthly_fixed_days')->nullable(); // e.g. [1, 14]
            $table->unsignedInteger('monthly_lookahead_days')->default(60); // how far ahead the monthly digest looks

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_reminder_settings');
    }
};
