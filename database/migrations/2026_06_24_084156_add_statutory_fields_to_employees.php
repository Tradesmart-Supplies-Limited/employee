<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Adds statutory ID and banking fields needed for NAPSA / ZRA / NHIMA
    | submissions and bulk bank payment exports.
    |
    | Each column is added only if it doesn't already exist, so this is safe
    | to run even if some of these were added previously under different
    | migrations.
    |--------------------------------------------------------------------------
    */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {

            if (!Schema::hasColumn('employees', 'ssn')) {
                $table->string('ssn')->nullable()->after('employee_id')
                      ->comment('NAPSA Social Security Number');
            }
            if (!Schema::hasColumn('employees', 'nrc_no')) {
                $table->string('nrc_no')->nullable()->after('ssn')
                      ->comment('National Registration Card number, e.g. 275564/63/1');
            }
            if (!Schema::hasColumn('employees', 'dob')) {
                $table->date('dob')->nullable()->after('nrc_no')
                      ->comment('Date of birth, required on NAPSA returns');
            }
            if (!Schema::hasColumn('employees', 'tpin')) {
                $table->string('tpin')->nullable()->after('dob')
                      ->comment('ZRA Taxpayer Identification Number');
            }
            if (!Schema::hasColumn('employees', 'nhima_no')) {
                $table->string('nhima_no')->nullable()->after('tpin')
                      ->comment('NHIMA / NHIS membership number');
            }
            if (!Schema::hasColumn('employees', 'napsa_employer_no')) {
                $table->string('napsa_employer_no')->nullable()->after('nhima_no')
                      ->comment('Company NAPSA employer number — usually same for all employees, but kept per-record for multi-entity support');
            }
            if (!Schema::hasColumn('employees', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('napsa_employer_no');
            }
            if (!Schema::hasColumn('employees', 'bank_account_no')) {
                $table->string('bank_account_no')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('employees', 'bank_sort_code')) {
                $table->string('bank_sort_code')->nullable()->after('bank_account_no');
            }
            if (!Schema::hasColumn('employees', 'bank_branch')) {
                $table->string('bank_branch')->nullable()->after('bank_sort_code');
            }
            if (!Schema::hasColumn('employees', 'employment_nature')) {
                $table->string('employment_nature')->default('PERMANENT')->after('bank_branch')
                      ->comment('PERMANENT, CONTRACT, CASUAL — required field on ZRA PAYE returns');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $columns = [
                'ssn', 'nrc_no', 'dob', 'tpin', 'nhima_no', 'napsa_employer_no',
                'bank_name', 'bank_account_no', 'bank_sort_code', 'bank_branch',
                'employment_nature',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};