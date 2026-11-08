<?php

use App\Enums\EmailType;
use App\Models\Memory;
use App\Models\Mood;
use App\Models\User;
use App\Notifications\CheckInReminder;
use App\Notifications\DailyEmail;
use App\Notifications\OnThisDayReminder;
use Carbon\CarbonImmutable;
use Database\Seeders\LookupSeeder;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\Mime\Email;

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    Notification::fake();
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

/**
 * Runs the hourly command as if it were this UTC time.
 */
function runEmailsAt(string $utc): void
{
    CarbonImmutable::setTestNow(CarbonImmutable::parse($utc, 'UTC'));
    test()->artisan('emails:send-daily')->assertSuccessful();
}

/**
 * @param  array<string, mixed>  $attributes
 */
function subscriber(array $attributes = []): User
{
    return User::factory()->create([
        'timezone' => 'America/Chicago',
        'reminder_emails' => true,
        'reminder_time' => '20:00',
        ...$attributes,
    ]);
}

function remindersSentTo(User $user): int
{
    return Notification::sent($user, CheckInReminder::class)->count();
}

describe('check-in reminder', function () {
    it('is off by default', function () {
        $user = User::factory()->create(['timezone' => 'UTC']);

        runEmailsAt('2026-11-07 20:00');

        expect($user->fresh()->reminder_emails)->toBeFalse()
            ->and($user->fresh()->on_this_day_emails)->toBeFalse();
        Notification::assertNothingSent();
    });

    it("goes out at the chosen hour in the person's own time zone", function () {
        $user = subscriber();

        // 01:00 UTC on November 7 is 19:00 on November 6 in Chicago.
        runEmailsAt('2026-11-07 01:00');
        expect(remindersSentTo($user))->toBe(0);

        runEmailsAt('2026-11-07 02:00');
        expect(remindersSentTo($user))->toBe(1);
    });

    it('is skipped on a day that already has an entry', function () {
        $user = subscriber();
        Memory::factory()->for($user)->create(['date' => '2026-11-06']);

        runEmailsAt('2026-11-07 02:00');

        Notification::assertNothingSent();
    });

    it('still goes out an hour or two late, but not later', function () {
        $late = subscriber();
        runEmailsAt('2026-11-07 04:00');
        expect(remindersSentTo($late))->toBe(1);

        $tooLate = subscriber(['reminder_time' => '17:00']);
        runEmailsAt('2026-11-07 02:00');
        expect(remindersSentTo($tooLate))->toBe(0);
    });

    it('goes out once a day, however often the command runs', function () {
        $user = subscriber();

        runEmailsAt('2026-11-07 02:00');
        runEmailsAt('2026-11-07 02:00');
        runEmailsAt('2026-11-07 03:00');

        expect(remindersSentTo($user))->toBe(1);

        runEmailsAt('2026-11-08 02:00');

        expect(remindersSentTo($user))->toBe(2);
    });

    it('goes out once when the clocks go back and the hour repeats', function () {
        $user = subscriber(['timezone' => 'America/New_York', 'reminder_time' => '01:00']);

        // 1 AM happens twice in New York on November 1, 2026: at 05:00 and 06:00 UTC.
        runEmailsAt('2026-11-01 05:00');
        runEmailsAt('2026-11-01 06:00');

        expect(remindersSentTo($user))->toBe(1);
    });

    it('still goes out when the clocks go forward and skip its hour', function () {
        $user = subscriber(['timezone' => 'America/New_York', 'reminder_time' => '02:00']);

        // 2 AM never happens in New York on March 8, 2026. 07:00 UTC is 3 AM.
        runEmailsAt('2026-03-08 07:00');

        expect(remindersSentTo($user))->toBe(1);
    });
});

describe('on this day email', function () {
    it('goes out at 8 AM local time when there are memories from this date', function () {
        $user = subscriber(['reminder_emails' => false, 'on_this_day_emails' => true]);
        Memory::factory()->for($user)->create(['date' => '2024-11-07']);

        runEmailsAt('2026-11-07 13:00');
        Notification::assertNothingSent();

        runEmailsAt('2026-11-07 14:00');
        Notification::assertSentToTimes($user, OnThisDayReminder::class, 1);
    });

    it('stays quiet when there is nothing from this date', function () {
        $user = subscriber(['reminder_emails' => false, 'on_this_day_emails' => true]);
        Memory::factory()->for($user)->create(['date' => '2025-11-06']);

        runEmailsAt('2026-11-07 14:00');

        Notification::assertNothingSent();
    });
});

describe('who gets email', function () {
    it('skips addresses that are not confirmed', function () {
        subscriber(['email_verified_at' => null, 'on_this_day_emails' => true]);

        runEmailsAt('2026-11-07 02:00');

        Notification::assertNothingSent();
    });

    it('never emails the demo account', function () {
        $demo = subscriber();
        $demo->forceFill(['is_demo' => true])->save();

        runEmailsAt('2026-11-07 02:00');

        Notification::assertNothingSent();
    });

    it('queues the emails instead of sending them during the run', function () {
        expect(new CheckInReminder)->toBeInstanceOf(ShouldQueue::class)
            ->and(new OnThisDayReminder)->toBeInstanceOf(ShouldQueue::class);
    });

    it('runs every hour', function () {
        $event = collect(app(Schedule::class)->events())
            ->first(fn (Event $event) => str_contains($event->command ?? '', 'emails:send-daily'));

        expect($event)->not->toBeNull()
            ->and($event->expression)->toBe('0 * * * *');
    });
});

