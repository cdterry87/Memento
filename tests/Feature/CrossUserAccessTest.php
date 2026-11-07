<?php

use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\LookupSeeder;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
 * Every journal record is reached through the signed-in user, so another
 * person's entry can never be read or changed. These tests walk every
 * signed-in route, so a new route is covered as soon as it is added.
 */

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    CarbonImmutable::setTestNow('2026-11-07 12:00:00');

    $this->owner = User::factory()->create();
    $this->stranger = User::factory()->create();

    $this->secret = 'A secret only the owner should ever see';
    $this->ownersMemory = Memory::factory()->for($this->owner)->create([
        'date' => '2026-11-07',
        'body' => $this->secret,
    ]);

    Storage::fake('media');
    $this->ownersPhoto = MemoryPhoto::factory()->for($this->ownersMemory)->create();
    Storage::disk('media')->put($this->ownersPhoto->path, 'photo');
    Storage::disk('media')->put($this->ownersPhoto->thumbnail_path, 'thumbnail');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

/**
 * @return list<RoutingRoute>
 */
function signedInRoutes(): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route) => in_array('verified', $route->gatherMiddleware(), true))
        ->values()
        ->all();
}

/**
 * Fills a route's parameters with the owner's records.
 */
function urlFor(RoutingRoute $route): string
{
    $values = [
        '{date}' => '2026-11-07',
        '{photo}' => test()->ownersPhoto->ulid,
        '{variant}' => 'thumb',
        '{year}' => '2026',
    ];

    $unknown = array_diff($route->parameterNames(), array_map(fn ($key) => trim($key, '{}'), array_keys($values)));

    if ($unknown !== []) {
        throw new RuntimeException("Add a cross-user case for {$route->uri()}, which takes: ".implode(', ', $unknown));
    }

    return '/'.ltrim(strtr($route->uri(), $values), '/');
}

it("returns not found for every route that names one of the owner's photos", function () {
    $routes = collect(signedInRoutes())->filter(fn (RoutingRoute $route) => in_array('photo', $route->parameterNames(), true));

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        $method = strtolower(collect($route->methods())->first());

        $this->actingAs($this->stranger)->{$method}(urlFor($route))->assertNotFound();
    }

    expect($this->ownersPhoto->fresh())->not->toBeNull();
    Storage::disk('media')->assertExists([$this->ownersPhoto->path, $this->ownersPhoto->thumbnail_path]);
});

it("never shows the owner's entry to anyone else", function () {
    $routes = collect(signedInRoutes())->filter(fn (RoutingRoute $route) => in_array('GET', $route->methods(), true));

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        $this->actingAs($this->stranger)
            ->get(urlFor($route))
            ->assertDontSee($this->secret);
    }
});

it("never changes the owner's entry from anyone else's request", function () {
    $routes = collect(signedInRoutes())->reject(fn (RoutingRoute $route) => in_array('GET', $route->methods(), true));

    foreach ($routes as $route) {
        $method = strtolower(collect($route->methods())->first());

        $this->actingAs($this->stranger)->{$method}(urlFor($route), [
            'mood' => 'awful',
            'reasons' => ['work'],
            'body' => 'Overwritten',
        ]);
    }

    $memory = $this->ownersMemory->fresh();

    expect($memory->body)->toBe($this->secret)
        ->and($memory->mood_id)->toBe($this->ownersMemory->mood_id)
        ->and($memory->reasons)->toHaveCount(0)
        ->and($memory->photos)->toHaveCount(1);
    Storage::disk('media')->assertExists([$this->ownersPhoto->path, $this->ownersPhoto->thumbnail_path]);
});

it("gives a stranger their own blank day at the owner's date", function () {
    $this->actingAs($this->stranger)
        ->get('/days/2026-11-07/check-in')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('memory', null));

    $this->actingAs($this->stranger)
        ->put('/days/2026-11-07', ['mood' => 'okay', 'reasons' => ['work'], 'body' => 'Mine'])
        ->assertSessionHasNoErrors();

    expect($this->stranger->memories()->sole()->body)->toBe('Mine')
        ->and($this->owner->memories()->sole()->body)->toBe($this->secret);
});

it("refuses to delete the owner's entry for anyone else", function () {
    $this->actingAs($this->stranger)
        ->delete('/days/2026-11-07')
        ->assertNotFound();

    expect($this->ownersMemory->fresh())->not->toBeNull();
});

it("sends a stranger to their own check-in at the owner's date", function () {
    $this->actingAs($this->stranger)
        ->get('/days/2026-11-07')
        ->assertRedirect('/days/2026-11-07/check-in');
});
