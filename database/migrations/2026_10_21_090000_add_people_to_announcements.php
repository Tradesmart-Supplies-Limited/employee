<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Announcements can go to specific people (as well as everyone or departments).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $t) {
            if (! Schema::hasColumn('announcements', 'employee_ids')) {
                $t->json('employee_ids')->nullable()->after('departments');
            }
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $t) {
            $t->dropColumn('employee_ids');
        });
    }
};
