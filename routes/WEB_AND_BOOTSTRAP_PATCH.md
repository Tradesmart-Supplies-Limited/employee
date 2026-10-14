# 1. bootstrap/app.php: register the middleware aliases

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'staff'           => \App\Http\Middleware\EnsureStaff::class,
            'portal'          => \App\Http\Middleware\EnsureEmployeePortal::class,
            'password.change' => \App\Http\Middleware\ForcePasswordChange::class,
        ]);
    })

# 2. routes/web.php: add at the very bottom

    require __DIR__ . '/portal.php';

# 3. routes/web.php: lock the admin area against employee accounts

Without this step an employee login could open /dashboard, /employees, /payroll etc.

a) Every  Route::middleware('auth')  becomes  Route::middleware(['auth', 'staff'])
   (there are three: the main group, the "HR Dashboard" leave group, and the
   payroll group, which is  Route::middleware('auth')->prefix('payroll')...).

b) The /logout route lives inside the first auth group. Move it OUT so employees can still sign out:

       Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

c) These routes are currently reachable WITHOUT login. Wrap them in the staff group too
   (this was already a hole before the portal; with employees holding real logins it matters more):

       Route::prefix('settings')->name('settings.')...                 (contract-reminders)
       Route::prefix('payroll/reports')->name('payroll.reports.')...   (NAPSA / ZRA / bank exports)
       Route::post('/payroll/{payroll}/email', ...)                    (payslip email)
       Route::post('/payroll/runs/{run}/email-all', ...)

   e.g.  Route::middleware(['auth', 'staff'])->group(function () { ...those routes... });

d) Leave the public leave form (/leave/apply, POST /leave/apply) as it is.

# 4. Optional but recommended: restrict registration

/register is open to anyone and lets them pick role admin or hr. Consider removing it or putting it
behind  ['auth', 'staff']  now that real employee data is reachable through login.
