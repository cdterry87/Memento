<?php

use App\Models\Memory;
use App\Models\Mood;
use App\Models\Reason;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\LookupSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    $this->user = User::factory()->create();
});

/**
 * Creates one entry per day, counting back from the given date.
 */
function entriesBackFrom(User $user, string $from, int $days): void
{
    $start = CarbonImmutable::parse($from);
    $mood = Mood::where('slug', 'good')->sole();

    foreach (range(0, $days - 1) as $offset) {
        Memory::factory()->for($user)->for($mood)->create(['date' => $start->subDays($offset)->toDateString()]);
    }
}

it('shows an empty timeline before the first check-in', function () {
    $this->actingAs($this->user)
        ->get(route('timeline'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Timeline')
            ->has('memories.data', 0)
        );
});

it('lists only days with entries, newest first', function () {
    foreach (['2026-10-01', '2026-11-05', '2025-12-31'] as $date) {
        Memory::factory()->for($this->user)->create(['date' => $date]);
    }

    $this->actingAs($this->user)
        ->get(route('timeline'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('memories.data', 3)
            ->where('memories.data.0.date', '2026-11-05')
            ->where('memories.data.1.date', '2026-10-01')
            ->where('memories.data.2.date', '2025-12-31')
        );
});

it('shows the mood, reasons, and message on each entry', function () {
    $memory = Memory::factory()->for($this->user)->for(Mood::where('slug', 'great')->sole())->create([
        'date' => '2026-11-05',
        'body' => 'Picnic in the park.',
    ]);
    $memory->reasons()->attach(Reason::where('slug', 'friends')->sole());

    $this->actingAs($this->user)
        ->get(route('timeline'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('memories.data.0.mood.emoji', '😄')
            ->where('memories.data.0.reasons.0.slug', 'friends')
            ->where('memories.data.0.body', 'Picnic in the park.')
        );
});

it('loads entries 20 at a time with a cursor', function () {
    entriesBackFrom($this->user, '2026-11-07', 25);

    $first = $this->actingAs($this->user)->get(route('timeline'));

    $first->assertInertia(fn (Assert $page) => $page
        ->has('memories.data', 20)
        ->where('memories.data.0.date', '2026-11-07')
        ->where('memories.data.19.date', '2026-10-19')
    );

    $nextCursor = $first->viewData('page')['scrollProps']['memories']['nextPage'];

    expect($nextCursor)->toBeString();

    $last = $this->actingAs($this->user)->get(route('timeline', ['cursor' => $nextCursor]));

    $last->assertInertia(fn (Assert $page) => $page
        ->has('memories.data', 5)
        ->where('memories.data.0.date', '2026-10-18')
        ->where('memories.data.4.date', '2026-10-14')
    );

    expect($last->viewData('page')['scrollProps']['memories']['nextPage'])->toBeNull();
});

it("never lists someone else's entries", function () {
    Memory::factory()->for(User::factory())->create(['date' => '2026-11-05']);

    $this->actingAs($this->user)
        ->get(route('timeline'))
        ->assertInertia(fn (Assert $page) => $page->has('memories.data', 0));
});
