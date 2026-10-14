<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Links a login to an employee record (employees.id).
            // Staff accounts (admin / hr) leave this NULL.
            $table->foreignId('employee_id')
                ->nullable()
                ->after('role')
                ->unique()
                ->constrained('employees')
                ->nullOnDelete();

            // Forces a password change on first login (temporary passwords).
            $table->boolean('must_change_password')->default(false)->after('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
            $table->dropColumn('must_change_password');
        });
    }
};
