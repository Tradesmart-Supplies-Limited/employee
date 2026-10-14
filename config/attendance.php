<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Local time zone for attendance
    |--------------------------------------------------------------------------
    | The app itself runs on UTC. Clock-in times, "today", lateness and the
    | midnight report all use this zone so 08:00 means 08:00 in Lusaka.
    */
    'timezone' => env('ATTENDANCE_TIMEZONE', 'Africa/Lusaka'),

    /*
    |--------------------------------------------------------------------------
    | QR codes
    |--------------------------------------------------------------------------
    | Badge QR codes carry the employee ID plus a short signature made with
    | APP_KEY, so a code typed up in any QR app with someone's ID won't work.
    | Set to false to also accept plain employee IDs (e.g. older ID cards).
    */
    'qr_require_signature' => env('ATTENDANCE_QR_SIGNED', true),

    // Prefix inside every badge QR code: TSLATT.<employee id>.<signature>
    'qr_prefix' => 'TSLATT',

    // Kiosk photos (taken at each scan) are kept this many days, 0 = forever.
    'photo_retention_days' => (int) env('ATTENDANCE_PHOTO_DAYS', 90),

    // Max days the "missed midnight" catch-up will go back.
    'catch_up_days' => 7,
];
