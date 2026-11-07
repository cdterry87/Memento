<?php

use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\LookupSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    CarbonImmutable::setTestNow('2026-11-07 12:00:00');
    Storage::fake('media');

    $this->user = User::factory()->create(['timezone' => 'UTC']);
    $this->memory = Memory::factory()->for($this->user)->create(['date' => '2026-11-07']);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

/**
 * A 300×200 JPEG whose EXIF says it was taken at a GPS location and should
 * be displayed rotated 90° clockwise (orientation 6), as phones often do.
 */
function jpegWithLocationAndRotation(): string
{
    $canvas = imagecreatetruecolor(300, 200);
    ob_start();
    imagejpeg($canvas);
    $jpeg = (string) ob_get_clean();

    $entry = fn (int $tag, int $type, int $count, string $value) => pack('vvV', $tag, $type, $count).$value;

    // IFD0 at offset 8: orientation, and a pointer to the GPS IFD at 38.
    $ifd0 = pack('v', 2)
        .$entry(0x0112, 3, 1, pack('vv', 6, 0))
        .$entry(0x8825, 4, 1, pack('V', 38))
        .pack('V', 0);

    // GPS IFD at 38: latitude reference and latitude, whose values are at 68.
    $gps = pack('v', 2)
        .$entry(0x0001, 2, 2, "N\0\0\0")
        .$entry(0x0002, 5, 3, pack('V', 68))
        .pack('V', 0)
        .pack('V6', 40, 1, 26, 1, 0, 1);

    $exif = "Exif\0\0".'II'.pack('vV', 42, 8).$ifd0.$gps;
    $app1 = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;

    return substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
}

function uploadPhoto(UploadedFile $file, string $date = '2026-11-07')
{
    return test()->actingAs(test()->user)->post(
        route('days.photos.store', $date),
        ['photo' => $file],
        ['Accept' => 'application/json'],
    );
}

/**
 * @return array{0: int, 1: int}
 */
function storedSize(string $path): array
{
    [$width, $height] = getimagesizefromstring(Storage::disk('media')->get($path));

    return [$width, $height];
}

it('stores a photo under a random name, with a thumbnail', function () {
    $response = uploadPhoto(UploadedFile::fake()->image('IMG_1234.jpg', 1200, 900));

    $photo = $this->memory->photos()->sole();

    $response->assertCreated()->assertExactJson(['data' => [
        'id' => $photo->ulid,
        'url' => "/media/photos/{$photo->ulid}/full",
        'thumbnailUrl' => "/media/photos/{$photo->ulid}/thumb",
        'width' => 1200,
        'height' => 900,
    ]]);

    Storage::disk('media')->assertExists([$photo->path, $photo->thumbnail_path]);
    expect($photo->path)->not->toContain('IMG_1234')
        ->and($photo->position)->toBe(1)
        ->and($photo->size_bytes)->toBe(Storage::disk('media')->size($photo->path));
});

it('removes location and other metadata, and applies the rotation', function () {
    $original = jpegWithLocationAndRotation();
    $originalExif = exif_read_data('data://image/jpeg;base64,'.base64_encode($original));

    expect($originalExif)->toHaveKey('GPSLatitude')
        ->and($originalExif['Orientation'])->toBe(6);

    uploadPhoto(UploadedFile::fake()->createWithContent('beach.jpg', $original))->assertCreated();

    $photo = $this->memory->photos()->sole();

    foreach ([$photo->path, $photo->thumbnail_path] as $path) {
        $stored = Storage::disk('media')->get($path);
        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($stored));

        expect($exif === false || ! array_key_exists('GPSLatitude', $exif))->toBeTrue()
            ->and($exif === false || ! array_key_exists('Orientation', $exif))->toBeTrue();
    }

    expect(storedSize($photo->path))->toBe([200, 300])
        ->and([$photo->width, $photo->height])->toBe([200, 300]);
});

it('shrinks large photos to 2048 pixels and thumbnails to 640', function () {
    uploadPhoto(UploadedFile::fake()->image('big.jpg', 4000, 3000))->assertCreated();

    $photo = $this->memory->photos()->sole();

    expect(storedSize($photo->path))->toBe([2048, 1536])
        ->and(storedSize($photo->thumbnail_path))->toBe([640, 480]);
});

it('keeps small photos at their own size', function () {
    uploadPhoto(UploadedFile::fake()->image('small.jpg', 400, 300))->assertCreated();

    expect(storedSize($this->memory->photos()->sole()->path))->toBe([400, 300]);
});

it('accepts PNG and WebP, and stores them as JPEG', function (string $name) {
    uploadPhoto(UploadedFile::fake()->image($name, 300, 200))->assertCreated();

    $stored = Storage::disk('media')->get($this->memory->photos()->sole()->path);

    expect(getimagesizefromstring($stored)['mime'])->toBe('image/jpeg');
})->with(['photo.png', 'photo.webp']);

it('rejects files that are not JPEG, PNG, or WebP images', function (UploadedFile $file) {
    uploadPhoto($file)->assertUnprocessable()->assertJsonValidationErrors('photo');

    expect($this->memory->photos()->exists())->toBeFalse();
    expect(Storage::disk('media')->allFiles())->toBe([]);
})->with([
    'text renamed to .jpg' => fn () => UploadedFile::fake()->createWithContent('photo.jpg', 'not really a photo'),
    'GIF' => fn () => UploadedFile::fake()->image('photo.gif'),
    'SVG' => fn () => UploadedFile::fake()->createWithContent('photo.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'),
]);

