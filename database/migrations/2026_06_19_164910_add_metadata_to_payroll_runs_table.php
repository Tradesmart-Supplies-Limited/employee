<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {

            // Optional alias/name for the payroll run
            // Examples:
            // "July Main Payroll"
            // "July Bonus Run"
            // "July Corrections"
            $table->string('alias')
                  ->nullable()
                  ->after('period');

            // User who generated the payroll run
            $table->unsignedBigInteger('created_by')
                  ->nullable()
                  ->after('alias');

            // User who audited/reviewed the payroll run
            $table->unsignedBigInteger('audited_by')
                  ->nullable()
                  ->after('created_by');

            // Optional audit date
            $table->timestamp('audited_at')
                  ->nullable()
                  ->after('audited_by');

            // Foreign keys
            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();

            $table->foreign('audited_by')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {

            $table->dropForeign(['created_by']);
            $table->dropForeign(['audited_by']);

            $table->dropColumn([
                'alias',
                'created_by',
                'audited_by',
                'audited_at',
            ]);
        });
    }
};