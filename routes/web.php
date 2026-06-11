<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PayrollRuleController;

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
    Route::get('/payroll/runs/all', [DashboardController::class, 'payroll'])->name('payroll.index');
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


Route::middleware('auth')->prefix('payroll')->name('payroll.')->group(function () {

    // -------------------------
    // PAYROLL (EMPLOYEE LEVEL)
    // -------------------------
    Route::get('/', [PayrollController::class, 'index'])->name('index');
    Route::get('/create', [PayrollController::class, 'create'])->name('create');
    Route::post('/store', [PayrollController::class, 'store'])->name('store');

    Route::get('/{payroll}', [PayrollController::class, 'show'])->name('show');
    Route::get('/{payroll}/edit', [PayrollController::class, 'edit'])->name('edit');
    Route::put('/{payroll}', [PayrollController::class, 'update'])->name('update');
    Route::delete('/{payroll}', [PayrollController::class, 'destroy'])->name('destroy');

    Route::post('/{payroll}/process', [PayrollController::class, 'process'])->name('process');
    Route::get('/{payroll}/print', [PayrollController::class, 'print'])->name('print');


    // -------------------------
    // PAYROLL RUNS (BATCH LEVEL)
    // -------------------------
    Route::get('/runs/all', [PayrollRunController::class, 'index'])->name('runs.index');
    Route::get('/runs/create', [PayrollRunController::class, 'create'])->name('runs.create');
    Route::post('/runs', [PayrollRunController::class, 'store'])->name('runs.store');

    Route::get('/runs/{run}', [PayrollRunController::class, 'show'])->name('runs.show');

    Route::post('/runs/{run}/generate', [PayrollRunController::class, 'generate'])->name('runs.generate');

    Route::post('/runs/{run}/finalize', [PayrollRunController::class, 'finalize'])->name('runs.finalize');

    Route::get('/runs/{run}/payslips', [PayrollRunController::class, 'payslips'])->name('runs.payslips');

    // LIST (optional page later if you want full screen manager)
    // Route::get('/', [PayrollRuleController::class, 'index'])->name('index');

    Route::post('/rules/store', [PayrollRuleController::class, 'store'])->name('rules.store');

    Route::post('/rules/bulk-update', [PayrollRuleController::class, 'bulkUpdate'])->name('rules.bulkUpdate');

    Route::post('/runs/{run}/adjustments', [PayrollRunController::class, 'storeAdjustment'])->name('runs.adjustments.store');

    

    Route::get(
        '/runs/{run}/employee/{payroll}/summary',
        [PayrollRunController::class, 'employeeSummary']
    )->name('runs.employee.summary');



Route::post('/items/{item}/update-field', [PayrollRunController::class, 'updateField']);

Route::delete('/adjustments/{adjustment}', [PayrollRunController::class, 'deleteAdjustment']);

Route::post('/{payroll}/items/store', [PayrollRunController::class, 'storeItem']);


});