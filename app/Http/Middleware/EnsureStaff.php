<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps employee self-service accounts out of the admin / HR area.
 * Alias: "staff"
 */
class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isPortalUser()) {
            return redirect()->route('portal.dashboard');
        }

        return $next($request);
    }
}
