<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
 protected $fillable = [

        // Employee Details
        'employee_name',
        'employee_number',
        'position',

        // Leave Details
        'leave_type',
        'other_leave_type',
        'date_from',
        'date_to',
        'total_days',
        'return_date',
        'reason',

        // Late Application
        'late_application_reason',

        // Applicant
        'applicant_signature_date',

        // Supervisor Section
        'status',
        'supervisor_comments',
        'supervisor_decision',
        'supervisor_name',
        'supervisor_position',
        'supervisor_signature_date',

        // HR Section
        'days_accrued',
        'days_available',
        'days_requested',
        'days_balance',
        'hr_name',
        'hr_position',
        'hr_signature_date',

        // Supporting Documents
        'supporting_document',

        // Emails
        'email',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'return_date' => 'date',
        'applicant_signature_date' => 'date',
        'supervisor_signature_date' => 'date',
        'hr_signature_date' => 'date',
    ];
}