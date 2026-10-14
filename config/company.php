<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Company branding used across payslips, management reports, and
    | payslip emails. Centralized here so it's set once instead of being
    | hardcoded as a string in every controller.
    |--------------------------------------------------------------------------
    */

    'name' => env('COMPANY_NAME', 'TRADESMART SUPPLIES LIMITED'),

    'logo_url' => env('COMPANY_LOGO_URL', 'http://misc.tradesmartzm.com/logo.png'),

    /*
    |--------------------------------------------------------------------------
    | HR notification recipients
    |--------------------------------------------------------------------------
    | Comma-separated list in .env:  HR_NOTIFICATION_EMAILS=a@x.com,b@x.com
    | Every leave notification job reads this key. If it is empty, the leave
    | workflow falls back to all users with the "admin" or "hr" role.
    */
    'hr_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('HR_NOTIFICATION_EMAILS', ''))
    ))),

];
