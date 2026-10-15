<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * The self-service portal: Employee, HR, Manager (and Admin) logins that are
 * linked to an Active employee record. Directors have no portal.
 * Alias: "portal"
 */
class EnsureEmployeePortal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Directors, and staff with no employee record, use the admin area only.
        if (! $user->canUsePortal()) {
            return $user->canUseAdmin()
                ? redirect()->route('dashboard')->with('error', $user->isDirector()
                    ? 'Directors use the admin area only.'
                    : 'Your login isn\'t linked to an employee record, so there is no self-service portal for it. Ask HR to link it.')
                : $this->signOut($request, 'Your account has no access role yet. Please contact HR.');
        }

        $employee = $user->employee;

        // Exited / suspended employees lose portal access immediately.
        if (! $employee || strtolower((string) $employee->employment_status) !== 'active') {
            if ($user->canUseAdmin()) {
                return redirect()->route('dashboard')->with('error', 'Your employee record isn\'t Active, so your self-service portal is closed.');
            }

            return $this->signOut($request, 'Your portal access is not active. Please contact HR.');
        }

        return $next($request);
    }

    private function signOut(Request $request, string $message): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
