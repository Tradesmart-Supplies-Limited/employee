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
            if (! Schema::hasColumn('employees', 'residential_address')) {
                $table->string('residential_address', 500)->nullable()->after('secondary_phone');
            }
            if (! Schema::hasColumn('employees', 'marital_status')) {
                $table->string('marital_status', 30)->nullable()->after('gender');
            }
        });

        // Payroll (NAPSA) reads `dob`, the forms filled `date_of_birth`: copy across both ways.
        if (Schema::hasColumn('employees', 'dob')) {
            DB::table('employees')->whereNull('dob')->whereNotNull('date_of_birth')->update(['dob' => DB::raw('date_of_birth')]);
            DB::table('employees')->whereNull('date_of_birth')->whereNotNull('dob')->update(['date_of_birth' => DB::raw('dob')]);
        }

        // Sensitive details changed by employees in the portal wait here for HR approval.
        Schema::create('profile_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('changes');                 // {"bank_account_no": {"old": "...", "new": "..."}}
            $table->string('note', 1000)->nullable(); // employee's explanation
            $table->string('status', 20)->default('pending'); // pending | approved | rejected | cancelled
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('review_note', 1000)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_change_requests');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['residential_address', 'marital_status']);
        });
    }
};
