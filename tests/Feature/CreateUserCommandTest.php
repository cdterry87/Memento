<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates a verified account from the options and a password prompt', function () {
    $this->artisan('user:create', [
        '--name' => 'Chase Terry',
        '--email' => 'Chase@Example.com',
        '--timezone' => 'America/Chicago',
    ])
        ->expectsQuestion('Password', 'a-strong-password')
        ->assertSuccessful();

    $user = User::sole();

    expect($user->name)->toBe('Chase Terry')
        ->and($user->email)->toBe('chase@example.com')
        ->and($user->timezone)->toBe('America/Chicago')
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->is_demo)->toBeFalse()
        ->and(Hash::check('a-strong-password', $user->password))->toBeTrue();
});

it('asks for anything not given as an option', function () {
    $this->artisan('user:create')
        ->expectsQuestion('Name', 'Ada Lovelace')
        ->expectsQuestion('Email', 'ada@example.com')
        ->expectsQuestion('Time zone', 'Europe/London')
        ->expectsQuestion('Password', 'a-strong-password')
        ->assertSuccessful();

    expect(User::sole()->only('name', 'email', 'timezone'))->toBe([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'timezone' => 'Europe/London',
    ]);
});

it('works when registration is turned off', function () {
    config(['memento.demo_mode' => true]);

    $this->artisan('user:create', ['--name' => 'Owner', '--email' => 'owner@example.com', '--timezone' => 'UTC'])
        ->expectsQuestion('Password', 'a-strong-password')
        ->assertSuccessful();

    expect(User::where('email', 'owner@example.com')->exists())->toBeTrue();
});

it('can leave the email address to be verified', function () {
    $this->artisan('user:create', ['--name' => 'Owner', '--email' => 'owner@example.com', '--timezone' => 'UTC', '--unverified' => true])
        ->expectsQuestion('Password', 'a-strong-password')
        ->assertSuccessful();

    expect(User::sole()->hasVerifiedEmail())->toBeFalse();
});

it('refuses an email address that is already used', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->artisan('user:create', ['--name' => 'Owner', '--email' => 'taken@example.com', '--timezone' => 'UTC'])
        ->expectsQuestion('Password', 'a-strong-password')
        ->expectsOutputToContain('The email has already been taken.')
        ->assertFailed();

    expect(User::count())->toBe(1);
});

it('refuses an unknown time zone', function () {
    $this->artisan('user:create', ['--name' => 'Owner', '--email' => 'owner@example.com', '--timezone' => 'Mars/Olympus'])
        ->expectsQuestion('Password', 'a-strong-password')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

it('runs without prompts when every detail is passed', function () {
    $this->artisan('user:create', [
        '--name' => 'Owner',
        '--email' => 'owner@example.com',
        '--timezone' => 'UTC',
        '--password' => 'a-strong-password',
        '--no-interaction' => true,
    ])->assertSuccessful();

    expect(Hash::check('a-strong-password', User::sole()->password))->toBeTrue();
});

it('says what is missing when it cannot ask', function () {
    $this->artisan('user:create', ['--name' => 'Owner', '--no-interaction' => true])
        ->expectsOutputToContain('--email, --timezone, --password')
        ->assertFailed();

    expect(User::count())->toBe(0);
});
