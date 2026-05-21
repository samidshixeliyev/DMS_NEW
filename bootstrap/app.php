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
    ->withMiddleware(function (Middleware $middleware) {
        // Logout is safe to exempt from CSRF: worst-case is a forced logout (no data change).
        // This prevents the 419 PAGE EXPIRED error when the user's session has already expired.
        $middleware->validateCsrfTokens(except: ['logout']);

        $middleware->alias([
            'role'                  => \App\Http\Middleware\RoleMiddleware::class,
            'force.password.change' => \App\Http\Middleware\ForcePasswordChange::class,
        ]);

        // Enforce password-reset page for first-login users on every authenticated web request.
        $middleware->appendToGroup('web', \App\Http\Middleware\ForcePasswordChange::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Redirect to login on any 419 (stale login form, idle session, etc.).
        // withErrors() is intentionally omitted — flashing to an expired session
        // can silently fail and cause the raw 419 page to appear anyway.
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            return redirect()->route('login');
        });
    })->create();
