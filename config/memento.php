<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo mode
    |--------------------------------------------------------------------------
    |
    | Lets anyone try the app without an account (spec section 6.9). When it
    | is on, registration is turned off, the login page offers a one-click
    | demo sign-in, and `demo:reset` rebuilds the demo account every night
    | from these details.
    |
    */

    'demo_mode' => (bool) env('DEMO_MODE', false),

    'demo' => [
        'name' => env('DEMO_NAME', 'Demo User'),
        'email' => env('DEMO_EMAIL', 'demo@example.com'),
        'password' => env('DEMO_PASSWORD', 'password'),
        'timezone' => env('DEMO_TIMEZONE', 'America/Chicago'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile
    |--------------------------------------------------------------------------
    |
    | Keeps bots out of registration and the demo sign-in. Local and test
    | environments use Cloudflare's published test keys, which always pass.
    | See https://developers.cloudflare.com/turnstile/troubleshooting/testing/
    |
    */

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
        'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | Sent in production (spec section 8.4). It is off by default elsewhere,
    | because the Vite dev server serves scripts from another origin. Set
    | CSP_ENABLED=true to try it locally against a production build.
    |
    */

    'content_security_policy' => (bool) env('CSP_ENABLED', env('APP_ENV') === 'production'),

];
