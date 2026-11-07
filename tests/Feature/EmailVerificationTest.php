<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

function verificationUrl(User $user, ?string $email = null): string
{
    return URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1($email ?? $user->email),
    ]);
}

it('keeps unverified users out of the journal', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('today'))
        ->assertRedirect(route('verification.notice'));
});

it('shows the verify email screen', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/VerifyEmail'));
});

it('verifies an email address from the signed link', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(verificationUrl($user))
        ->assertRedirect(route('today').'?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects a link for a different email address', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(verificationUrl($user, 'someone@example.com'))->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('sends the link again on request', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($user, VerifyEmail::class);
});
