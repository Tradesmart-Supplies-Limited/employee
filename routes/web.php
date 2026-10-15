<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeImportController;
use App\Http\Controllers\ProfileChangeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LeaveManagementController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PayrollRuleController;
use App\Http\Controllers\PayrollReportsController;
use App\Http\Controllers\PayslipMailController;
use App\Http\Controllers\ContractReminderSettingController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceImportController;
use App\Http\Controllers\AttendanceSetupController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\RotaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AccountPasswordController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\Auth\SetupController;

/*
|--------------------------------------------------------------------------
| ATTENDANCE KIOSK (entrance tablet) — no login, the secret link is the key
|--------------------------------------------------------------------------
*/
Route::prefix('kiosk/{token}')->name('kiosk.')->where(['token' => '[A-Za-z0-9]{20,64}'])->middleware('throttle:180,1')->group(function () {
    Route::get('/', [KioskController::class, 'show'])->name('show');
    Route::post('scan', [KioskController::class, 'scan'])->name('scan');
    Route::get('ping', [KioskController::class, 'ping'])->name('ping');
});

/*
|--------------------------------------------------------------------------
| HOME: welcome page that helps people pick the right login
|--------------------------------------------------------------------------
*/
Route::get('/', [WelcomeController::class, 'index'])->name('home');
Route::get('/setup/done', [SetupController::class, 'done'])->middleware('auth')->name('setup.done');

/*
|--------------------------------------------------------------------------
| GUEST ROUTES (NOT LOGGED IN)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // First-time setup: creates the first Admin login, with email confirmation.
    // Only open while there are no logins at all (the controller closes it after).
    Route::get('/setup', [SetupController::class, 'show'])->name('setup');
    Route::post('/setup/details', [SetupController::class, 'details'])->middleware('throttle:10,1')->name('setup.details');
    Route::post('/setup/verify', [SetupController::class, 'verify'])->middleware('throttle:15,1')->name('setup.verify');
    Route::post('/setup/resend', [SetupController::class, 'resend'])->middleware('throttle:6,1')->name('setup.resend');
    Route::post('/setup/password', [SetupController::class, 'password'])->middleware('throttle:10,1')->name('setup.password');
    Route::post('/setup/restart', [SetupController::class, 'restart'])->name('setup.restart');
    Route::redirect('/register', '/setup')->name('register');

    // Forgot / reset password (all users) — by emailed link or 6-digit code
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email');
    Route::get('/forgot-password/code', [PasswordResetController::class, 'otpForm'])->name('password.otp');
    Route::post('/forgot-password/code', [PasswordResetController::class, 'otpVerify'])->name('password.otp.verify');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});
/*
| Staff-only admin tools (were reachable without login before)
*/
Route::middleware(['auth', 'staff', 'perm:full'])->group(function () {

Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('contract-reminders', [ContractReminderSettingController::class, 'index'])
        ->name('contract-reminders.index');

    Route::put('contract-reminders/{setting}', [ContractReminderSettingController::class, 'update'])
        ->name('contract-reminders.update');

    Route::post('contract-reminders/{setting}/test', [ContractReminderSettingController::class, 'sendTest'])
        ->name('contract-reminders.test');

    Route::post('contract-reminders/run', [ContractReminderSettingController::class, 'run'])
        ->name('contract-reminders.run');
});
 
// Optional: add a sidebar/nav link pointing at



Route::prefix('payroll/reports')->name('payroll.reports.')->group(function () {
 
    // Main dashboard — pick a run, see summary, jump to any export
    Route::get('/', [PayrollReportsController::class, 'index'])->name('index');
 
    // Management reports — PDF export
    // {report} = total_earnings | total_deductions | net_payable |
    //            statutory_summary | comprehensive | department | branch
    Route::get('/{run}/management/{report}/pdf',
        [PayrollReportsController::class, 'exportManagementPdf'])
        ->name('management.pdf');
 
    // Statutory submissions
    Route::get('/{run}/napsa/csv',
        [PayrollReportsController::class, 'exportNapsaCsv'])
        ->name('napsa.csv');
 
    Route::get('/{run}/zra/excel',
        [PayrollReportsController::class, 'exportZraExcel'])
        ->name('zra.excel');
 
    Route::get('/{run}/nhima/csv',
        [PayrollReportsController::class, 'exportNhimaCsv'])
        ->name('nhima.csv');
 
    Route::get('/{run}/wcfcb/csv',
        [PayrollReportsController::class, 'exportWcfcbCsv'])
        ->name('wcfcb.csv');
 
    // Banking & payments
    Route::get('/{run}/bank-payments/csv',
        [PayrollReportsController::class, 'exportBankPaymentsCsv'])
        ->name('bank_payments.csv');
 
});

Route::post('/payroll/{payroll}/email', [PayslipMailController::class, 'sendSingle'])
    ->name('payroll.email.single');
 
Route::post('/payroll/runs/{run}/email-all', [PayslipMailController::class, 'sendBulk'])
    ->name('payroll.email.bulk');

}); // end staff-only admin tools



