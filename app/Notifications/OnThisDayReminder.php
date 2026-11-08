<?php

namespace App\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Sent in the morning when the user has entries from this date in earlier
 * years. It says only that there are memories, never what they are.
 */
class OnThisDayReminder extends DailyEmail
{
    public function type(): EmailType
    {
        return EmailType::OnThisDay;
    }

    protected function message(User $user): MailMessage
    {
        return (new MailMessage)
            ->subject('You have memories from this day')
            ->greeting("Good morning, {$this->firstName($user)}")
            ->line('You wrote about this day in an earlier year. Take a moment to look back.')
            ->action('See your memories', route('today'));
    }
}
