<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Who this employee reports to. Exactly one of these is set:
            // another employee, or a staff (admin / HR) user account.
            $table->foreignId('supervisor_employee_id')
                ->nullable()
                ->after('supervisor')
                ->constrained('employees')
                ->nullOnDelete();

            $table->foreignId('supervisor_user_id')
                ->nullable()
                ->after('supervisor_employee_id')
                ->constrained('users')
                ->nullOnDelete();

            // Leave balance (days). Deducted automatically on final approval.
            $table->decimal('annual_leave_entitlement', 6, 1)
                ->default(config('leave.annual_entitlement', 24))
                ->after('employment_status');

            $table->decimal('leave_balance', 6, 1)
                ->default(0)
                ->after('annual_leave_entitlement');
        });

        /*
        | Opening balance for existing employees:
        | entitlement minus Annual leave already approved this calendar year.
        | HR should review these numbers once and adjust where needed.
        */
        $entitlement = (float) config('leave.annual_entitlement', 24);
        $types       = config('leave.deductible_types', ['Annual']);

        $taken = DB::table('leave_requests')
            ->select('employee_id', DB::raw('SUM(total_days) as days'))
            ->whereNotNull('employee_id')
            ->where('status', 'Approved')
            ->whereIn('leave_type', $types)
            ->whereYear('date_from', now()->year)
            ->groupBy('employee_id')
            ->pluck('days', 'employee_id');

        DB::table('employees')->orderBy('id')->each(function ($employee) use ($entitlement, $taken) {
            DB::table('employees')->where('id', $employee->id)->update([
                'annual_leave_entitlement' => $entitlement,
                'leave_balance'            => $entitlement - (float) ($taken[$employee->id] ?? 0),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_employee_id');
            $table->dropConstrainedForeignId('supervisor_user_id');
            $table->dropColumn(['annual_leave_entitlement', 'leave_balance']);
        });
    }
};
