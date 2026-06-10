<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveController;

/*
|--------------------------------------------------------------------------
| GUEST ROUTES (NOT LOGGED IN)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});


/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES (LOGGED IN USERS ONLY)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // ✅ STATIC ROUTES FIRST
    Route::get('/employees/sample-csv', [EmployeeController::class, 'downloadSampleCsv'])
        ->name('employees.sample-csv');

    Route::post('/employees/import', [EmployeeController::class, 'import'])
        ->name('employees.import');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Employees
    Route::resource('employees', EmployeeController::class);

    // Others
    Route::get('/departments', [DashboardController::class, 'departments'])->name('departments.index');
    Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::get('/attendance', [DashboardController::class, 'attendance'])->name('attendance.index');
    Route::get('/payroll', [DashboardController::class, 'payroll'])->name('payroll.index');
    Route::get('/reports', [DashboardController::class, 'reports'])->name('reports.index');

    Route::view('/profile', 'dashboard.profile')->name('profile.index');
    Route::view('/settings', 'dashboard.settings')->name('settings.index');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Public Leave Form
|--------------------------------------------------------------------------
*/

Route::get('/leave/apply', [LeaveController::class, 'create'])
    ->name('leave.apply');

Route::post('/leave/apply', [LeaveController::class, 'store'])
    ->name('leave.store');


/*
|--------------------------------------------------------------------------
| HR Dashboard
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/leave', [LeaveController::class, 'index'])
        ->name('leave.index');

    Route::get('/leave/{leave}', [LeaveController::class, 'show'])
        ->name('leave.show');

    Route::post('/leave/{leave}/approve', [LeaveController::class, 'approve'])
        ->name('leave.approve');

    Route::post('/leave/{leave}/reject', [LeaveController::class, 'reject'])
        ->name('leave.reject');

        Route::delete('/leave/{leave}', [LeaveController::class, 'destroy'])
    ->name('leave.destroy');

    Route::get('/leave/{leave}/print', [LeaveController::class, 'print'])
    ->name('leave.print');


    Route::patch('/leave/{leave}/supervisor', [LeaveController::class, 'updateSupervisor'])
    ->name('leave.supervisor.update');

Route::patch('/leave/{leave}/hr', [LeaveController::class, 'updateHr'])
    ->name('leave.hr.update');

});