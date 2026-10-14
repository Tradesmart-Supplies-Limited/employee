<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sensitive changes need HR approval
    |--------------------------------------------------------------------------
    | Legal name, date of birth, NRC / passport, bank account and NAPSA / TPIN /
    | NHIMA numbers. true  = saved as a request; HR approves before it applies.
    |                false = applied immediately (HR still gets an email).
    | Contact, address, emergency contact and next of kin always apply immediately.
    */
    'sensitive_changes_require_approval' => (bool) env('PORTAL_SENSITIVE_CHANGES_REQUIRE_APPROVAL', true),

    // Employees may delete documents they uploaded themselves (never HR's).
    'employees_can_delete_own_documents' => (bool) env('PORTAL_EMPLOYEES_CAN_DELETE_OWN_DOCUMENTS', true),

    // Email HR when an employee uploads a document.
    'notify_hr_on_upload' => (bool) env('PORTAL_NOTIFY_HR_ON_UPLOAD', true),

];
