<?php

use App\Jobs\BuildDataExport;
use App\Models\DataExport;
use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Models\Mood;
use App\Models\Reason;
use App\Models\User;
use App\Notifications\DataExportReady;
use Carbon\CarbonImmutable;
use Database\Seeders\LookupSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    CarbonImmutable::setTestNow('2026-11-07 12:00:00');
    Storage::fake('media');
    Notification::fake();

    $this->user = User::factory()->create(['name' => 'Ada Lovelace']);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

/**
 * Opens the user's built export and returns its JSON and file list.
 *
 * @return array{json: array<string, mixed>, files: list<string>, contents: array<string, string>}
 */
function openExport(DataExport $export): array
{
    $local = storage_path('framework/testing/'.$export->ulid.'.zip');
    File::put($local, Storage::disk('media')->get((string) $export->path));

    $zip = new ZipArchive;
    $zip->open($local);

    $files = [];
    $contents = [];

    for ($index = 0; $index < $zip->numFiles; $index++) {
        $name = (string) $zip->getNameIndex($index);
        $files[] = $name;
        $contents[$name] = (string) $zip->getFromName($name);
    }

    $zip->close();
    File::delete($local);

    sort($files);

    return ['json' => json_decode($contents['memento.json'], true), 'files' => $files, 'contents' => $contents];
}

function photoFor(Memory $memory, string $contents, int $position = 1): MemoryPhoto
{
    $photo = MemoryPhoto::factory()->for($memory)->create(['position' => $position]);
    Storage::disk('media')->put($photo->path, $contents);
    Storage::disk('media')->put($photo->thumbnail_path, "{$contents} thumbnail");

    return $photo;
}

it('queues an export when asked', function () {
    Queue::fake();

    $this->actingAs($this->user)
        ->post(route('account.export'))
        ->assertRedirect()
        ->assertSessionHas('status', 'export-requested');

    $export = $this->user->dataExports()->sole();
    expect($export->isPending())->toBeTrue();
    Queue::assertPushed(BuildDataExport::class, fn (BuildDataExport $job) => $job->export->is($export));
});

it("builds a ZIP of all the user's entries and photos, and only theirs", function () {
    $great = Mood::where('slug', 'great')->sole();
    $memory = Memory::factory()->for($this->user)->create([
        'date' => '2026-11-06',
        'mood_id' => $great->id,
        'body' => 'Pancakes and a walk 🥞',
    ]);
    $memory->reasons()->attach(Reason::whereIn('slug', ['family', 'friends'])->pluck('id'));
    photoFor($memory, 'first photo', 1);
    photoFor($memory, 'second photo', 2);
    Memory::factory()->for($this->user)->create(['date' => '2026-10-01', 'body' => null]);

    $stranger = User::factory()->create();
    $strangersMemory = Memory::factory()->for($stranger)->create(['date' => '2026-11-06', 'body' => 'Not yours']);
    photoFor($strangersMemory, 'stranger photo');

    $this->actingAs($this->user)->post(route('account.export'));

    $export = $this->user->dataExports()->sole();
    expect($export->isReady())->toBeTrue()
        ->and($export->expires_at->equalTo(now()->addHours(24)))->toBeTrue();

    ['json' => $json, 'files' => $files, 'contents' => $contents] = openExport($export);

    expect($files)->toBe([
        'README.txt',
        'memento.json',
        'photos/2026-11-06-1.jpg',
        'photos/2026-11-06-2.jpg',
    ])
        ->and($contents['photos/2026-11-06-1.jpg'])->toBe('first photo')
        ->and($contents['photos/2026-11-06-2.jpg'])->toBe('second photo')
        ->and($json['account']['name'])->toBe('Ada Lovelace')
        ->and($json['account']['email'])->toBe($this->user->email)
        ->and($json['account'])->not->toHaveKey('password')
        ->and($json['entries'])->toHaveCount(2)
        ->and($json['entries'][0]['date'])->toBe('2026-10-01')
        ->and($json['entries'][1])->toMatchArray([
            'date' => '2026-11-06',
            'message' => 'Pancakes and a walk 🥞',
            'photos' => ['photos/2026-11-06-1.jpg', 'photos/2026-11-06-2.jpg'],
        ])
        ->and($json['entries'][1]['mood']['slug'])->toBe('great')
        ->and(array_column($json['entries'][1]['reasons'], 'slug'))->toBe(['family', 'friends'])
        ->and($contents['memento.json'])->not->toContain('Not yours')
        ->and(implode('', $contents))->not->toContain('stranger photo');
});

it('emails a signed link, with none of the journal in it', function () {
    Memory::factory()->for($this->user)->create(['date' => '2026-11-06', 'body' => 'A private thought']);

    $this->actingAs($this->user)->post(route('account.export'));

    $export = $this->user->dataExports()->sole();

    Notification::assertSentTo($this->user, DataExportReady::class, function (DataExportReady $notification) use ($export) {
        $mail = $notification->toMail($this->user);
        $rendered = (string) $mail->render();

        return $notification->export->is($export)
            && str_contains($mail->actionUrl, '/account/export/'.$export->ulid)
            && str_contains($mail->actionUrl, 'signature=')
            && ! str_contains($rendered, 'A private thought');
    });
});

