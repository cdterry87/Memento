<?php

use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Models\Mood;
use App\Models\Reason;
use App\Models\User;
use App\Services\RecapBuilder;
use Carbon\CarbonImmutable;
use Database\Seeders\LookupSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    CarbonImmutable::setTestNow('2026-11-07 12:00:00');
    $this->user = User::factory()->create(['timezone' => 'UTC']);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

/**
 * @param  list<string>  $reasons
 */
function entry(User $user, string $date, string $mood = 'good', array $reasons = [], ?string $body = null, bool $photo = false): Memory
{
    $memory = Memory::factory()->for($user)->create([
        'date' => $date,
        'mood_id' => Mood::where('slug', $mood)->sole()->id,
        'body' => $body,
    ]);

    $memory->reasons()->attach(Reason::whereIn('slug', $reasons)->pluck('id'));

    if ($photo) {
        MemoryPhoto::factory()->for($memory)->create();
    }

    return $memory;
}

/**
 * @return array<string, mixed>
 */
function recapFor(User $user, int $year): array
{
    return app(RecapBuilder::class)->build($user, $year, $user->today());
}

describe('pages', function () {
    it('opens the latest year with entries', function () {
        entry($this->user, '2024-05-01');
        entry($this->user, '2025-05-01');

        $this->actingAs($this->user)->get(route('recap'))->assertRedirect(route('recap.show', 2025));
    });

    it('opens this year when there are no entries yet', function () {
        $this->actingAs($this->user)->get(route('recap'))->assertRedirect(route('recap.show', 2026));

        $this->actingAs($this->user)
            ->get(route('recap.show', 2026))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Recap')
                ->where('recap.checkIns', 0)
                ->where('recap.isYearToDate', true)
                ->where('years', [])
            );
    });

    it('refuses a future year or a past year with no entries', function () {
        entry($this->user, '2024-05-01');

        $this->actingAs($this->user)->get(route('recap.show', 2027))->assertNotFound();
        $this->actingAs($this->user)->get(route('recap.show', 2023))->assertNotFound();
        $this->actingAs($this->user)->get(route('recap.show', 2024))->assertOk();
    });

    it("never counts or shows another person's entries", function () {
        $stranger = User::factory()->create(['timezone' => 'UTC']);
        entry($stranger, '2025-03-01', 'great', ['family'], 'Not yours', photo: true);
        entry($this->user, '2026-03-01');

        $this->actingAs($this->user)->get(route('recap.show', 2025))->assertNotFound();

        $this->actingAs($this->user)
            ->get(route('recap.show', 2026))
            ->assertDontSee('Not yours')
            ->assertInertia(fn (Assert $page) => $page
                ->where('recap.checkIns', 1)
                ->where('years', [2026])
            );
    });

    it('sends guests to sign in', function () {
        $this->get(route('recap'))->assertRedirect(route('login'));
        $this->get(route('recap.show', 2026))->assertRedirect(route('login'));
    });

    it('sends good memories as they appear elsewhere, with no database IDs', function () {
        entry($this->user, '2026-03-01', 'great', ['family'], 'Pancakes', photo: true);

        $this->actingAs($this->user)
            ->get(route('recap.show', 2026))
            ->assertInertia(fn (Assert $page) => $page
                ->has('recap.goodMemories.0', fn (Assert $memory) => $memory
                    ->where('date', '2026-03-01')
                    ->where('body', 'Pancakes')
                    ->has('mood')
                    ->has('reasons')
                    ->has('photos.0', fn (Assert $photo) => $photo->hasAll(['id', 'url', 'thumbnailUrl', 'width', 'height']))
                )
            );
    });
});

