<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ThrottleGuestForms;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo('/today');

        /*
         * Mail apps post one-click unsubscribes with no session. The signed
         * URL is what protects the route.
         */
        $middleware->validateCsrfTokens(except: ['unsubscribe/*']);

        /*
         * AuthenticateSession signs out every other session, and every
         * "remember me" cookie, once the password changes, so a stolen
         * session ends when its owner changes or resets their password.
         */
        $middleware->web(append: [
            AuthenticateSession::class,
            SecurityHeaders::class,
            ThrottleGuestForms::class,
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
