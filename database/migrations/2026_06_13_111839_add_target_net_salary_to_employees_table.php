<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {

            $table->decimal('net_salary', 12, 2)
                  ->nullable()
                  ->after('salary');

        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {

            $table->dropColumn('net_salary');

        });
    }
};