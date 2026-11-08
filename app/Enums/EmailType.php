<?php

namespace App\Enums;

/**
 * The optional emails a user can turn on. Each maps to the user column that
 * holds the opt-in, and is used in unsubscribe links and the send log.
 */
enum EmailType: string
{
    case CheckInReminder = 'check-in-reminder';
    case OnThisDay = 'on-this-day';

    /**
     * The users column that records whether this email is on.
     */
    public function preference(): string
    {
        return match ($this) {
            self::CheckInReminder => 'reminder_emails',
            self::OnThisDay => 'on_this_day_emails',
        };
    }

    /**
     * How the email is described on the unsubscribe page.
     */
    public function label(): string
    {
        return match ($this) {
            self::CheckInReminder => 'daily check-in reminders',
            self::OnThisDay => 'on this day emails',
        };
    }
}
