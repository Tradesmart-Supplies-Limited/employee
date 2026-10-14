<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Working shifts. Times per kind of day; blank = this shift doesn't run that day.
        Schema::create('attendance_shifts', function (Blueprint $t) {
            $t->id();
            $t->string('name', 60);
            $t->string('code', 20)->unique();
            $t->string('color', 9)->default('#185FA5');
            foreach (['weekday', 'saturday', 'sunday', 'holiday'] as $d) {
                $t->string("{$d}_start", 5)->nullable();
                $t->string("{$d}_end", 5)->nullable();
            }
            $t->boolean('is_default')->default(false);   // everyone not on a rota works this one
            $t->boolean('is_active')->default(true);
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        $now = now();
        DB::table('attendance_shifts')->insert([
            ['name' => 'Day shift', 'code' => 'DAY', 'color' => '#0284c7', 'weekday_start' => '08:00', 'weekday_end' => '17:00', 'saturday_start' => '09:00', 'saturday_end' => '12:00', 'sunday_start' => '08:00', 'sunday_end' => '17:00', 'holiday_start' => '08:00', 'holiday_end' => '17:00', 'is_default' => true, 'is_active' => true, 'sort' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Night shift', 'code' => 'NIGHT', 'color' => '#7c3aed', 'weekday_start' => '15:00', 'weekday_end' => '22:00', 'saturday_start' => '15:00', 'saturday_end' => '20:00', 'sunday_start' => null, 'sunday_end' => null, 'holiday_start' => '15:00', 'holiday_end' => '20:00', 'is_default' => false, 'is_active' => true, 'sort' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('attendance_settings', function (Blueprint $t) {
            $t->boolean('rota_enabled')->default(true);
            $t->json('rota_departments')->nullable();
            $t->boolean('overtime_auto')->default(true);          // Sunday / holiday work → overtime claim
            $t->boolean('comp_off_required')->default(true);      // warn when a Sunday worker has no day off that week
        });
        DB::table('attendance_settings')->update(['rota_departments' => json_encode(['Operations'])]);

        Schema::table('attendance_records', function (Blueprint $t) {
            $t->foreignId('shift_id')->nullable()->constrained('attendance_shifts')->nullOnDelete();
            $t->string('scheduled_start', 5)->nullable();
            $t->string('scheduled_end', 5)->nullable();
            $t->string('day_type', 10)->nullable();               // weekday | saturday | sunday | holiday
            $t->boolean('rostered')->default(false);              // on a published rota that day
            $t->unsignedInteger('overtime_minutes')->default(0);
            $t->unsignedBigInteger('overtime_request_id')->nullable();
        });

        // One rota per department per week (Monday → Sunday).
        Schema::create('rotas', function (Blueprint $t) {
            $t->id();
            $t->string('department', 120);
            $t->date('week_start');
            $t->string('status', 20)->default('draft');            // draft | published
            $t->unsignedSmallInteger('version')->default(0);
            $t->text('notes')->nullable();
            $t->json('published_snapshot')->nullable();            // employee_id => [date => code] at last publish
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->unique(['department', 'week_start']);
        });

        Schema::create('rota_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('rota_id')->constrained('rotas')->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->date('date');
            $t->string('kind', 10);                                // shift | backup | off
            $t->foreignId('shift_id')->nullable()->constrained('attendance_shifts')->nullOnDelete();
            $t->string('note', 200)->nullable();
            $t->timestamps();
            $t->unique(['rota_id', 'employee_id', 'date']);
            $t->index(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rota_assignments');
        Schema::dropIfExists('rotas');
        Schema::table('attendance_records', function (Blueprint $t) {
            $t->dropConstrainedForeignId('shift_id');
            $t->dropColumn(['scheduled_start', 'scheduled_end', 'day_type', 'rostered', 'overtime_minutes', 'overtime_request_id']);
        });
        Schema::table('attendance_settings', function (Blueprint $t) {
            $t->dropColumn(['rota_enabled', 'rota_departments', 'overtime_auto', 'comp_off_required']);
        });
        Schema::dropIfExists('attendance_shifts');
    }
};
