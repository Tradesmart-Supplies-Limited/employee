<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per employee per day: the attendance register.
        Schema::create('attendance_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->date('date');
            $t->time('clock_in')->nullable();
            $t->time('clock_out')->nullable();
            // present | late | absent | on_leave | holiday | off | half_day
            $t->string('status', 20)->default('present');
            $t->unsignedInteger('minutes_late')->default(0);
            $t->unsignedInteger('worked_minutes')->default(0);
            $t->boolean('left_early')->default(false);
            $t->boolean('missing_out')->default(false);
            // qr | import | manual | biometric | system
            $t->string('source_in', 20)->nullable();
            $t->string('source_out', 20)->nullable();
            $t->string('in_photo')->nullable();
            $t->string('out_photo')->nullable();
            $t->string('notes', 500)->nullable();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->unique(['employee_id', 'date']);
            $t->index(['date', 'status']);
        });

        // Tablets / devices allowed to record scans. The token is the device's "password".
        Schema::create('attendance_kiosks', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('location')->nullable();
            $t->string('token', 64)->unique();
            $t->boolean('is_active')->default(true);
            $t->boolean('capture_photo')->default(true);
            $t->timestamp('last_seen_at')->nullable();
            $t->string('last_ip', 45)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        // Every scan attempt (accepted or not) - the audit trail.
        Schema::create('attendance_scans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->foreignId('attendance_kiosk_id')->nullable()->constrained('attendance_kiosks')->nullOnDelete();
            $t->dateTime('scanned_at');          // local time (config attendance.timezone)
            $t->date('date');
            $t->string('action', 20);            // in | out | duplicate | rejected
            $t->string('method', 20)->default('qr');
            $t->string('code_hint', 40)->nullable();
            $t->string('message')->nullable();
            $t->string('photo')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamps();

            $t->index(['date', 'action']);
            $t->index(['employee_id', 'scanned_at']);
        });

        Schema::create('attendance_settings', function (Blueprint $t) {
            $t->id();
            $t->string('work_start', 5)->default('08:00');
            $t->string('work_end', 5)->default('17:00');
            $t->unsignedSmallInteger('grace_minutes')->default(15);
            $t->json('working_days')->nullable();            // ISO 1=Mon … 7=Sun
            $t->unsignedSmallInteger('half_day_minutes')->default(240);
            $t->unsignedSmallInteger('scan_cooldown_minutes')->default(5);
            $t->boolean('count_holidays_off')->default(true);
            $t->boolean('report_enabled')->default(true);
            $t->boolean('report_include_hr')->default(true);
            $t->json('report_recipients')->nullable();       // management emails
            $t->boolean('report_attach_csv')->default(true);
            $t->date('last_closed_date')->nullable();
            $t->timestamp('last_report_at')->nullable();
            $t->timestamps();
        });

        Schema::table('employees', function (Blueprint $t) {
            if (! Schema::hasColumn('employees', 'attendance_qr_version')) {
                // Bump to cancel a lost badge: old QR codes stop working.
                $t->unsignedSmallInteger('attendance_qr_version')->default(1);
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $t) {
            if (Schema::hasColumn('employees', 'attendance_qr_version')) {
                $t->dropColumn('attendance_qr_version');
            }
        });
        Schema::dropIfExists('attendance_scans');
        Schema::dropIfExists('attendance_kiosks');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_settings');
    }
};
