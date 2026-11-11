<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Management self-service portal:
 *  - profile details and a profile photo on the login itself (Management has
 *    no employee record);
 *  - Management's own clock-in / clock-out, kept apart from staff attendance
 *    (Management is left out of staff attendance reports).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'photo_path')) {
                $t->string('photo_path')->nullable();
                $t->string('job_title', 120)->nullable();
                $t->string('phone', 40)->nullable();
                $t->string('office_location', 120)->nullable();
                $t->text('bio')->nullable();
            }
        });

        if (! Schema::hasTable('management_attendance')) {
            Schema::create('management_attendance', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $t->date('date');
                $t->time('clock_in')->nullable();
                $t->time('clock_out')->nullable();
                $t->string('in_ip', 45)->nullable();
                $t->string('out_ip', 45)->nullable();
                $t->string('note', 255)->nullable();
                $t->timestamps();
                $t->unique(['user_id', 'date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('management_attendance');
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['photo_path', 'job_title', 'phone', 'office_location', 'bio']);
        });
    }
};
