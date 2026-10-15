<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Each person's colour theme (Tradesmart blue, Zambezi teal, …). Null = Tradesmart blue. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'ui_accent')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('ui_accent', 20)->nullable()->after('role');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'ui_accent')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('ui_accent');
            });
        }
    }
};
