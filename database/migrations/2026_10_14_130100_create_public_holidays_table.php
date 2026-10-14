<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('name');
            // generated = from the Zambian calendar; manual = added by HR
            $table->string('source', 20)->default('manual');
            // Generated days are switched off instead of deleted, so they don't come back.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->unsignedSmallInteger('calendar_days')->nullable()->after('total_days');
            $table->boolean('exclude_sundays')->nullable()->after('calendar_days');
            $table->boolean('exclude_saturdays')->nullable()->after('exclude_sundays');
            $table->boolean('exclude_holidays')->nullable()->after('exclude_saturdays');
            // [{"date":"2026-10-24","reason":"Independence Day"}, ...]
            $table->json('excluded_dates')->nullable()->after('exclude_holidays');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_holidays');

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['calendar_days', 'exclude_sundays', 'exclude_saturdays', 'exclude_holidays', 'excluded_dates']);
        });
    }
};
