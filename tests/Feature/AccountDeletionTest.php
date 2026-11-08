<?php

use App\Models\DataExport;
use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Models\Reason;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\LookupSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    CarbonImmutable::setTestNow('2026-11-07 12:00:00');
    Storage::fake('media');

    $this->user = User::factory()->create();
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

/**
 * Gives the user an entry with a photo, and a ready export, with real files.
 *
 * @return list<string> the paths of the files on the media disk
 */
function fillAccount(User $user): array
{
    $memory = Memory::factory()->for($user)->create(['date' => '2026-11-06']);
    $memory->reasons()->attach(Reason::first());
    $photo = MemoryPhoto::factory()->for($memory)->create();
    $export = DataExport::factory()->for($user)->ready()->create();

    $paths = [$photo->path, $photo->thumbnail_path, (string) $export->path];

    foreach ($paths as $path) {
        Storage::disk('media')->put($path, 'contents');
    }

    return $paths;
}

it('deletes the account, every row, and every file, then signs out', function () {
    $paths = fillAccount($this->user);

    $this->actingAs($this->user)
        ->delete(route('account.destroy'), ['password' => 'password'])
        ->assertRedirect(route('home'))
        ->assertSessionHas('status', 'account-deleted');

    $this->assertGuest();
    expect(User::find($this->user->id))->toBeNull()
        ->and(Memory::count())->toBe(0)
        ->and(MemoryPhoto::count())->toBe(0)
        ->and(DataExport::count())->toBe(0)
        ->and(DB::table('memory_reason')->count())->toBe(0)
        ->and(Storage::disk('media')->allFiles())->toBe([]);

    foreach ($paths as $path) {
        Storage::disk('media')->assertMissing($path);
    }
});

it("leaves everyone else's data alone", function () {
    $other = User::factory()->create();
    $othersPaths = fillAccount($other);
    fillAccount($this->user);

    $this->actingAs($this->user)->delete(route('account.destroy'), ['password' => 'password']);

    expect($other->fresh())->not->toBeNull()
        ->and($other->memories()->count())->toBe(1)
        ->and($other->dataExports()->count())->toBe(1);
    Storage::disk('media')->assertExists($othersPaths);
});

it('needs the current password', function (?string $password) {
    fillAccount($this->user);

    $this->actingAs($this->user)
        ->delete(route('account.destroy'), ['password' => $password])
        ->assertSessionHasErrorsIn('deleteAccount', 'password');

    $this->assertAuthenticatedAs($this->user);
    expect($this->user->fresh())->not->toBeNull()
        ->and($this->user->memories()->count())->toBe(1);
})->with([
    'missing' => [null],
    'wrong' => ['not-my-password'],
]);

it('refuses to delete the demo account', function () {
    $demo = User::factory()->create(['is_demo' => true]);
    fillAccount($demo);

    $this->actingAs($demo)
        ->delete(route('account.destroy'), ['password' => 'password'])
        ->assertSessionHasErrorsIn('deleteAccount', ['password' => "The demo account can't be deleted."]);

    expect($demo->fresh())->not->toBeNull()
        ->and($demo->memories()->count())->toBe(1);
});

it('ends sessions on other devices', function () {
    config(['session.driver' => 'database']);
    DB::table('sessions')->insert([
        'id' => 'other-device',
        'user_id' => $this->user->id,
        'payload' => '',
        'last_activity' => now()->getTimestamp(),
    ]);

    $this->actingAs($this->user)->delete(route('account.destroy'), ['password' => 'password']);

    expect(DB::table('sessions')->where('user_id', $this->user->id)->exists())->toBeFalse();
});
