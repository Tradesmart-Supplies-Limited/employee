<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PayrollRuleController;
use App\Http\Controllers\PayrollReportController;
use App\Http\Controllers\PayslipMailController;

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


Route::prefix('payroll/reports')->name('payroll.reports.')->group(function () {
 
    // Main dashboard — pick a run, see summary, jump to any export
    Route::get('/', [PayrollReportController::class, 'index'])->name('index');
 
    // Management reports — PDF export
    // {report} = total_earnings | total_deductions | net_payable |
    //            statutory_summary | comprehensive | department | branch
    Route::get('/{run}/management/{report}/pdf',
        [PayrollReportController::class, 'exportManagementPdf'])
        ->name('management.pdf');
 
    // Statutory submissions
    Route::get('/{run}/napsa/csv',
        [PayrollReportController::class, 'exportNapsaCsv'])
        ->name('napsa.csv');
 
    Route::get('/{run}/zra/excel',
        [PayrollReportController::class, 'exportZraExcel'])
        ->name('zra.excel');
 
    Route::get('/{run}/nhima/csv',
        [PayrollReportController::class, 'exportNhimaCsv'])
        ->name('nhima.csv');
 
    Route::get('/{run}/wcfcb/csv',
        [PayrollReportController::class, 'exportWcfcbCsv'])
        ->name('wcfcb.csv');
 
    // Banking & payments
    Route::get('/{run}/bank-payments/csv',
        [PayrollReportController::class, 'exportBankPaymentsCsv'])
        ->name('bank_payments.csv');
 
});

Route::post('/payroll/{payroll}/email', [PayslipMailController::class, 'sendSingle'])
    ->name('payroll.email.single');
 
Route::post('/payroll/runs/{run}/email-all', [PayslipMailController::class, 'sendBulk'])
    ->name('payroll.email.bulk');



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

Route::get('/payroll/rules', function () {
    return "RULES WORKING";
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
    Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])->name('show');
    Route::get('/payroll/{payroll}/print', [PayrollController::class, 'print'])->name('print');
    Route::get('/payroll/{payroll}/pdf', [PayrollController::class, 'downloadPdf'])->name('pdf');


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

    
    Route::get('/rules',              [PayrollRuleController::class, 'index'])->name('rules.index');
    Route::post('/rules',             [PayrollRuleController::class, 'store'])->name('rules.store');
    Route::patch('/rules/{rule}',     [PayrollRuleController::class, 'update'])->name('rules.update');
    Route::delete('/rules/{rule}',    [PayrollRuleController::class, 'destroy'])->name('rules.destroy');
    Route::post('/rules/seed',        [PayrollRuleController::class, 'seedDefaults'])->name('rules.seedDefaults');

    
    Route::post('/runs/{run}/adjustments',          [PayrollRunController::class, 'storeAdjustment'])->name('runs.adjustments.store');
    Route::delete('/runs/adjustments/{adjustment}', [PayrollRunController::class, 'destroyAdjustment'])->name('runs.adjustments.destroy');
    Route::get(
        '/runs/{run}/adjustments/template',
        [PayrollRunController::class, 'downloadAdjustmentTemplate']
    )->name('runs.adjustments.template');

    Route::post(
        '/runs/{run}/adjustments/import',
        [PayrollRunController::class, 'importAdjustmentsExcel']
    )->name('runs.adjustments.import');

    Route::get(
        '/runs/{run}/adjustments/template/{rule}',
        [PayrollRunController::class, 'downloadAdjustmentTemplate']
    )->name('runs.adjustments.template');

    

    Route::get(
        '/runs/{run}/employee/{payroll}/summary',
        [PayrollRunController::class, 'employeeSummary']
    )->name('runs.employee.summary');



    Route::post('/items/{item}/update-field', [PayrollRunController::class, 'updateField']);

    Route::delete('/items/{item}', [PayrollRunController::class, 'deleteItem']);

    Route::post('/{payroll}/items/store', [PayrollRunController::class, 'storeItem']);


});




