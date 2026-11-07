<?php

namespace App\Models;

use App\Enums\Theme;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $timezone
 * @property Theme $theme
 * @property bool $show_quotes
 * @property bool $reminder_emails
 * @property string $reminder_time
 * @property bool $on_this_day_emails
 * @property bool $is_demo
 */
#[Fillable(['name', 'email', 'password', 'timezone', 'theme', 'show_quotes', 'reminder_emails', 'reminder_time', 'on_this_day_emails'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'theme' => Theme::class,
            'show_quotes' => 'boolean',
            'reminder_emails' => 'boolean',
            'on_this_day_emails' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }
}
