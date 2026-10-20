<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Automatic clock-out: anyone still clocked in an hour (configurable) after
 * the day's last shift ends is clocked out by the system.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_settings', function (Blueprint $t) {
            if (! Schema::hasColumn('attendance_settings', 'auto_clockout_enabled')) {
                $t->boolean('auto_clockout_enabled')->default(true);
            }
            if (! Schema::hasColumn('attendance_settings', 'auto_clockout_after_minutes')) {
                $t->unsignedSmallInteger('auto_clockout_after_minutes')->default(60);
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_settings', function (Blueprint $t) {
            $t->dropColumn(['auto_clockout_enabled', 'auto_clockout_after_minutes']);
        });
    }
};