describe('the year in numbers', function () {
    it('counts check-ins, photos, and the average mood', function () {
        entry($this->user, '2026-01-10', 'great', photo: true);
        entry($this->user, '2026-02-10', 'okay', photo: true);
        entry($this->user, '2026-03-10', 'good');

        expect(recapFor($this->user, 2026))
            ->checkIns->toBe(3)
            ->photos->toBe(2)
            ->averageScore->toBe(4.0);
    });

    it('counts the year so far for this year, and the whole year for past years', function () {
        entry($this->user, '2025-06-01');
        entry($this->user, '2024-06-01');

        expect(recapFor($this->user, 2026))->daysInPeriod->toBe(311)->isYearToDate->toBeTrue()
            ->and(recapFor($this->user, 2025))->daysInPeriod->toBe(365)->isYearToDate->toBeFalse()
            ->and(recapFor($this->user, 2024)['daysInPeriod'])->toBe(366);
    });

    it('only includes days inside the year', function () {
        entry($this->user, '2025-12-31');
        entry($this->user, '2026-01-01');
        entry($this->user, '2026-12-31');

        expect(recapFor($this->user, 2025)['checkIns'])->toBe(1)
            ->and(recapFor($this->user, 2026)['checkIns'])->toBe(1);
    });

    it('finds the longest run of days in a row', function () {
        foreach (['2026-01-01', '2026-01-02', '2026-01-04', '2026-01-05', '2026-01-06', '2026-02-01'] as $date) {
            entry($this->user, $date);
        }

        expect(recapFor($this->user, 2026)['longestStreak'])->toBe(3);
    });

    it('counts a streak across February 29 in a leap year', function () {
        foreach (['2024-02-28', '2024-02-29', '2024-03-01'] as $date) {
            entry($this->user, $date);
        }

        expect(recapFor($this->user, 2024)['longestStreak'])->toBe(3);
    });

    it('counts a streak across the new year only within each year', function () {
        foreach (['2025-12-30', '2025-12-31', '2026-01-01'] as $date) {
            entry($this->user, $date);
        }

        expect(recapFor($this->user, 2025)['longestStreak'])->toBe(2)
            ->and(recapFor($this->user, 2026)['longestStreak'])->toBe(1);
    });

    it('works for a year with a single entry', function () {
        entry($this->user, '2025-07-04', 'great');

        expect(recapFor($this->user, 2025))
            ->checkIns->toBe(1)
            ->longestStreak->toBe(1)
            ->bestMonth->toBeNull()
            ->lifted->toBe([])
            ->and(recapFor($this->user, 2025)['goodMemories'])->toHaveCount(1);
    });

    it('has nothing to show for an empty year', function () {
        expect(recapFor($this->user, 2026))
            ->checkIns->toBe(0)
            ->longestStreak->toBe(0)
            ->averageScore->toBeNull()
            ->pixels->toBe([])
            ->moodMix->toBe([])
            ->bestMonth->toBeNull()
            ->and(recapFor($this->user, 2026)['goodMemories'])->toHaveCount(0);
    });
});

describe('charts', function () {
    it('colours each day by its mood score', function () {
        entry($this->user, '2026-03-01', 'great');

        expect(recapFor($this->user, 2026)['pixels'])->toBe([
            ['date' => '2026-03-01', 'score' => 5, 'emoji' => '😄', 'label' => 'Great'],
        ]);
    });

    it('averages each month up to today, leaving gaps for empty months', function () {
        entry($this->user, '2026-01-10', 'great');
        entry($this->user, '2026-01-11', 'okay');
        entry($this->user, '2026-03-10', 'good');

        $months = recapFor($this->user, 2026)['months'];

        expect($months)->toHaveCount(11)
            ->and($months[0])->toBe(['month' => 1, 'average' => 4.0, 'count' => 2])
            ->and($months[1])->toBe(['month' => 2, 'average' => null, 'count' => 0])
            ->and($months[2])->toBe(['month' => 3, 'average' => 4.0, 'count' => 1])
            ->and(recapFor($this->user, 2025)['months'])->toHaveCount(12);
    });

    it('shares out the moods, most common first', function () {
        entry($this->user, '2026-01-01', 'great');
        entry($this->user, '2026-01-02', 'great');
        entry($this->user, '2026-01-03', 'great');
        entry($this->user, '2026-01-04', 'okay');

        expect(recapFor($this->user, 2026)['moodMix'])->toBe([
            ['slug' => 'great', 'label' => 'Great', 'emoji' => '😄', 'score' => 5, 'count' => 3, 'share' => 0.75],
            ['slug' => 'okay', 'label' => 'Okay', 'emoji' => '😐', 'score' => 3, 'count' => 1, 'share' => 0.25],
        ]);
    });

    it('ranks the most chosen reasons, up to six', function () {
        $reasons = ['work', 'family', 'relationship', 'friends', 'school', 'travel', 'health'];

        foreach ($reasons as $index => $reason) {
            foreach (range(0, $index) as $day) {
                entry($this->user, sprintf('2026-%02d-%02d', $index + 1, $day + 1), 'good', [$reason]);
            }
        }

        expect(array_column(recapFor($this->user, 2026)['topReasons'], 'slug'))
            ->toBe(['health', 'travel', 'school', 'friends', 'relationship', 'family']);
    });
});

