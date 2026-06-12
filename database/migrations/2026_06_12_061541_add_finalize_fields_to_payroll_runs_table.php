<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {

            $table->timestamp('finalized_at')->nullable()->after('status');

            $table->foreignId('finalized_by')
                ->nullable()
                ->after('finalized_at')
                ->constrained('users')
                ->nullOnDelete();

        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {

            $table->dropForeign(['finalized_by']);

            $table->dropColumn([
                'finalized_at',
                'finalized_by'
            ]);

        });
    }
};