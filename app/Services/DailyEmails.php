<?php

namespace App\Services;

use App\Models\NotificationSend;
use App\Models\User;
use App\Notifications\CheckInReminder;
use App\Notifications\DailyEmail;
use App\Notifications\OnThisDayReminder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Queues the daily emails that are due, run once an hour (spec section 6.8).
 *
 * An email is due from its local hour until a few hours after, so a missed
 * run or a skipped hour when the clocks go forward still sends it. The send
 * log allows one of each email per user per local date, so a repeated hour
 * when the clocks go back, or an overlapping run, never sends it twice.
 */
class DailyEmails
{
    /** The local hour the on-this-day email is sent. */
    public const ON_THIS_DAY_HOUR = 8;

    /** How many hours after its time an email can still go out. */
    public const SEND_WINDOW_HOURS = 3;

    public function __construct(private OnThisDay $onThisDay) {}

    /**
     * Queues every email due at this moment, and returns how many.
     */
    public function sendDue(CarbonImmutable $now): int
    {
        $sent = 0;

        $this->recipients()->chunkById(200, function ($users) use ($now, &$sent) {
            foreach ($users as $user) {
                $sent += $this->sendDueTo($user, $now);
            }
        });

        return $sent;
    }

    /**
     * Verified, real users who have turned on at least one email. The demo
     * account never receives email.
     *
     * @return Builder<User>
     */
    private function recipients(): Builder
    {
        return User::query()
            ->whereNotNull('email_verified_at')
            ->where('is_demo', false)
            ->where(fn (Builder $query) => $query->where('reminder_emails', true)->orWhere('on_this_day_emails', true));
    }

    private function sendDueTo(User $user, CarbonImmutable $now): int
    {
        $local = $now->setTimezone($user->timezone);
        $today = $local->startOfDay();
        $sent = 0;

        if ($user->reminder_emails
            && $this->isDue($local, $this->reminderHour($user))
            && ! $user->memories()->where('date', $today->toDateString())->exists()
        ) {
            $sent += $this->send($user, $today, new CheckInReminder);
        }

        if ($user->on_this_day_emails
            && $this->isDue($local, self::ON_THIS_DAY_HOUR)
            && $this->onThisDay->for($user, $today)->isNotEmpty()
        ) {
            $sent += $this->send($user, $today, new OnThisDayReminder);
        }

        return $sent;
    }

    private function isDue(CarbonImmutable $local, int $hour): bool
    {
        return $local->hour >= $hour && $local->hour < $hour + self::SEND_WINDOW_HOURS;
    }

    private function reminderHour(User $user): int
    {
        return (int) substr($user->reminder_time, 0, 2);
    }

    /**
     * Records the send first, so a second run in the same local day finds the
     * record and stops.
     */
    private function send(User $user, CarbonImmutable $today, DailyEmail $email): int
    {
        try {
            NotificationSend::create([
                'user_id' => $user->id,
                'type' => $email->type(),
                'local_date' => $today->toDateString(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return 0;
        }

        $user->notify($email);

        return 1;
    }
}