it('leaves no working files behind on the server', function () {
    $memory = Memory::factory()->for($this->user)->create(['date' => '2026-11-06']);
    photoFor($memory, 'photo');

    $this->actingAs($this->user)->post(route('account.export'));

    expect(File::isDirectory(storage_path('app/tmp/exports')) ? File::allFiles(storage_path('app/tmp/exports')) : [])->toBe([]);
});

it('redirects the owner to a short-lived link for a ready export', function () {
    $export = DataExport::factory()->for($this->user)->ready()->create();

    $response = $this->actingAs($this->user)->get(DataExportReady::downloadUrl($export));

    $response->assertRedirect()->assertHeader('Cache-Control', 'no-store, private');
    expect($response->headers->get('Location'))->toContain($export->path);
});

it('refuses a download link without a valid signature', function () {
    $export = DataExport::factory()->for($this->user)->ready()->create();

    $this->actingAs($this->user)
        ->get(route('account.export.download', $export->ulid))
        ->assertForbidden();
});

it('refuses an expired download link', function () {
    $export = DataExport::factory()->for($this->user)->ready()->create();
    $url = DataExportReady::downloadUrl($export);

    $this->travel(25)->hours();

    $this->actingAs($this->user)->get($url)->assertForbidden();
});

it("returns not found for someone else's export, even with its signed link", function () {
    $export = DataExport::factory()->for($this->user)->ready()->create();

    $this->actingAs(User::factory()->create())
        ->get(DataExportReady::downloadUrl($export))
        ->assertNotFound();
});

it('asks guests to sign in before downloading', function () {
    $export = DataExport::factory()->for($this->user)->ready()->create();

    $this->get(DataExportReady::downloadUrl($export))->assertRedirect(route('login'));
});

it('refuses a second export while one is being made', function () {
    Queue::fake();
    DataExport::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->post(route('account.export'))
        ->assertSessionHasErrors('export');

    Queue::assertNothingPushed();
});

it('replaces the previous export, and its file, with a new one', function () {
    $old = DataExport::factory()->for($this->user)->ready()->create();
    Storage::disk('media')->put($old->path, 'old zip');

    $this->actingAs($this->user)->post(route('account.export'));

    expect(DataExport::find($old->id))->toBeNull()
        ->and($this->user->dataExports()->sole()->isReady())->toBeTrue();
    Storage::disk('media')->assertMissing($old->path);
});

it('shows where the latest export stands on the account page', function (?string $state, ?string $status) {
    if ($state !== null) {
        DataExport::factory()->for($this->user)->{$state}()->create();
    }

    $this->actingAs($this->user)
        ->get(route('account'))
        ->assertInertia(fn (Assert $page) => $status === null
            ? $page->where('dataExport', null)->etc()
            : $page->where('dataExport.status', $status)->etc());
})->with([
    'none' => [null, null],
    'ready' => ['ready', 'ready'],
    'expired' => ['expired', null],
]);

it('marks an export as failed when the job fails', function () {
    $export = DataExport::factory()->for($this->user)->create();

    (new BuildDataExport($export))->failed(new RuntimeException('Out of disk'));

    expect($export->fresh()->failed_at)->not->toBeNull()
        ->and($export->fresh()->isPending())->toBeFalse();

    $this->actingAs($this->user)
        ->get(route('account'))
        ->assertInertia(fn (Assert $page) => $page->where('dataExport.status', 'failed')->etc());
});

it('prunes expired exports and their files, and keeps working ones', function () {
    $expired = DataExport::factory()->for($this->user)->expired()->create();
    Storage::disk('media')->put($expired->path, 'expired zip');
    $ready = DataExport::factory()->for($this->user)->ready()->create();
    Storage::disk('media')->put($ready->path, 'ready zip');
    $lost = DataExport::factory()->for($this->user)->create(['created_at' => now()->subHours(3)]);

    $this->artisan('model:prune', ['--model' => [DataExport::class]])->assertSuccessful();

    expect(DataExport::pluck('id')->all())->toBe([$ready->id]);
    Storage::disk('media')->assertMissing($expired->path);
    Storage::disk('media')->assertExists($ready->path);
    expect($lost->fresh())->toBeNull();
});

it('refuses exports for the demo account', function () {
    Queue::fake();
    $demo = User::factory()->create(['is_demo' => true]);

    $this->actingAs($demo)
        ->post(route('account.export'))
        ->assertSessionHasErrors(['export' => 'Exports are turned off in the demo.']);

    expect($demo->dataExports()->exists())->toBeFalse();
    Queue::assertNothingPushed();
});

it('limits how many exports can be asked for in a day', function () {
    Queue::fake();

    foreach (range(1, 5) as $attempt) {
        $this->user->dataExports()->delete();
        $this->actingAs($this->user)->post(route('account.export'))->assertRedirect();
    }

    $this->user->dataExports()->delete();
    $this->actingAs($this->user)->post(route('account.export'))->assertTooManyRequests();
});
