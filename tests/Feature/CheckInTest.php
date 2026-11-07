<?php

use App\Models\Memory;
use App\Models\Mood;
use App\Models\Reason;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\LookupSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
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
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function checkIn(array $overrides = []): array
{
    return ['mood' => 'great', 'reasons' => ['friends', 'health'], 'body' => 'Coffee with Sam.', ...$overrides];
}

it('offers the mood grid on today until there is an entry', function () {
    $this->actingAs($this->user)
        ->get(route('today'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Today')
            ->where('today', '2026-11-07')
            ->where('memory', null)
            ->has('moods', 12)
            ->where('moods.0', ['slug' => 'awful', 'label' => 'Awful', 'emoji' => '😭', 'score' => 1])
        );
});

it("shows today's entry once there is one", function () {
    $this->actingAs($this->user)->put(route('days.update', '2026-11-07'), checkIn());

    $this->actingAs($this->user)
        ->get(route('today'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('memory.mood.slug', 'great')
            ->where('memory.reasons.0.slug', 'friends')
            ->where('memory.body', 'Coffee with Sam.')
            ->where('moods', [])
        );
});

it('opens the check-in with the mood picked on today', function () {
    $this->actingAs($this->user)
        ->get(route('days.check-in', ['date' => '2026-11-07', 'mood' => 'great']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('CheckIn')
            ->where('date', '2026-11-07')
            ->where('isToday', true)
            ->where('initialMood', 'great')
            ->has('moods', 12)
            ->has('reasons', 11)
        );
});

it('ignores an unknown mood in the link', function () {
    $this->actingAs($this->user)
        ->get(route('days.check-in', ['date' => '2026-11-07', 'mood' => 'smug']))
        ->assertInertia(fn (Assert $page) => $page->where('initialMood', null));
});

it('saves a check-in and returns to today', function () {
    $this->actingAs($this->user)
        ->put(route('days.update', '2026-11-07'), checkIn())
        ->assertRedirect(route('today'))
        ->assertSessionHas('status', 'check-in-saved');

    $memory = $this->user->memories()->sole();

    expect($memory->date->toDateString())->toBe('2026-11-07')
        ->and($memory->mood->slug)->toBe('great')
        ->and($memory->reasons->pluck('slug')->all())->toBe(['friends', 'health'])
        ->and($memory->body)->toBe('Coffee with Sam.');
});

it('saves a mood on its own', function () {
    $this->actingAs($this->user)
        ->put(route('days.update', '2026-11-07'), ['mood' => 'okay', 'reasons' => [], 'body' => null])
        ->assertSessionHasNoErrors();

    expect($this->user->memories()->sole())
        ->body->toBeNull()
        ->reasons->toHaveCount(0);
});

it('updates the same entry when saving the same day again', function () {
    $this->actingAs($this->user)->put(route('days.update', '2026-11-07'), checkIn());
    $this->actingAs($this->user)->put(route('days.update', '2026-11-07'), checkIn([
        'mood' => 'okay',
        'reasons' => ['work'],
        'body' => 'Changed my mind.',
    ]));

    $memory = $this->user->memories()->sole();

    expect($memory->mood->slug)->toBe('okay')
        ->and($memory->reasons->pluck('slug')->all())->toBe(['work'])
        ->and($memory->body)->toBe('Changed my mind.');
});

it('enforces one entry per user per day in the database', function () {
    $memory = Memory::factory()->for($this->user)->create(['date' => '2026-11-01']);

    expect(fn () => Memory::factory()->for($this->user)->create(['date' => '2026-11-01', 'mood_id' => $memory->mood_id]))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('backfills any past day', function () {
    $this->actingAs($this->user)
        ->get(route('days.check-in', '2025-02-14'))
        ->assertInertia(fn (Assert $page) => $page->where('isToday', false));

    $this->actingAs($this->user)
        ->put(route('days.update', '2025-02-14'), checkIn())
        ->assertSessionHasNoErrors();

    expect($this->user->memories()->sole()->date->toDateString())->toBe('2025-02-14');
});

it('refuses future and impossible dates', function (string $date) {
    $this->actingAs($this->user)->get("/days/{$date}/check-in")->assertNotFound();
    $this->actingAs($this->user)->put("/days/{$date}", checkIn())->assertNotFound();

    expect($this->user->memories()->count())->toBe(0);
})->with([
    'tomorrow' => '2026-11-08',
    'next year' => '2027-01-01',
    'February 30' => '2026-02-30',
    'month 13' => '2026-13-01',
]);

it("decides today with the user's own time zone", function () {
    // At 12:00 UTC on November 7 it is already November 8 in Kiritimati (UTC+14).
    $early = User::factory()->create(['timezone' => 'Pacific/Kiritimati']);
    $late = User::factory()->create(['timezone' => 'Pacific/Pago_Pago']);

    $this->actingAs($early)->get(route('today'))->assertInertia(fn (Assert $page) => $page->where('today', '2026-11-08'));
    $this->actingAs($early)->put(route('days.update', '2026-11-08'), checkIn())->assertSessionHasNoErrors();

    $this->actingAs($late)->get(route('today'))->assertInertia(fn (Assert $page) => $page->where('today', '2026-11-07'));
    $this->actingAs($late)->put('/days/2026-11-08', checkIn())->assertNotFound();
});

it('requires a known mood', function (array $input) {
    $this->actingAs($this->user)
        ->put(route('days.update', '2026-11-07'), checkIn($input))
        ->assertSessionHasErrors('mood');
})->with([
    'missing' => [['mood' => null]],
    'unknown' => [['mood' => 'smug']],
]);

it('rejects unknown or repeated reasons', function () {
    $this->actingAs($this->user)
        ->put(route('days.update', '2026-11-07'), checkIn(['reasons' => ['work', 'work', 'gardening']]))
        ->assertSessionHasErrors(['reasons.0', 'reasons.2']);
});

it('accepts a 255-character message, counting each emoji as one', function () {
    $this->actingAs($this->user)
        ->put(route('days.update', '2026-11-07'), checkIn(['body' => str_repeat('😀', 255)]))
        ->assertSessionHasNoErrors();

    expect(mb_strlen($this->user->memories()->sole()->body))->toBe(255);
});

it('rejects a message over 255 characters', function () {
    $this->actingAs($this->user)
        ->put(route('days.update', '2026-11-07'), checkIn(['body' => str_repeat('a', 254).'😀😀']))
        ->assertSessionHasErrors('body');
});

it('stores a blank message as no message', function () {
    $this->actingAs($this->user)->put(route('days.update', '2026-11-07'), checkIn(['body' => '   ']));

    expect($this->user->memories()->sole()->body)->toBeNull();
});

it('shows a newly added mood and reason in the picker', function () {
    Mood::factory()->create(['slug' => 'proud', 'label' => 'Proud', 'position' => 99]);
    Reason::factory()->create(['slug' => 'pets', 'label' => 'Pets', 'position' => 99]);

    $this->actingAs($this->user)
        ->get(route('days.check-in', '2026-11-07'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('moods.12.slug', 'proud')
            ->where('reasons.11.slug', 'pets')
        );
});

it('hides a retired mood from the picker but keeps it on old entries', function () {
    $retired = Mood::factory()->retired()->create(['slug' => 'meh', 'label' => 'Meh']);
    Memory::factory()->for($this->user)->for($retired)->create(['date' => '2026-11-01']);

    $this->actingAs($this->user)
        ->get(route('days.check-in', '2026-11-07'))
        ->assertInertia(fn (Assert $page) => $page->has('moods', 12));

    $this->actingAs($this->user)
        ->put(route('days.update', '2026-11-07'), checkIn(['mood' => 'meh']))
        ->assertSessionHasErrors('mood');

    $this->actingAs($this->user)
        ->get(route('days.check-in', '2026-11-01'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('moods', 13)
            ->where('memory.mood.slug', 'meh')
        );

    $this->actingAs($this->user)
        ->put(route('days.update', '2026-11-01'), checkIn(['mood' => 'meh']))
        ->assertSessionHasNoErrors();
});

it('keeps a retired reason on the entry that already has it', function () {
    $retired = Reason::factory()->retired()->create(['slug' => 'commute']);
    $memory = Memory::factory()->for($this->user)->create(['date' => '2026-11-01']);
    $memory->reasons()->attach($retired);

    $this->actingAs($this->user)
        ->put(route('days.update', '2026-11-01'), checkIn(['reasons' => ['commute']]))
        ->assertSessionHasNoErrors();

    $this->actingAs($this->user)
        ->put(route('days.update', '2026-11-07'), checkIn(['reasons' => ['commute']]))
        ->assertSessionHasErrors('reasons.0');
});

it('never sends database IDs to the browser', function () {
    $this->actingAs($this->user)->put(route('days.update', '2026-11-07'), checkIn());

    $this->actingAs($this->user)
        ->get(route('today'))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('memory.id')
            ->missing('memory.user_id')
            ->missing('memory.mood.id')
        );
});
