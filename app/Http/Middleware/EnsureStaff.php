<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * The admin / HR area: Admin, HR, Director and Manager roles only.
 * Employee (portal-only) accounts go to their portal; accounts with no
 * recognised role are signed out.
 * Alias: "staff"
 */
class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->canUseAdmin()) {
            if ($user->canUsePortal()) {
                return redirect()->route('portal.dashboard');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has no access role yet. Please contact HR.',
            ]);
        }

        return $next($request);
    }
}