it('rejects photos over 10 MB', function () {
    uploadPhoto(UploadedFile::fake()->image('huge.jpg', 300, 200)->size(10 * 1024 + 1))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['photo' => 'Photos can be up to 10 MB each.']);
});

it('allows up to four photos a day', function () {
    foreach (range(1, 4) as $position) {
        uploadPhoto(UploadedFile::fake()->image("photo{$position}.jpg", 300, 200))->assertCreated();
    }

    uploadPhoto(UploadedFile::fake()->image('photo5.jpg', 300, 200))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['photo' => 'A day can have up to 4 photos.']);

    expect($this->memory->photos()->pluck('position')->all())->toBe([1, 2, 3, 4])
        ->and(Storage::disk('media')->allFiles())->toHaveCount(8);
});

it('needs a check-in before photos can be added', function () {
    uploadPhoto(UploadedFile::fake()->image('photo.jpg'), '2026-11-01')->assertNotFound();

    expect(Storage::disk('media')->allFiles())->toBe([]);
});

it('rejects photos for future dates', function () {
    uploadPhoto(UploadedFile::fake()->image('photo.jpg'), '2026-11-08')->assertNotFound();
});

it('puts photos in a new order, making the first the cover', function () {
    [$first, $second, $third] = MemoryPhoto::factory()->for($this->memory)
        ->forEachSequence(['position' => 1], ['position' => 2], ['position' => 3])
        ->create();

    $this->actingAs($this->user)
        ->patch(route('days.photos.order', '2026-11-07'), ['photos' => [$third->ulid, $first->ulid, $second->ulid]])
        ->assertSessionHasNoErrors();

    expect($this->memory->photos()->pluck('ulid')->all())->toBe([$third->ulid, $first->ulid, $second->ulid]);
});

it('refuses an order that leaves out or adds photos', function (string $mistake) {
    $photos = MemoryPhoto::factory()->for($this->memory)
        ->forEachSequence(['position' => 1], ['position' => 2])
        ->create();
    $elsewhere = MemoryPhoto::factory()->create();

    $this->actingAs($this->user)
        ->patch(route('days.photos.order', '2026-11-07'), ['photos' => match ($mistake) {
            'missing one' => [$photos[1]->ulid],
            'repeated' => [$photos[0]->ulid, $photos[0]->ulid],
            "another day's photo" => [$photos[0]->ulid, $photos[1]->ulid, $elsewhere->ulid],
        }])
        ->assertSessionHasErrors();

    expect($this->memory->photos()->pluck('position')->all())->toBe([1, 2]);
})->with(['missing one', 'repeated', "another day's photo"]);

it('deletes a photo and every file of it', function () {
    uploadPhoto(UploadedFile::fake()->image('photo.jpg'));
    $photo = $this->memory->photos()->sole();

    $this->actingAs($this->user)
        ->delete(route('days.photos.destroy', ['2026-11-07', $photo->ulid]))
        ->assertRedirect()
        ->assertSessionHas('status', 'photo-deleted');

    expect($photo->fresh())->toBeNull()
        ->and(Storage::disk('media')->allFiles())->toBe([]);
});

it('leaves no files behind when the whole day is deleted', function () {
    uploadPhoto(UploadedFile::fake()->image('one.jpg'));
    uploadPhoto(UploadedFile::fake()->image('two.jpg'));

    expect(Storage::disk('media')->allFiles())->toHaveCount(4);

    $this->actingAs($this->user)->delete(route('days.destroy', '2026-11-07'));

    expect(MemoryPhoto::count())->toBe(0)
        ->and(Storage::disk('media')->allFiles())->toBe([]);
});

it('serves a photo by redirecting to a short-lived signed URL', function (string $variant, string $column) {
    $photo = MemoryPhoto::factory()->for($this->memory)->create();

    $response = $this->actingAs($this->user)->get(route('media.photos', [$photo->ulid, $variant]));

    $response->assertRedirect()
        ->assertHeader('Cache-Control', 'no-store, private');

    expect($response->headers->get('Location'))->toContain($photo->{$column})
        ->toContain('expiration='.now()->addMinutes(10)->getTimestamp());
})->with([
    'full size' => ['full', 'path'],
    'thumbnail' => ['thumb', 'thumbnail_path'],
]);

it('refuses media to guests', function () {
    $photo = MemoryPhoto::factory()->for($this->memory)->create();

    $this->get(route('media.photos', [$photo->ulid, 'full']))->assertRedirect(route('login'));
});

it('only knows the full and thumb variants', function () {
    $photo = MemoryPhoto::factory()->for($this->memory)->create();

    $this->actingAs($this->user)->get("/media/photos/{$photo->ulid}/original")->assertNotFound();
});

it('includes photos, cover first, wherever the entry is shown', function (string $routeName, string $prop) {
    MemoryPhoto::factory()->for($this->memory)
        ->forEachSequence(['position' => 2, 'width' => 300], ['position' => 1, 'width' => 100])
        ->create();

    $this->actingAs($this->user)
        ->get(route($routeName, $routeName === 'days.show' ? '2026-11-07' : []))
        ->assertInertia(fn (Assert $page) => $page
            ->has("{$prop}.photos", 2)
            ->where("{$prop}.photos.0.width", 100)
            ->where("{$prop}.photos.1.width", 300)
            ->missing("{$prop}.photos.0.path")
        );
})->with([
    'today' => ['today', 'memory'],
    'day' => ['days.show', 'memory'],
    'timeline' => ['timeline', 'memories.data.0'],
]);
