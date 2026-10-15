<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends users with a temporary password to the change-password page
 * (the portal page, or the admin-area page for logins without a portal).
 * Alias: "password.change"
 */
class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            // Portal logins use the portal page; Directors / Admins without one use the admin page.
            if ($user->canUsePortal()) {
                return redirect()->route('portal.password.edit')->with('error', 'Please set a new password before continuing.');
            }
            if ($user->canUseAdmin()) {
                return redirect()->route('account.password.edit');
            }
        }

        return $next($request);
    }
}
