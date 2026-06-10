<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | EMPLOYEE DETAILS
            |--------------------------------------------------------------------------
            */
            $table->string('employee_name');
            $table->string('employee_number')->nullable();
            $table->string('position')->nullable();
            $table->string('email');

            /*
            |--------------------------------------------------------------------------
            | LEAVE DETAILS
            |--------------------------------------------------------------------------
            */
            $table->string('leave_type');
            $table->string('other_leave_type')->nullable();

            $table->date('date_from');
            $table->date('date_to');

            $table->integer('total_days')->default(0);

            $table->date('return_date')->nullable();

            $table->longText('reason')->nullable();

            /*
            |--------------------------------------------------------------------------
            | LATE APPLICATION
            |--------------------------------------------------------------------------
            */
            $table->longText('late_application_reason')->nullable();

            /*
            |--------------------------------------------------------------------------
            | APPLICANT
            |--------------------------------------------------------------------------
            */
            $table->date('applicant_signature_date')->nullable();

            /*
            |--------------------------------------------------------------------------
            | SUPERVISOR
            |--------------------------------------------------------------------------
            */
            $table->enum('status', [
                'Pending',
                'Approved',
                'Rejected'
            ])->default('Pending');

            $table->longText('supervisor_comments')->nullable();

            $table->string('supervisor_name')->nullable();
            $table->string('supervisor_position')->nullable();
            $table->date('supervisor_signature_date')->nullable();

            /*
            |--------------------------------------------------------------------------
            | HR
            |--------------------------------------------------------------------------
            */
            $table->integer('days_accrued')->nullable();
            $table->integer('days_available')->nullable();
            $table->integer('days_requested')->nullable();
            $table->integer('days_balance')->nullable();

            $table->string('hr_name')->nullable();
            $table->string('hr_position')->nullable();
            $table->date('hr_signature_date')->nullable();

            /*
            |--------------------------------------------------------------------------
            | DOCUMENT
            |--------------------------------------------------------------------------
            */
            $table->string('supporting_document')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
    }
};
