<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** The Management self-service portal is for Management logins. */
class EnsureManagementPortal
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->isDirector()) {
            return redirect()->route('dashboard')->with('error', 'The Management portal is for Management logins. You\'re welcome to use your own area instead.');
        }

        return $next($request);
    }
}
