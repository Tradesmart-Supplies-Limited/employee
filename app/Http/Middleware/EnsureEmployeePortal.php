<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows only linked employee accounts, and only while the employee is Active.
 * Alias: "portal"
 */
class EnsureEmployeePortal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Staff opening a portal URL go back to the admin dashboard.
        if (! $user || ! $user->isPortalUser()) {
            return redirect()->route('dashboard');
        }

        $employee = $user->employee;

        // Exited / suspended employees lose portal access immediately.
        if (! $employee || strtolower((string) $employee->employment_status) !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your portal access is not active. Please contact HR.',
            ]);
        }

        return $next($request);
    }
}
