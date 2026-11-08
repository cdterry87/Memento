<?php

use App\Models\User;
use App\Notifications\CheckInReminder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;

it('renders emails with the Memento theme, header, and footer', function () {
    $user = User::factory()->create();

    $html = (string) (new CheckInReminder)->toMail($user)->render();

    expect($html)->toContain('#6252e0')
        ->toContain(asset('icons/icon-192.png'))
        ->toContain(route('privacy'))
        ->toContain('— Memento')
        ->not->toContain('Regards,')
        ->not->toContain('laravel.com');
});

it("uses Memento's wording for the account emails", function () {
    $user = User::factory()->unverified()->create();

    $verify = (new VerifyEmail)->toMail($user);
    $reset = (new ResetPassword('a-token'))->toMail($user);

    expect($verify->subject)->toBe('Confirm your email address')
        ->and($verify->actionText)->toBe('Confirm email address')
        ->and($reset->subject)->toBe('Reset your Memento password')
        ->and($reset->actionUrl)->toContain('/reset-password/a-token')
        ->and(implode(' ', $reset->outroLines))->toContain('60 minutes');
});
