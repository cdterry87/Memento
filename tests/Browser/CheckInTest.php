<?php

use App\Models\User;
use Database\Seeders\LookupSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    Storage::fake('media');

    $this->photo = storage_path('framework/testing/beach.jpg');
    File::put($this->photo, UploadedFile::fake()->image('beach.jpg', 1200, 800)->get());
});

afterEach(function () {
    File::delete($this->photo);
});

it('signs in and checks in with a mood, reasons, a message, and a photo', function () {
    $user = User::factory()->create(['email' => 'ada@example.com', 'name' => 'Ada Lovelace']);

    $page = visit('/login')
        ->type('email', 'ada@example.com')
        ->type('password', 'password')
        ->press('Sign in')
        ->assertPathIs('/today');

    $page->click('Great')
        ->assertSee('Step 2 of 3')
        ->click('Family')
        ->click('Friends')
        ->press('Next')
        ->assertSee('Step 3 of 3')
        ->type('.check-in textarea', 'Pancakes and a long walk by the river.')
        ->attach('input[type=file]', $this->photo)
        ->assertSee('Up to 4 photos a day.')
        ->press('Save')
        ->assertNoJavaScriptErrors();

    $page->waitForText('Pancakes and a long walk by the river.');

    $memory = $user->memories()->sole();

    expect($memory->mood->slug)->toBe('great')
        ->and($memory->reasons->pluck('slug')->all())->toBe(['family', 'friends'])
        ->and($memory->body)->toBe('Pancakes and a long walk by the river.');

    $tries = 0;
    while ($memory->photos()->count() === 0 && $tries++ < 20) {
        usleep(250_000);
    }

    expect($memory->photos()->count())->toBe(1);
});
