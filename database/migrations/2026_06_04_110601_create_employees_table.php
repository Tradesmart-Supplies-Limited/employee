<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | IDENTIFICATION
            |--------------------------------------------------------------------------
            */
            $table->string('employee_id')->unique(); // EIN

            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');

            /*
            |--------------------------------------------------------------------------
            | PERSONAL INFORMATION
            |--------------------------------------------------------------------------
            */
            $table->date('date_of_birth');
            $table->string('gender');
            $table->string('nationality');
            $table->string('national_id_number')->nullable();
            $table->string('passport_number')->nullable();

            // Passport photo (file path)
            $table->string('passport_photo')->nullable();

            /*
            |--------------------------------------------------------------------------
            | CONTACT INFORMATION
            |--------------------------------------------------------------------------
            */
            $table->string('personal_email')->nullable();
            $table->string('company_email')->nullable();
            $table->string('primary_phone');
            $table->string('secondary_phone')->nullable();

            /*
            |--------------------------------------------------------------------------
            | JOB INFORMATION
            |--------------------------------------------------------------------------
            */
            $table->string('position');
            $table->string('department')->nullable(); // keeping simple (no FK)
            $table->string('supervisor')->nullable();

            $table->string('employment_status')->default('Active'); 
            // Active | Exited | Suspended

            /*
            |--------------------------------------------------------------------------
            | EMPLOYMENT DATES
            |--------------------------------------------------------------------------
            */
            $table->date('probation_start')->nullable();
            $table->date('probation_end')->nullable();

            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();

            /*
            |--------------------------------------------------------------------------
            | EMERGENCY CONTACT
            |--------------------------------------------------------------------------
            */
            $table->string('emergency_name')->nullable();
            $table->string('emergency_relationship')->nullable();
            $table->string('emergency_phone')->nullable();

            /*
            |--------------------------------------------------------------------------
            | NEXT OF KIN
            |--------------------------------------------------------------------------
            */
            $table->string('next_of_kin_name')->nullable();
            $table->string('next_of_kin_phone')->nullable();
            $table->string('next_of_kin_address')->nullable();

            /*
            |--------------------------------------------------------------------------
            | FINANCIAL INFORMATION
            |--------------------------------------------------------------------------
            */
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('nssf_number')->nullable();
            $table->string('tin_number')->nullable();

            $table->decimal('salary', 15, 2)->nullable();

            /*
            |--------------------------------------------------------------------------
            | DOCUMENT UPLOADS (JSON)
            |--------------------------------------------------------------------------
            | Format:
            | [
            |   { "name": "Contract 2026", "path": "uploads/contracts/file.pdf" },
            |   { "name": "ID Copy", "path": "uploads/docs/id.pdf" }
            | ]
            */
            $table->json('uploads')->nullable();

            /*
            |--------------------------------------------------------------------------
            | SYSTEM FIELDS
            |--------------------------------------------------------------------------
            */
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};