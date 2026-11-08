<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Production hardening from spec section 8.4: a Content Security Policy,
 * HSTS on secure requests, and headers that stop framing, sniffing, and
 * indexing. The policy is off in local development, where scripts come
 * from the Vite dev server.
 */
class SecurityHeaders
{
    /**
     * Cloudflare Turnstile's script and challenge frame.
     */
    private const TURNSTILE_ORIGIN = 'https://challenges.cloudflare.com';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = config('memento.content_security_policy') ? Vite::useCspNonce() : null;

        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $headers->set('X-Robots-Tag', 'noindex, nofollow');

        if ($request->isSecure() && app()->isProduction()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($nonce !== null) {
            $headers->set('Content-Security-Policy', $this->policy($nonce));
        }

        return $response;
    }

    /**
     * Styles allow inline rules because Vuetify writes its theme into a
     * style tag and uses style attributes. Scripts don't: each one needs
     * the nonce or must come from this site or Turnstile.
     */
    private function policy(string $nonce): string
    {
        $media = $this->mediaOrigin();
        [$dev, $devSocket] = $this->viteDevOrigins();

        return implode('; ', [
            "default-src 'self'",
            trim("script-src 'self' 'nonce-{$nonce}' ".self::TURNSTILE_ORIGIN." {$dev}"),
            trim("style-src 'self' 'unsafe-inline' {$dev}"),
            trim("img-src 'self' data: blob: {$media} {$dev}"),
            trim("font-src 'self' data: {$dev}"),
            trim("connect-src 'self' {$dev} {$devSocket}"),
            'frame-src '.self::TURNSTILE_ORIGIN,
            "frame-ancestors 'none'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);
    }

    /**
     * While the Vite dev server runs, scripts, styles, and fonts come from
     * it and hot reloading uses its WebSocket. Production never runs hot.
     *
     * @return array{string, string}
     */
    private function viteDevOrigins(): array
    {
        if (! Vite::isRunningHot()) {
            return ['', ''];
        }

        $origin = rtrim((string) file_get_contents(Vite::hotFile()));

        return [$origin, (string) preg_replace('/^http/', 'ws', $origin)];
    }

    /**
     * Photos are redirected to signed URLs on the media host, so images
     * must be allowed from there.
     */
    private function mediaOrigin(): string
    {
        $endpoint = config('filesystems.disks.media.public_endpoint') ?: config('filesystems.disks.media.endpoint');

        if (! is_string($endpoint) || $endpoint === '') {
            return '';
        }

        $parts = parse_url($endpoint);

        if (! isset($parts['scheme'], $parts['host'])) {
            return '';
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
