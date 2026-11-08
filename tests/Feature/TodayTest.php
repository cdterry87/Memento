<?php

use App\Models\Memory;
use App\Models\Quote;
use App\Models\User;
use App\Services\QuoteBackground;
use Carbon\CarbonImmutable;
use Database\Seeders\QuoteSeeder;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-11-07 12:00:00');
    $this->user = User::factory()->create(['timezone' => 'UTC']);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

/**
 * @param  list<string>  $dates
 */
function entriesOn(User $user, array $dates): void
{
    foreach ($dates as $date) {
        Memory::factory()->for($user)->create(['date' => $date]);
    }
}

/**
 * @return list<string>
 */
function onThisDayDates(User $user, string $now): array
{
    CarbonImmutable::setTestNow("{$now} 12:00:00");

    return collect(test()->actingAs($user)->get(route('today'))->viewData('page')['props']['onThisDay'])
        ->pluck('date')
        ->all();
}

describe('quote of the day', function () {
    it('shows the quote for the day of the year', function () {
        $quotes = Quote::factory()->count(5)->create();
        // November 7, 2026 is day 311, and 311 % 5 = 1.
        $expected = $quotes[1];

        $this->actingAs($this->user)
            ->get(route('today'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('quote', ['text' => $expected->text, 'author' => $expected->author])
            );
    });

    it('shows everyone the same quote, and a different one tomorrow', function () {
        Quote::factory()->count(5)->create();
        $other = User::factory()->create(['timezone' => 'UTC']);

        $quoteFor = fn (User $user) => $this->actingAs($user)->get(route('today'))->viewData('page')['props']['quote'];

        $today = $quoteFor($this->user);
        expect($quoteFor($other))->toBe($today);

        CarbonImmutable::setTestNow('2026-11-08 12:00:00');
        expect($quoteFor($this->user))->not->toBe($today);
    });

    it('hides the quote for someone who turned quotes off', function () {
        Quote::factory()->create();
        $this->user->update(['show_quotes' => false]);

        $this->actingAs($this->user)
            ->get(route('today'))
            ->assertInertia(fn (Assert $page) => $page->where('quote', null));
    });

    it('shows no quote when there are none', function () {
        $this->actingAs($this->user)
            ->get(route('today'))
            ->assertInertia(fn (Assert $page) => $page->where('quote', null));
    });

    it('seeds every quote from the data file, in order, without duplicates', function () {
        $this->seed(QuoteSeeder::class);
        $this->seed(QuoteSeeder::class);

        $quotes = require database_path('data/quotes.php');

        expect(Quote::orderBy('id')->pluck('text')->all())->toBe(array_column($quotes, 'text'));
    });

    it('turns quotes off and on from the account page', function () {
        $this->actingAs($this->user)
            ->patch(route('account.preferences'), ['show_quotes' => false])
            ->assertSessionHasNoErrors();

        expect($this->user->fresh()->show_quotes)->toBeFalse();

        $this->actingAs($this->user)
            ->get(route('account'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.user.showQuotes', false));

        $this->actingAs($this->user)->patch(route('account.preferences'), ['show_quotes' => true]);

        expect($this->user->fresh()->show_quotes)->toBeTrue();
    });

    it('rejects a quote setting that is not true or false', function () {
        $this->actingAs($this->user)
            ->patch(route('account.preferences'), ['show_quotes' => 'sometimes'])
            ->assertSessionHasErrors('show_quotes');
    });
});

describe('quote background', function () {
    beforeEach(function () {
        $this->directory = storage_path('framework/testing/quote-backgrounds');
        File::deleteDirectory($this->directory);
        File::ensureDirectoryExists($this->directory);
    });

    afterEach(function () {
        File::deleteDirectory($this->directory);
    });

    it('sends a photo from the backgrounds folder with the quote', function () {
        Quote::factory()->create();

        $this->actingAs($this->user)
            ->get(route('today'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('quoteBackground', fn (string $url) => str_starts_with($url, '/'.QuoteBackground::DIRECTORY.'/')
                    && File::exists(public_path(ltrim(rawurldecode($url), '/'))))
            );
    });

    it('sends no photo when quotes are hidden', function () {
        Quote::factory()->create();
        $this->user->update(['show_quotes' => false]);

        $this->actingAs($this->user)
            ->get(route('today'))
            ->assertInertia(fn (Assert $page) => $page->where('quoteBackground', null));
    });

    it('shows every image once before repeating, ignoring other files', function () {
        foreach (['beach1.jpg', 'beach2.jpg', 'forest1.webp', 'clouds 1.jpeg', 'notes.txt'] as $name) {
            File::put("{$this->directory}/{$name}", 'image');
        }

        $background = new QuoteBackground($this->directory);
        $start = CarbonImmutable::parse('2026-01-01');

        $cycle = collect(range(0, 3))->map(fn (int $day) => $background->forDate($start->addDays($day)));

        expect($cycle->unique())->toHaveCount(4)
            ->and($cycle->sort()->values()->all())->toBe([
                '/inspirational-quote-backgrounds/beach1.jpg',
                '/inspirational-quote-backgrounds/beach2.jpg',
                '/inspirational-quote-backgrounds/clouds%201.jpeg',
                '/inspirational-quote-backgrounds/forest1.webp',
            ])
            ->and($background->forDate($start->addDays(4)))->toBe($cycle[0]);
    });

    it('falls back to no photo when the folder is empty or missing', function () {
        expect((new QuoteBackground($this->directory))->forDate(CarbonImmutable::now()))->toBeNull()
            ->and((new QuoteBackground("{$this->directory}/missing"))->forDate(CarbonImmutable::now()))->toBeNull();
    });
});

describe('on this day', function () {
    it('shows this date from every earlier year, newest first', function () {
        entriesOn($this->user, ['2023-11-07', '2025-11-07', '2024-11-07']);

        expect(onThisDayDates($this->user, '2026-11-07'))->toBe(['2025-11-07', '2024-11-07', '2023-11-07']);
    });

    it("leaves out today's own entry and other dates", function () {
        entriesOn($this->user, ['2026-11-07', '2025-11-06', '2025-11-08', '2025-10-07', '2025-11-07']);

        expect(onThisDayDates($this->user, '2026-11-07'))->toBe(['2025-11-07']);
    });

    it("never shows another person's entries", function () {
        entriesOn(User::factory()->create(), ['2025-11-07']);

        expect(onThisDayDates($this->user, '2026-11-07'))->toBe([]);
    });

    it('includes February 29 on February 28 in a year without one', function () {
        entriesOn($this->user, ['2024-02-29', '2024-02-28', '2025-02-28']);

        expect(onThisDayDates($this->user, '2027-02-28'))->toBe(['2025-02-28', '2024-02-29', '2024-02-28']);
    });

    it('keeps February 29 for its own day in a leap year', function () {
        entriesOn($this->user, ['2024-02-29', '2027-02-28']);

        expect(onThisDayDates($this->user, '2028-02-28'))->toBe(['2027-02-28'])
            ->and(onThisDayDates($this->user, '2028-02-29'))->toBe(['2024-02-29']);
    });

    it("uses the person's own date", function () {
        // At 12:00 UTC on November 7 it is already November 8 in Kiritimati (UTC+14).
        $this->user->update(['timezone' => 'Pacific/Kiritimati']);
        entriesOn($this->user, ['2025-11-07', '2025-11-08']);

        expect(onThisDayDates($this->user, '2026-11-07'))->toBe(['2025-11-08']);
    });

    it('sends the entry as it appears on the timeline, with no database IDs', function () {
        entriesOn($this->user, ['2025-11-07']);

        $this->actingAs($this->user)
            ->get(route('today'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('onThisDay.0', fn (Assert $memory) => $memory
                    ->where('date', '2025-11-07')
                    ->has('mood')
                    ->has('reasons')
                    ->has('body')
                    ->has('photos')
                )
            );
    });
});

describe('recap promotion', function () {
    it('promotes a year with entries from December 26 to January 31', function (string $now, ?int $year) {
        entriesOn($this->user, ['2026-03-01']);
        CarbonImmutable::setTestNow("{$now} 12:00:00");

        $this->actingAs($this->user)
            ->get(route('today'))
            ->assertInertia(fn (Assert $page) => $page->where('recapYear', $year));
    })->with([
        'December 25' => ['2026-12-25', null],
        'December 26' => ['2026-12-26', 2026],
        'December 31' => ['2026-12-31', 2026],
        'January 1' => ['2027-01-01', 2026],
        'January 31' => ['2027-01-31', 2026],
        'February 1' => ['2027-02-01', null],
    ]);

    it('skips a year with no entries', function () {
        entriesOn($this->user, ['2025-06-01']);
        CarbonImmutable::setTestNow('2026-12-28 12:00:00');

        $this->actingAs($this->user)
            ->get(route('today'))
            ->assertInertia(fn (Assert $page) => $page->where('recapYear', null));
    });
});
