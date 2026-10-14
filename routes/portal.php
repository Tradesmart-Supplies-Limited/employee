<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OvertimeController as AdminOvertimeController;
use App\Http\Controllers\Portal;
use App\Http\Controllers\PortalAccountController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| EMPLOYEE SELF-SERVICE PORTAL  (employees only)
|--------------------------------------------------------------------------
| auth      : must be logged in
| portal    : must be a linked, Active employee account
| password.change : temporary-password users must set a new one first
*/
Route::middleware(['auth', 'portal'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function () {

        // Reachable even while a password change is forced
        Route::get('password', [Portal\PasswordController::class, 'edit'])->name('password.edit');
        Route::put('password', [Portal\PasswordController::class, 'update'])->name('password.update');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::middleware('password.change')->group(function () {

            Route::get('/', [Portal\DashboardController::class, 'index'])->name('dashboard');

            // Profile
            Route::get('profile', [Portal\ProfileController::class, 'show'])->name('profile.show');
            Route::get('profile/edit', [Portal\ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('profile', [Portal\ProfileController::class, 'update'])->name('profile.update');
            Route::post('profile/requests/{change}/cancel', [Portal\ProfileController::class, 'cancelRequest'])->name('profile.requests.cancel');

            // Announcements from HR
            Route::get('announcements', [Portal\AnnouncementController::class, 'index'])->name('announcements.index');
            Route::post('announcements/read-all', [Portal\AnnouncementController::class, 'readAll'])->name('announcements.read-all');
            Route::get('announcements/{announcement}', [Portal\AnnouncementController::class, 'show'])->name('announcements.show');
            Route::get('announcements/{announcement}/attachment', [Portal\AnnouncementController::class, 'attachment'])->name('announcements.attachment');

            // Shifts: my rota, and (supervisors) the team rota editor
            Route::get('shifts', [Portal\ShiftController::class, 'index'])->name('shifts.index');
            Route::get('team-rota', [\App\Http\Controllers\RotaController::class, 'index'])->name('rota.index');
            Route::post('team-rota', [\App\Http\Controllers\RotaController::class, 'store'])->name('rota.store');
            Route::get('team-rota/{rota}', [\App\Http\Controllers\RotaController::class, 'edit'])->name('rota.edit');
            Route::put('team-rota/{rota}', [\App\Http\Controllers\RotaController::class, 'update'])->name('rota.update');
            Route::post('team-rota/{rota}/publish', [\App\Http\Controllers\RotaController::class, 'publish'])->name('rota.publish');
            Route::post('team-rota/{rota}/unpublish', [\App\Http\Controllers\RotaController::class, 'unpublish'])->name('rota.unpublish');
            Route::post('team-rota/{rota}/copy', [\App\Http\Controllers\RotaController::class, 'copyPrevious'])->name('rota.copy');
            Route::get('team-rota/{rota}/print', [\App\Http\Controllers\RotaController::class, 'print'])->name('rota.print');
            Route::delete('team-rota/{rota}', [\App\Http\Controllers\RotaController::class, 'destroy'])->name('rota.destroy');

            // Attendance: my QR code + history
            Route::get('attendance', [Portal\AttendanceController::class, 'index'])->name('attendance.index');

            // Documents
            Route::get('documents', [Portal\DocumentController::class, 'index'])->name('documents.index');
            Route::post('documents', [Portal\DocumentController::class, 'store'])->name('documents.store');
            Route::get('documents/{index}', [Portal\DocumentController::class, 'download'])->whereNumber('index')->name('documents.download');
            Route::delete('documents/{index}', [Portal\DocumentController::class, 'destroy'])->whereNumber('index')->name('documents.destroy');

            // Leave
            Route::get('leave', [Portal\LeaveController::class, 'index'])->name('leave.index');
            Route::get('leave/create', [Portal\LeaveController::class, 'create'])->name('leave.create');
            Route::post('leave', [Portal\LeaveController::class, 'store'])->name('leave.store');
            Route::get('leave/{leave}', [Portal\LeaveController::class, 'show'])->name('leave.show');
            Route::delete('leave/{leave}', [Portal\LeaveController::class, 'destroy'])->name('leave.destroy');

            // Team approvals (for employees who supervise others)
            Route::get('team-leave', [Portal\TeamLeaveController::class, 'index'])->name('team-leave.index');
            Route::get('team-leave/{leave}', [Portal\TeamLeaveController::class, 'show'])->name('team-leave.show');
            Route::post('team-leave/{leave}/decision', [Portal\TeamLeaveController::class, 'decide'])->name('team-leave.decide');

            // Overtime
            Route::get('overtime', [Portal\OvertimeController::class, 'index'])->name('overtime.index');
            Route::get('overtime/create', [Portal\OvertimeController::class, 'create'])->name('overtime.create');
            Route::post('overtime', [Portal\OvertimeController::class, 'store'])->name('overtime.store');
            Route::delete('overtime/{overtime}', [Portal\OvertimeController::class, 'destroy'])->name('overtime.destroy');

            // Payslips
            Route::get('payslips', [Portal\PayslipController::class, 'index'])->name('payslips.index');
            Route::get('payslips/{payroll}', [Portal\PayslipController::class, 'show'])->name('payslips.show');
            Route::get('payslips/{payroll}/pdf', [Portal\PayslipController::class, 'pdf'])->name('payslips.pdf');
        });
    });

/*
|--------------------------------------------------------------------------
| ADMIN / HR SIDE OF THE PORTAL  (staff only)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'staff'])->group(function () {

    // Overtime approvals
    Route::get('/overtime', [AdminOvertimeController::class, 'index'])->name('overtime.index');
    Route::post('/overtime/{overtime}/approve', [AdminOvertimeController::class, 'approve'])->name('overtime.approve');
    Route::post('/overtime/{overtime}/reject', [AdminOvertimeController::class, 'reject'])->name('overtime.reject');

    // Portal logins for employees
    Route::post('/employees/{employee}/portal-account', [PortalAccountController::class, 'store'])->name('employees.portal.store');
    Route::post('/employees/{employee}/portal-account/reset', [PortalAccountController::class, 'resetPassword'])->name('employees.portal.reset');
    Route::delete('/employees/{employee}/portal-account', [PortalAccountController::class, 'destroy'])->name('employees.portal.destroy');
});
