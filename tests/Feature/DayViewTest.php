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
    CarbonImmutable::setTestNow('2026-11-07 12:00:00');
    $this->user = User::factory()->create(['timezone' => 'UTC']);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('shows a past day with everything on it', function () {
    $memory = Memory::factory()->for($this->user)->for(Mood::where('slug', 'great')->sole())->create([
        'date' => '2026-10-31',
        'body' => 'Carved pumpkins.',
    ]);
    $memory->reasons()->attach(Reason::where('slug', 'family')->sole());

    $this->actingAs($this->user)
        ->get(route('days.show', '2026-10-31'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Day')
            ->where('date', '2026-10-31')
            ->where('isToday', false)
            ->where('memory.mood.slug', 'great')
            ->where('memory.reasons.0.slug', 'family')
            ->where('memory.body', 'Carved pumpkins.')
        );
});

it('marks today as today', function () {
    Memory::factory()->for($this->user)->create(['date' => '2026-11-07']);

    $this->actingAs($this->user)
        ->get(route('days.show', '2026-11-07'))
        ->assertInertia(fn (Assert $page) => $page->where('isToday', true));
});

it('sends a day without an entry to the check-in', function () {
    $this->actingAs($this->user)
        ->get(route('days.show', '2026-10-31'))
        ->assertRedirect(route('days.check-in', '2026-10-31'));
});

it('rejects future and impossible dates', function (string $date) {
    $this->actingAs($this->user)->get("/days/{$date}")->assertNotFound();
})->with(['2026-11-08', '2026-02-30']);

it('returns to the day after saving a past day', function () {
    $this->actingAs($this->user)
        ->put(route('days.update', '2026-10-31'), ['mood' => 'good', 'reasons' => []])
        ->assertRedirect(route('days.show', '2026-10-31'))
        ->assertSessionHas('status', 'check-in-saved');
});

it('deletes an entry and its reasons', function () {
    $memory = Memory::factory()->for($this->user)->create(['date' => '2026-10-31']);
    $memory->reasons()->attach(Reason::where('slug', 'family')->sole());

    $this->actingAs($this->user)
        ->delete(route('days.destroy', '2026-10-31'))
        ->assertRedirect(route('timeline'))
        ->assertSessionHas('status', 'memory-deleted');

    expect($this->user->memories()->exists())->toBeFalse();
    $this->assertDatabaseMissing('memory_reason', ['memory_id' => $memory->id]);
});

it('only deletes the entry for that date', function () {
    Memory::factory()->for($this->user)->create(['date' => '2026-10-31']);
    Memory::factory()->for($this->user)->create(['date' => '2026-10-30']);

    $this->actingAs($this->user)->delete(route('days.destroy', '2026-10-31'));

    expect($this->user->memories()->pluck('date')->map->toDateString()->all())->toBe(['2026-10-30']);
});

it('returns not found when deleting a day without an entry', function () {
    $this->actingAs($this->user)
        ->delete(route('days.destroy', '2026-10-31'))
        ->assertNotFound();
});

it('lets a day be checked in again after deleting it', function () {
    Memory::factory()->for($this->user)->create(['date' => '2026-10-31']);

    $this->actingAs($this->user)->delete(route('days.destroy', '2026-10-31'));
    $this->actingAs($this->user)->put(route('days.update', '2026-10-31'), ['mood' => 'okay', 'reasons' => []]);

    expect($this->user->memories()->sole()->mood->slug)->toBe('okay');
});
