<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_reminder_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('contract_reminder_setting_id')
                ->nullable()
                ->constrained('contract_reminder_settings')
                ->nullOnDelete();

            // e.g. "days_before_14", "days_before_7", "monthly_fixed"
            $table->string('trigger_type');

            // The calendar date the reminder job ran on — used to prevent
            // sending the same trigger twice for the same employee on the same day.
            $table->date('run_date');

            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['employee_id', 'trigger_type', 'run_date'],
                'contract_reminder_logs_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_reminder_logs');
    }
};