describe('email content', function () {
    it('contains no journal content, only a link back', function (string $notification) {
        $user = User::factory()->create(['name' => 'Ada Lovelace']);
        $secret = 'A secret only the owner should ever see';
        Memory::factory()->for($user)->for(Mood::where('slug', 'great')->sole())->create([
            'date' => '2024-11-07',
            'body' => $secret,
        ]);

        /** @var DailyEmail $email */
        $email = new $notification;
        $html = (string) $email->toMail($user)->render();

        expect($html)
            ->toContain('Ada')
            ->toContain(route('today'))
            ->not->toContain($secret)
            ->not->toContain('Great')
            ->not->toContain('😄');
    })->with([
        'check-in reminder' => CheckInReminder::class,
        'on this day' => OnThisDayReminder::class,
    ]);

    it('offers one-click unsubscribe in the body and the headers', function () {
        $user = User::factory()->create();
        $mail = (new CheckInReminder)->toMail($user);
        $unsubscribeUrl = DailyEmail::unsubscribeUrl($user, EmailType::CheckInReminder);

        $message = new Email;
        foreach ($mail->callbacks as $callback) {
            $callback($message);
        }

        expect((string) $mail->render())->toContain(e($unsubscribeUrl))
            ->and($message->getHeaders()->get('List-Unsubscribe')?->getBodyAsString())->toBe("<{$unsubscribeUrl}>")
            ->and($message->getHeaders()->get('List-Unsubscribe-Post')?->getBodyAsString())->toBe('List-Unsubscribe=One-Click');
    });
});

describe('unsubscribe', function () {
    it('asks before turning emails off from the link', function () {
        $user = subscriber();
        $url = DailyEmail::unsubscribeUrl($user, EmailType::CheckInReminder);

        $this->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Unsubscribe')
                ->where('emailLabel', 'daily check-in reminders')
                ->where('isSubscribed', true)
                ->where('confirmUrl', $url)
            );

        expect($user->fresh()->reminder_emails)->toBeTrue();
    });

    it('turns off only that email when confirmed, signed in or not', function () {
        $user = subscriber(['on_this_day_emails' => true]);
        $url = DailyEmail::unsubscribeUrl($user, EmailType::CheckInReminder);

        $this->post($url, [], ['X-Inertia' => 'true'])->assertRedirect();

        expect($user->fresh())
            ->reminder_emails->toBeFalse()
            ->on_this_day_emails->toBeTrue();
    });

    it('accepts a one-click unsubscribe from a mail app', function () {
        $user = subscriber(['reminder_emails' => false, 'on_this_day_emails' => true]);
        $url = DailyEmail::unsubscribeUrl($user, EmailType::OnThisDay);

        $this->call('POST', $url, ['List-Unsubscribe' => 'One-Click'])->assertOk();

        expect($user->fresh()->on_this_day_emails)->toBeFalse();
    });

    it('refuses a link that is not signed or has been changed', function () {
        $user = subscriber();
        $other = subscriber();
        $url = DailyEmail::unsubscribeUrl($user, EmailType::CheckInReminder);

        $this->get("/unsubscribe/{$user->id}/check-in-reminder")->assertForbidden();
        $this->post(str_replace("/{$user->id}/", "/{$other->id}/", $url))->assertForbidden();
        $this->post(str_replace('check-in-reminder', 'on-this-day', $url))->assertForbidden();

        expect($user->fresh()->reminder_emails)->toBeTrue()
            ->and($other->fresh()->reminder_emails)->toBeTrue();
    });

    it('returns not found for an unknown email type, even when signed', function () {
        $user = subscriber();

        $this->get(URL::signedRoute('unsubscribe', ['user' => $user->id, 'type' => 'newsletter']))->assertNotFound();
    });
});

describe('account settings', function () {
    it('turns each email on and off and sets the reminder hour', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('account.preferences'), [
                'reminder_emails' => true,
                'reminder_time' => '07:00',
                'on_this_day_emails' => true,
            ])
            ->assertSessionHasNoErrors();

        expect($user->fresh())
            ->reminder_emails->toBeTrue()
            ->on_this_day_emails->toBeTrue()
            ->and(substr($user->fresh()->reminder_time, 0, 5))->toBe('07:00');

        $this->actingAs($user)
            ->get(route('account'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.reminderEmails', true)
                ->where('auth.user.reminderTime', '07:00')
                ->where('auth.user.onThisDayEmails', true)
            );
    });

    it('accepts only whole hours for the reminder', function (string $time) {
        $this->actingAs(User::factory()->create())
            ->patch(route('account.preferences'), ['reminder_time' => $time])
            ->assertSessionHasErrors('reminder_time');
    })->with(['07:30', '24:00', '7:00', 'evening']);
});
