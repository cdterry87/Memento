<?php

use App\Enums\Theme;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the account page with the list of time zones', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('account'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Account')
            ->has('timezones', count(DateTimeZone::listIdentifiers()))
        );
});

it('updates the name', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('user-profile-information.update'), ['name' => 'New Name', 'email' => $user->email])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->name)->toBe('New Name')
        ->and($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('asks for the new address to be verified after an email change', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('user-profile-information.update'), ['name' => $user->name, 'email' => 'new@example.com'])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->email)->toBe('new@example.com')
        ->and($user->fresh()->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('changes the password only with the current one', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'current_password' => 'wrong',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])
        ->assertSessionHasErrorsIn('updatePassword', 'current_password');

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])
        ->assertSessionHasNoErrors();

    expect(Hash::check('a-brand-new-password', $user->fresh()->password))->toBeTrue();
});

it('saves the theme preference', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('account.preferences'), ['theme' => 'dark'])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->theme)->toBe(Theme::Dark);
});

it('applies a saved theme on the first paint', function () {
    $user = User::factory()->create(['theme' => Theme::Dark]);

    $this->actingAs($user)
        ->get(route('today'))
        ->assertSee('data-theme="dark"', escape: false)
        ->assertDontSee("localStorage.getItem('memento.theme')", escape: false);
});

it('saves the time zone', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('account.preferences'), ['timezone' => 'Asia/Tokyo'])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->timezone)->toBe('Asia/Tokyo');
});

it('rejects an unknown theme or time zone', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('account.preferences'), ['theme' => 'purple', 'timezone' => 'Mars/Olympus_Mons'])
        ->assertSessionHasErrors(['theme', 'timezone']);
});

it('ignores fields that are not preferences', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('account.preferences'), [
        'theme' => 'light',
        'is_demo' => true,
        'email' => 'sneaky@example.com',
    ]);

    expect($user->fresh())
        ->is_demo->toBeFalse()
        ->email->not->toBe('sneaky@example.com');
});
