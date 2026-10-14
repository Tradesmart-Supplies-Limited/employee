<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_reminder_settings', function (Blueprint $table) {
            $table->boolean('track_contracts')->default(true)->after('monthly_lookahead_days');
            $table->boolean('track_probation')->default(true)->after('track_contracts');
            $table->boolean('include_expired')->default(true)->after('track_probation');
            $table->boolean('notify_supervisor')->default(false)->after('include_expired');
        });

        // What each log row is about, so a reminder is never sent twice for the
        // same end date + threshold — even if the job missed some days.
        Schema::table('contract_reminder_logs', function (Blueprint $table) {
            $table->string('event', 20)->nullable()->after('trigger_type');   // contract | probation | digest
            $table->date('end_date')->nullable()->after('event');
            $table->integer('threshold')->nullable()->after('end_date');      // days before; 0 = expired
            $table->index(['employee_id', 'event', 'end_date'], 'crl_employee_event_end');
        });
    }

    public function down(): void
    {
        Schema::table('contract_reminder_logs', function (Blueprint $table) {
            $table->dropIndex('crl_employee_event_end');
            $table->dropColumn(['event', 'end_date', 'threshold']);
        });

        Schema::table('contract_reminder_settings', function (Blueprint $table) {
            $table->dropColumn(['track_contracts', 'track_probation', 'include_expired', 'notify_supervisor']);
        });
    }
};
