<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
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
        ...$overrides,
    ];
}

it('shows the registration screen', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/Register'));
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
