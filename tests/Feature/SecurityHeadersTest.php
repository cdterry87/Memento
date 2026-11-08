<?php

use App\Models\User;

it('sends hardening headers on every page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('sends a content security policy with a nonce the page uses', function () {
    config([
        'memento.content_security_policy' => true,
        'filesystems.disks.media.endpoint' => 'https://account.r2.cloudflarestorage.com',
        'filesystems.disks.media.public_endpoint' => null,
    ]);

    $response = $this->get(route('login'))->assertOk();
    $policy = (string) $response->headers->get('Content-Security-Policy');

    preg_match("/'nonce-([^']+)'/", $policy, $matches);

    expect($matches)->toHaveCount(2)
        ->and($policy)->toContain("default-src 'self'")
        ->toContain('https://challenges.cloudflare.com')
        ->toContain('img-src \'self\' data: blob: https://account.r2.cloudflarestorage.com')
        ->toContain("frame-ancestors 'none'")
        ->and($response->getContent())->toContain('nonce="'.$matches[1].'"');
});

it('leaves the policy off when it is turned off', function () {
    config(['memento.content_security_policy' => false]);

    $this->actingAs(User::factory()->create())
        ->get(route('today'))
        ->assertHeaderMissing('Content-Security-Policy');
});

it('sends HSTS only on secure production requests', function () {
    $this->get('https://localhost/login')->assertHeaderMissing('Strict-Transport-Security');

    app()->detectEnvironment(fn () => 'production');

    $this->get('https://localhost/login')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});
