<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            // Supervisor assigned when the request was submitted (snapshot).
            $table->foreignId('supervisor_employee_id')->nullable()->after('employee_id')
                ->constrained('employees')->nullOnDelete();
            $table->foreignId('supervisor_user_id')->nullable()->after('supervisor_employee_id')
                ->constrained('users')->nullOnDelete();

            // Who actually recorded the supervisor decision, and when.
            $table->foreignId('supervisor_acted_by')->nullable()->after('supervisor_signature_date')
                ->constrained('users')->nullOnDelete();
            $table->dateTime('supervisor_acted_at')->nullable()->after('supervisor_acted_by');

            // Final HR / Admin decision.
            $table->string('hr_decision')->nullable()->after('hr_signature_date');
            $table->text('hr_comments')->nullable()->after('hr_decision');
            $table->foreignId('hr_acted_by')->nullable()->after('hr_comments')
                ->constrained('users')->nullOnDelete();
            $table->dateTime('hr_acted_at')->nullable()->after('hr_acted_by');

            // Days actually taken off the employee's balance on approval.
            $table->decimal('balance_deducted', 6, 1)->nullable()->after('hr_acted_at');
        });

        // Allow half days / decimal balances in the HR figures.
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->decimal('days_accrued', 6, 1)->nullable()->change();
            $table->decimal('days_available', 6, 1)->nullable()->change();
            $table->decimal('days_requested', 6, 1)->nullable()->change();
            $table->decimal('days_balance', 6, 1)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_employee_id');
            $table->dropConstrainedForeignId('supervisor_user_id');
            $table->dropConstrainedForeignId('supervisor_acted_by');
            $table->dropConstrainedForeignId('hr_acted_by');
            $table->dropColumn(['supervisor_acted_at', 'hr_decision', 'hr_comments', 'hr_acted_at', 'balance_deducted']);
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->integer('days_accrued')->nullable()->change();
            $table->integer('days_available')->nullable()->change();
            $table->integer('days_requested')->nullable()->change();
            $table->integer('days_balance')->nullable()->change();
        });
    }
};
