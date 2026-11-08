<?php

namespace App\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * A gentle nudge, sent at the user's chosen time when today has no entry yet.
 */
class CheckInReminder extends DailyEmail
{
    public function type(): EmailType
    {
        return EmailType::CheckInReminder;
    }

    protected function message(User $user): MailMessage
    {
        return (new MailMessage)
            ->subject('How was today?')
            ->greeting("Hi {$this->firstName($user)},")
            ->line("There's still time to check in for today. It only takes a moment.")
            ->action('Check in', route('today'));
    }
}