/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES (LOGGED IN USERS ONLY)
|--------------------------------------------------------------------------
*/
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Change password for admin-area logins without a portal (temporary passwords land here first)
Route::middleware(['auth', 'staff'])->group(function () {
    Route::get('/account/password', [AccountPasswordController::class, 'edit'])->name('account.password.edit');
    Route::put('/account/password', [AccountPasswordController::class, 'update'])->name('account.password.update');
});

Route::middleware(['auth', 'staff', 'password.change'])->group(function () {

    // Everyone in the admin area (Admin, HR, Director, Manager)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::view('/profile', 'dashboard.profile')->name('profile.index');

    // ------------------------------------------------------------------
    // Admin / HR / Director only
    // ------------------------------------------------------------------
    Route::middleware('perm:full')->group(function () {

        // ✅ STATIC ROUTES FIRST
        Route::get('/employees/sample-csv', [EmployeeController::class, 'downloadSampleCsv'])
            ->name('employees.sample-csv');

        // Bulk import: upload → preview → confirm
        Route::get('/employees/import', [EmployeeImportController::class, 'create'])->name('employees.import');
        Route::get('/employees/import/template', [EmployeeImportController::class, 'template'])->name('employees.import.template');
        Route::post('/employees/import/preview', [EmployeeImportController::class, 'preview'])->name('employees.import.preview');
        Route::get('/employees/import/preview/{token}', [EmployeeImportController::class, 'showPreview'])->name('employees.import.show');
        Route::post('/employees/import/commit/{token}', [EmployeeImportController::class, 'commit'])->name('employees.import.commit');

        Route::get('/employees/export', [EmployeeController::class, 'export'])->name('employees.export');

        // Employee documents
        Route::post('/employees/{employee}/documents', [EmployeeController::class, 'documentStore'])->name('employees.documents.store');
        Route::get('/employees/{employee}/documents/{index}', [EmployeeController::class, 'documentDownload'])->whereNumber('index')->name('employees.documents.download');
        Route::delete('/employees/{employee}/documents/{index}', [EmployeeController::class, 'documentDestroy'])->whereNumber('index')->name('employees.documents.destroy');

        // Employee self-service change requests (HR approval)
        Route::get('/profile-changes', [ProfileChangeController::class, 'index'])->name('profile-changes.index');
        Route::post('/profile-changes/{change}/approve', [ProfileChangeController::class, 'approve'])->name('profile-changes.approve');
        Route::post('/profile-changes/{change}/reject', [ProfileChangeController::class, 'reject'])->name('profile-changes.reject');

        // Employees
        Route::resource('employees', EmployeeController::class);

        // Users & roles (who can log in, and what they can do)
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/reset', [UserController::class, 'resetPassword'])->name('users.reset');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Others
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::get('/departments/{slug}', [DepartmentController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('departments.show');

        // Announcements (shown in the employee portal; optional email)
        Route::get('announcements/{announcement}/attachment', [AnnouncementController::class, 'attachment'])->name('announcements.attachment');
        Route::resource('announcements', AnnouncementController::class);

        Route::get('/payroll/runs/all', [DashboardController::class, 'payroll'])->name('payroll.index');
        Route::get('/reports', [DashboardController::class, 'reports'])->name('reports.index');
        Route::view('/settings', 'dashboard.settings')->name('settings.index');
    });

    // ------------------------------------------------------------------
    // Attendance: Managers see their own department; setup is HR only
    // ------------------------------------------------------------------
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::middleware('perm:attendance.view')->group(function () {
            Route::get('/', [AttendanceController::class, 'index'])->name('index');
            Route::get('live', [AttendanceController::class, 'live'])->name('live');
            Route::get('register', [AttendanceController::class, 'register'])->name('register');
            Route::get('summary', [AttendanceController::class, 'summary'])->name('summary');
            Route::get('export', [AttendanceController::class, 'export'])->name('export');
            Route::get('employee/{employee}', [AttendanceController::class, 'employee'])->name('employee');
            Route::get('photo', [AttendanceController::class, 'photo'])->name('photo');
        });
        Route::post('register', [AttendanceController::class, 'registerSave'])->middleware('perm:attendance.edit')->name('register.save');

        // Weekly shift rotas (HR: every rota department; Managers: their own)
        Route::middleware('perm:rota.manage')->group(function () {
            Route::get('rota', [RotaController::class, 'index'])->name('rota.index');
            Route::post('rota', [RotaController::class, 'store'])->name('rota.store');
            Route::get('rota/{rota}', [RotaController::class, 'edit'])->name('rota.edit');
            Route::put('rota/{rota}', [RotaController::class, 'update'])->name('rota.update');
            Route::post('rota/{rota}/publish', [RotaController::class, 'publish'])->name('rota.publish');
            Route::post('rota/{rota}/unpublish', [RotaController::class, 'unpublish'])->name('rota.unpublish');
            Route::post('rota/{rota}/copy', [RotaController::class, 'copyPrevious'])->name('rota.copy');
            Route::get('rota/{rota}/print', [RotaController::class, 'print'])->name('rota.print');
            Route::delete('rota/{rota}', [RotaController::class, 'destroy'])->name('rota.destroy');
        });

        Route::middleware('perm:full')->group(function () {
            Route::get('import', [AttendanceImportController::class, 'create'])->name('import');
            Route::get('import/template', [AttendanceImportController::class, 'template'])->name('import.template');
            Route::post('import', [AttendanceImportController::class, 'preview'])->name('import.preview');
            Route::get('import/{token}', [AttendanceImportController::class, 'show'])->name('import.show');
            Route::post('import/{token}', [AttendanceImportController::class, 'commit'])->name('import.commit');

            Route::get('settings', [AttendanceSetupController::class, 'settings'])->name('settings');
            Route::put('settings', [AttendanceSetupController::class, 'updateSettings'])->name('settings.update');
            Route::post('settings/test-report', [AttendanceSetupController::class, 'testReport'])->name('settings.test');
            Route::post('settings/send-day', [AttendanceSetupController::class, 'sendDay'])->name('settings.send-day');

            Route::get('qr', [AttendanceSetupController::class, 'qr'])->name('qr');
            Route::get('qr/badges', [AttendanceSetupController::class, 'badges'])->name('badges');
            Route::post('qr/{employee}/reissue', [AttendanceSetupController::class, 'reissue'])->name('qr.reissue');
            Route::post('kiosks', [AttendanceSetupController::class, 'storeKiosk'])->name('kiosks.store');
            Route::put('kiosks/{kiosk}', [AttendanceSetupController::class, 'updateKiosk'])->name('kiosks.update');
            Route::post('kiosks/{kiosk}/regenerate', [AttendanceSetupController::class, 'regenerateKiosk'])->name('kiosks.regenerate');
            Route::delete('kiosks/{kiosk}', [AttendanceSetupController::class, 'destroyKiosk'])->name('kiosks.destroy');
        });
    });
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
| HR Dashboard — leave workflow (staff only: employee portal logins are
| redirected away so they can never approve their own leave)
|--------------------------------------------------------------------------
| PATCH /leave/{leave}/supervisor → stage 2: supervisor recommendation
| POST  /leave/{leave}/approve    → stage 3: FINAL approval + balance deduction
| POST  /leave/{leave}/reject     → stage 3: FINAL rejection
*/

Route::middleware(['auth', 'staff', 'password.change'])->group(function () {

    // Managers: their department's requests + the supervisor step
    Route::middleware('perm:leave.view')->group(function () {
        Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
        Route::get('/leave/{leave}', [LeaveController::class, 'show'])->name('leave.show');
        Route::get('/leave/{leave}/print', [LeaveController::class, 'print'])->name('leave.print');
    });
    Route::patch('/leave/{leave}/supervisor', [LeaveController::class, 'updateSupervisor'])
        ->middleware('perm:leave.supervise')->name('leave.supervisor.update');

    // Final decision, deletion, paper HR record: HR / Admin / Director
    Route::middleware('perm:full')->group(function () {
        Route::post('/leave/{leave}/approve', [LeaveController::class, 'approve'])->name('leave.approve');
        Route::post('/leave/{leave}/reject', [LeaveController::class, 'reject'])->name('leave.reject');
        Route::delete('/leave/{leave}', [LeaveController::class, 'destroy'])->name('leave.destroy');
        Route::patch('/leave/{leave}/hr', [LeaveController::class, 'updateHr'])->name('leave.hr.update');
    });

    /*
    | Leave Management: reports, planner, balances, holidays
    | (Managers: their department's balances and planner only)
    */
    Route::prefix('leave-management')->name('leave.manage.')->controller(LeaveManagementController::class)->group(function () {
        Route::middleware('perm:leave.reports')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/planner', 'planner')->name('planner');
            Route::get('/export/balances', 'exportBalances')->name('export.balances');
            Route::get('/export/requests', 'exportRequests')->name('export.requests');
        });

        Route::middleware('perm:full')->group(function () {
            Route::post('/new-year', 'startYear')->name('new-year');

            Route::get('/record', 'recordForm')->name('record');
            Route::post('/record', 'recordStore')->name('record.store');

            Route::get('/employees/{employee}', 'employee')->name('employee');
            Route::post('/employees/{employee}/adjust', 'adjust')->name('adjust');

            Route::get('/holidays', 'holidays')->name('holidays');
            Route::post('/holidays', 'holidayStore')->name('holidays.store');
            Route::post('/holidays/generate', 'holidayGenerate')->name('holidays.generate');
            Route::patch('/holidays/{holiday}/toggle', 'holidayToggle')->name('holidays.toggle');
            Route::delete('/holidays/{holiday}', 'holidayDestroy')->name('holidays.destroy');
        });
    });

});

Route::get('/payroll/rules', function () {
    return "RULES WORKING";
});

Route::middleware(['auth', 'staff', 'perm:full'])->prefix('payroll')->name('payroll.')->group(function () {

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




require __DIR__ . '/portal.php';