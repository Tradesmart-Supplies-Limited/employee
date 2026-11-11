<?php

use App\Http\Controllers\Management\PortalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MANAGEMENT SELF-SERVICE PORTAL
|--------------------------------------------------------------------------
| For Management logins (who have no employee portal): their own profile
| and photo, and their own clock-in / clock-out.
*/
Route::middleware(['auth', 'management', 'password.change'])
    ->prefix('management')
    ->name('mgmt.')
    ->controller(PortalController::class)
    ->group(function () {
        Route::get('/', 'dashboard')->name('dashboard');
        Route::get('profile', 'profile')->name('profile');
        Route::put('profile', 'updateProfile')->name('profile.update');
        Route::post('profile/photo', 'updatePhoto')->name('photo.update');
        Route::delete('profile/photo', 'removePhoto')->name('photo.remove');
        Route::get('attendance', 'attendance')->name('attendance');
        Route::post('attendance/clock', 'clock')->name('clock');
        Route::post('attendance/day', 'saveDay')->name('attendance.save');
        Route::delete('attendance/day/{day}', 'deleteDay')->name('attendance.delete');
        Route::get('attendance/export', 'export')->name('attendance.export');
    });
