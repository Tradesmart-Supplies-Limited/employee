<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role-based access for admin-area routes.
 *   ->middleware('perm:full')                    Admin / HR / Director only
 *   ->middleware('perm:attendance.view')         …or a Manager with that permission
 *   ->middleware('perm:leave.view,leave.reports') any one of them
 * Alias: "perm"
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        $ok = $user && collect($permissions)->contains(
            fn ($p) => $p === 'full' ? $user->hasFullAccess() : $user->hasPermission($p)
        );

        abort_unless($ok, 403, 'Your role (' . ($user?->roleLabel() ?? 'guest') . ') doesn\'t give access to this page.');

        return $next($request);
    }
}
