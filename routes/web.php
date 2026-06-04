<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

// Profile (static view for now)
Route::get('/profile', function () {
    return view('dashboard.profile');
})->name('profile.index');

// Settings (static view for now)
Route::get('/settings', function () {
    return view('dashboard.settings');
})->name('settings.index');

// Logout placeholder (optional for now)
Route::post('/logout', function () {
    return redirect('/dashboard');
})->name('logout');