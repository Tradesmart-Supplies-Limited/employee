<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;

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





Route::get('/employees', [EmployeeController::class, 'index'])
    ->name('employees.index');

Route::get('/employees/create', [EmployeeController::class, 'create'])
    ->name('employees.create');

Route::post('/employees', [EmployeeController::class, 'store'])
    ->name('employees.store');

Route::get('/employees/{employee}', [EmployeeController::class, 'show'])
    ->name('employees.show');

Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])
    ->name('employees.edit');

Route::put('/employees/{employee}', [EmployeeController::class, 'update'])
    ->name('employees.update');

Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])
    ->name('employees.destroy');