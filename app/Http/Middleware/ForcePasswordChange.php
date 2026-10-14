<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends users with a temporary password to the change-password page.
 * Alias: "password.change"
 */
class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            return redirect()
                ->route('portal.password.edit')
                ->with('error', 'Please set a new password before continuing.');
        }

        return $next($request);
    }
}
