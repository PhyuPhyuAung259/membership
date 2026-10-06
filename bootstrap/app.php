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
        //
        $middleware->trustProxies(at: '*');
        // Laravel's own 'can' middleware (aliased by the framework already)
        // is used for permission checks, not spatie's 'permission'/'role'
        // middleware — spatie's version checks the database directly and
        // would bypass AppServiceProvider's Gate::before bypass for Admin,
        // where 'can' goes through Gate and picks it up correctly.

        // Without this, 'auth:member' on a /portal/* route would still
        // bounce a guest to the staff /login page — Laravel's default
        // redirect target ignores which guard actually failed.
        $middleware->redirectGuestsTo(function ($request) {
            return $request->is('portal', 'portal/*') ? route('portal.login') : route('login');
        });

        // Same reasoning in reverse: an already-authenticated member hitting
        // /portal/login should land on their own dashboard, not staff's.
        $middleware->redirectUsersTo(function ($request) {
            return $request->is('portal', 'portal/*') ? route('portal.dashboard') : route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