describe('highlights', function () {
    /**
     * Twenty days scoring 3, plus days with Family that score 5.
     */
    function yearWithFamilyDays(User $user, int $familyDays): void
    {
        foreach (range(1, 20) as $day) {
            entry($user, sprintf('2025-01-%02d', $day), 'okay', ['work']);
        }

        foreach (range(1, $familyDays) as $day) {
            entry($user, sprintf('2025-02-%02d', $day), 'great', ['family']);
        }
    }

    it('names the reasons on days that scored well above the year', function () {
        yearWithFamilyDays($this->user, 5);

        expect(recapFor($this->user, 2025)['lifted'])->toBe([
            ['slug' => 'family', 'label' => 'Family', 'emoji' => '🏠', 'average' => 5.0],
        ]);
    });

    it('waits for enough uses of a reason before saying it lifted you', function () {
        yearWithFamilyDays($this->user, 4);

        expect(recapFor($this->user, 2025)['lifted'])->toBe([]);
    });

    it('waits for enough check-ins before saying what lifted you', function () {
        foreach (range(1, 14) as $day) {
            entry($this->user, sprintf('2025-01-%02d', $day), 'okay', ['work']);
        }
        foreach (range(1, 5) as $day) {
            entry($this->user, sprintf('2025-02-%02d', $day), 'great', ['family']);
        }

        expect(recapFor($this->user, 2025)['lifted'])->toBe([]);
    });

    it('picks the best month only from months with enough check-ins', function () {
        foreach (range(1, 5) as $day) {
            entry($this->user, sprintf('2025-04-%02d', $day), 'good');
            entry($this->user, sprintf('2025-05-%02d', $day), 'great');
        }
        entry($this->user, '2025-06-01', 'great');

        expect(recapFor($this->user, 2025)['bestMonth'])->toBe(['month' => 5, 'average' => 5.0, 'count' => 5]);
    });

    it('chooses up to 12 good days, spread across the year, in date order', function () {
        foreach (range(1, 12) as $month) {
            entry($this->user, sprintf('2025-%02d-01', $month), 'great');
            entry($this->user, sprintf('2025-%02d-02', $month), 'good');
        }
        entry($this->user, '2025-01-03', 'okay', body: 'An okay day');

        $dates = recapFor($this->user, 2025)['goodMemories']->map(fn (Memory $memory) => $memory->date->toDateString())->all();

        expect($dates)->toHaveCount(12)
            ->and(collect($dates)->map(fn (string $date) => substr($date, 5, 2))->unique())->toHaveCount(12)
            ->and($dates)->toBe(collect($dates)->sort()->values()->all())
            ->and($dates)->not->toContain('2025-01-03');
    });

    it('prefers good days with a photo or a message', function () {
        entry($this->user, '2025-03-01', 'great');
        entry($this->user, '2025-03-02', 'good', body: 'A walk');
        entry($this->user, '2025-03-03', 'good', photo: true);
        foreach (range(4, 14) as $day) {
            entry($this->user, sprintf('2025-03-%02d', $day), 'great');
        }

        $dates = recapFor($this->user, 2025)['goodMemories']->map(fn (Memory $memory) => $memory->date->toDateString());

        expect($dates)->toHaveCount(12)
            ->toContain('2025-03-02')
            ->toContain('2025-03-03');
    });
});
