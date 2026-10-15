<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'staff'           => \App\Http\Middleware\EnsureStaff::class,
            'portal'          => \App\Http\Middleware\EnsureEmployeePortal::class,
            'password.change' => \App\Http\Middleware\ForcePasswordChange::class,
            'perm'            => \App\Http\Middleware\EnsurePermission::class,
        ]);

        // The entrance tablet stays open for days; its secret link authenticates it,
        // so scans must not fail when a session's CSRF token expires.
        $middleware->validateCsrfTokens(except: ['kiosk/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
