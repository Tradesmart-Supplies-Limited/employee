<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reminders:contract')->dailyAt('08:00');
// Attendance: close the day and email the report at midnight, Zambian time.
// (Also runs by itself from the kiosk tablet / admin dashboard if this scheduler isn't set up.)
Schedule::command('attendance:close-day')->dailyAt('00:00')->timezone(config('attendance.timezone', 'Africa/Lusaka'));
Schedule::command('attendance:close-day')->hourly()->timezone(config('attendance.timezone', 'Africa/Lusaka')); // catch-up if midnight was missed


// Announcements: email any that are due (scheduled ones go out when their time comes).
Artisan::command('announcements:send', function () {
    $this->info(app(\App\Services\Announcements::class)->emailDue() . ' announcement email(s) queued.');
})->purpose('Email announcements that are due');
Schedule::command('announcements:send')->everyTenMinutes();
