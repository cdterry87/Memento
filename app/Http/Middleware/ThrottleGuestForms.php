<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limits registration and password reset requests. Fortify registers
 * these routes without a throttle, so this runs in the web group and acts
 * only on those routes.
 */
class ThrottleGuestForms
{
    /**
     * @var list<string>
     */
    private const array THROTTLED_ROUTES = ['register.store', 'password.email', 'password.update'];

    public function __construct(private ThrottleRequests $throttle) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST') && $request->routeIs(...self::THROTTLED_ROUTES)) {
            return $this->throttle->handle($request, $next, 'guest-forms');
        }

        return $next($request);
    }
}
