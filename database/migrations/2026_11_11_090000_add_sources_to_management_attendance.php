<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Management attendance: remember how each time was recorded
 * (button = live clock-in/out, manual = typed in, auto = closed at midnight)
 * and when a day was last edited.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('management_attendance', function (Blueprint $t) {
            if (! Schema::hasColumn('management_attendance', 'in_source')) {
                $t->string('in_source', 10)->default('button');
                $t->string('out_source', 10)->nullable();
                $t->timestamp('edited_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('management_attendance', function (Blueprint $t) {
            $t->dropColumn(['in_source', 'out_source', 'edited_at']);
        });
    }
};
