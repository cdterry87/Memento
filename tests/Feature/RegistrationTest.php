<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array<string, string>
 */
function registrationDetails(array $overrides = []): array
{
    return [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'a-long-enough-password',
        'password_confirmation' => 'a-long-enough-password',
        'timezone' => 'America/Chicago',
        'turnstile' => 'a-turnstile-token',
        ...$overrides,
    ];
}

beforeEach(function () {
    $this->turnstilePasses = true;
    Http::fake(['challenges.cloudflare.com/*' => fn () => Http::response(['success' => $this->turnstilePasses])]);
});

it('shows the registration screen', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Register')
            ->where('turnstileSiteKey', config('memento.turnstile.site_key'))
        );
});

it('refuses registration without a passing Turnstile check', function (array $override, bool $cloudflareSaysYes) {
    $this->turnstilePasses = $cloudflareSaysYes;

    $this->post(route('register.store'), registrationDetails($override))
        ->assertSessionHasErrors(['turnstile' => 'Please complete the security check and try again.']);

    expect(User::count())->toBe(0);
})->with([
    'no token' => [['turnstile' => ''], true],
    'rejected by Cloudflare' => [[], false],
]);

it('sends the token and visitor address to Cloudflare', function () {
    Notification::fake();

    $this->post(route('register.store'), registrationDetails(), ['REMOTE_ADDR' => '203.0.113.9']);

    Http::assertSent(fn ($request) => $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
        && $request['response'] === 'a-turnstile-token'
        && $request['remoteip'] === '203.0.113.9'
        && $request['secret'] === config('memento.turnstile.secret_key'));
});

it('registers a user with the time zone their browser reported', function () {
    Notification::fake();

    $this->post(route('register.store'), registrationDetails())
        ->assertRedirect(route('today'));

    $user = User::where('email', 'ada@example.com')->sole();

    expect($user->timezone)->toBe('America/Chicago')
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);
    $this->assertAuthenticatedAs($user);
});

it('falls back to UTC when the browser sends an unknown time zone', function () {
    $this->post(route('register.store'), registrationDetails(['timezone' => 'Mars/Olympus_Mons']));

    expect(User::where('email', 'ada@example.com')->sole()->timezone)->toBe('UTC');
});

it('requires a confirmed password and a unique email', function () {
    User::factory()->create(['email' => 'ada@example.com']);

    $this->post(route('register.store'), registrationDetails(['password_confirmation' => 'different']))
        ->assertSessionHasErrors(['email', 'password']);
});

it('never lets registration mark a user as the demo account', function () {
    $this->post(route('register.store'), registrationDetails(['is_demo' => true]));

    expect(User::where('email', 'ada@example.com')->sole()->is_demo)->toBeFalse();
});

it('throttles repeated registrations from one address', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('register.store'), registrationDetails(['email' => "ada{$attempt}@example.com"]));
        auth()->logout();
    }

    $this->post(route('register.store'), registrationDetails(['email' => 'ada6@example.com']))
        ->assertTooManyRequests();
});
