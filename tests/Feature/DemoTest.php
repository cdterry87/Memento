<?php

use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Models\User;
use App\Services\DemoAccount;
use App\Services\ImageProcessor;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LookupSeeder;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    CarbonImmutable::setTestNow('2026-11-07 18:00:00');
    config(['memento.demo_mode' => true]);
    Storage::fake('media');

    // Two small sample photos, so a reset doesn't process the real set.
    $this->samples = storage_path('framework/testing/demo-samples');
    File::ensureDirectoryExists($this->samples);
    foreach (['beach.jpg', 'forest.jpg'] as $name) {
        $image = UploadedFile::fake()->image($name, 300, 200);
        File::put("{$this->samples}/{$name}", $image->get());
    }
    app()->bind(DemoAccount::class, fn () => new DemoAccount(app(ImageProcessor::class), $this->samples));

    $this->turnstilePasses = true;
    Http::fake(['challenges.cloudflare.com/*' => fn () => Http::response(['success' => $this->turnstilePasses])]);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
    File::deleteDirectory($this->samples);
});

function demoUser(): User
{
    return User::where('is_demo', true)->sole();
}

function resetDemo(): void
{
    test()->artisan('demo:reset')->assertSuccessful();
}

describe('demo:reset', function () {
    it('refuses to run when demo mode is off', function () {
        config(['memento.demo_mode' => false]);

        $this->artisan('demo:reset')->assertFailed();

        expect(User::where('is_demo', true)->exists())->toBeFalse();
    });

    it('creates the demo account from the settings', function () {
        resetDemo();

        $user = demoUser();

        expect($user)
            ->name->toBe('Demo User')
            ->email->toBe('demo@example.test')
            ->timezone->toBe('America/Chicago')
            ->and($user->hasVerifiedEmail())->toBeTrue()
            ->and(password_verify('demo-password', $user->password))->toBeTrue();
    });

    it('fills more than two years up to yesterday, and leaves today empty', function () {
        resetDemo();

        // 18:00 UTC is noon in Chicago, so today there is November 7.
        $dates = demoUser()->memories()->orderBy('date')->pluck('date');

        expect($dates->first()->lessThanOrEqualTo(CarbonImmutable::parse('2024-11-07')))->toBeTrue()
            ->and($dates->last()->lessThan(CarbonImmutable::parse('2026-11-07')))->toBeTrue()
            ->and($dates->last()->greaterThan(CarbonImmutable::parse('2026-10-24')))->toBeTrue()
            ->and($dates->count())->toBeGreaterThan(450)
            ->and(Memory::whereHas('user', fn ($query) => $query->where('is_demo', true))->whereHas('reasons')->count())->toBe($dates->count());
    });

    it('has gaps, a mix of moods, messages, and processed sample photos', function () {
        resetDemo();

        $memories = demoUser()->memories()->with('mood')->get();
        $photo = MemoryPhoto::first();

        expect($memories->count())->toBeLessThan(800)
            ->and($memories->pluck('mood.score')->unique()->count())->toBeGreaterThanOrEqual(4)
            ->and($memories->whereNotNull('body')->count())->toBeGreaterThan(100)
            ->and(MemoryPhoto::count())->toBeGreaterThan(0)
            ->and($photo->width)->toBe(300);

        Storage::disk('media')->assertExists([$photo->path, $photo->thumbnail_path]);
    });

    it('gives "on this day" and the recap something to show', function () {
        resetDemo();

        $user = demoUser();

        expect($user->memories()->whereYear('date', 2025)->count())->toBeGreaterThan(200)
            ->and($user->memories()->whereYear('date', 2024)->count())->toBeGreaterThan(0);
    });

    it('replaces the old entries and their files with fresh ones', function () {
        resetDemo();
        $oldPhoto = MemoryPhoto::first();
        $user = demoUser();
        $user->forceFill(['name' => 'Changed by a visitor', 'theme' => 'dark', 'show_quotes' => false])->save();
        $user->memories()->create(['mood_id' => $user->memories()->first()->mood_id, 'date' => '2026-11-07', 'body' => 'Visitor note']);

        resetDemo();

        expect(demoUser())
            ->name->toBe('Demo User')
            ->show_quotes->toBeTrue()
            ->and(demoUser()->theme->value)->toBe('system')
            ->and(demoUser()->memories()->where('date', '2026-11-07')->exists())->toBeFalse()
            ->and(User::where('is_demo', true)->count())->toBe(1)
            ->and(MemoryPhoto::find($oldPhoto->id))->toBeNull();

        Storage::disk('media')->assertMissing([$oldPhoto->path, $oldPhoto->thumbnail_path]);
    });

    it("never touches anyone else's account", function () {
        $owner = User::factory()->create();
        $memory = Memory::factory()->for($owner)->create(['date' => '2026-01-01']);
        $photo = MemoryPhoto::factory()->for($memory)->create();
        Storage::disk('media')->put($photo->path, 'photo');

        resetDemo();
        resetDemo();

        expect($owner->fresh()->is_demo)->toBeFalse()
            ->and($owner->memories()->count())->toBe(1)
            ->and($photo->fresh())->not->toBeNull();
        Storage::disk('media')->assertExists($photo->path);
    });

    it('creates the demo account when the database is seeded in demo mode', function () {
        $this->seed(DatabaseSeeder::class);

        expect(demoUser()->memories()->count())->toBeGreaterThan(450);
    });

    it('seeds no demo account when demo mode is off', function () {
        config(['memento.demo_mode' => false]);

        $this->seed(DatabaseSeeder::class);

        expect(User::where('is_demo', true)->exists())->toBeFalse();
    });

    it('refuses to take over a real account that uses the demo email', function () {
        $real = User::factory()->create(['email' => 'demo@example.test']);

        $this->artisan('demo:reset')->assertFailed();

        expect($real->fresh()->is_demo)->toBeFalse();
    });

    it('runs every night in the demo time zone, only in demo mode', function () {
        $event = collect(app(Schedule::class)->events())
            ->first(fn (Event $event) => str_contains($event->command ?? '', 'demo:reset'));

        expect($event)->not->toBeNull()
            ->and($event->expression)->toBe('0 3 * * *')
            ->and($event->timezone)->toBe('America/Chicago')
            ->and($event->filtersPass(app()))->toBeTrue();

        config(['memento.demo_mode' => false]);

        expect($event->filtersPass(app()))->toBeFalse();
    });
});

describe('signing in to the demo', function () {
    it('offers the demo on the login page', function () {
        $this->get(route('login'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/Login')
                ->where('demoMode', true)
                ->where('turnstileSiteKey', config('memento.turnstile.site_key'))
            );
    });

    it('signs in as the demo user after the Turnstile check', function () {
        resetDemo();

        $this->post(route('demo'), ['turnstile' => 'a-token'])->assertRedirect(route('today'));

        $this->assertAuthenticatedAs(demoUser());
    });

    it('refuses without a passing Turnstile check', function (string $token, bool $cloudflareSaysYes) {
        resetDemo();
        $this->turnstilePasses = $cloudflareSaysYes;

        $this->post(route('demo'), ['turnstile' => $token])->assertSessionHasErrors('turnstile');

        $this->assertGuest();
    })->with([
        'no token' => ['', true],
        'rejected by Cloudflare' => ['a-token', false],
    ]);

    it('explains when the demo account has not been created yet', function () {
        $this->post(route('demo'), ['turnstile' => 'a-token'])
            ->assertSessionHasErrors(['turnstile' => "The demo isn't ready yet. Please try again later."]);

        $this->assertGuest();
    });

    it('does not exist when demo mode is off', function () {
        config(['memento.demo_mode' => false]);

        $this->post(route('demo'), ['turnstile' => 'a-token'])->assertNotFound();
    });

    it('tells demo users that changes reset', function () {
        resetDemo();

        $this->actingAs(demoUser())
            ->get(route('today'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.user.isDemo', true));
    });
});

describe('registration in demo mode', function () {
    afterEach(function () {
        // Back to the value phpunit.xml sets. Unsetting it would let .env's value through.
        putenv('DEMO_MODE=false');
        $_ENV['DEMO_MODE'] = $_SERVER['DEMO_MODE'] = 'false';
    });

    it('removes the registration routes', function () {
        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = $_SERVER['DEMO_MODE'] = 'true';
        $this->refreshApplication();

        $this->get('/register')->assertNotFound();
        $this->post('/register', ['name' => 'Bot', 'email' => 'bot@example.com'])->assertNotFound();
    });

    it('keeps registration when demo mode is off', function () {
        $this->get('/register')->assertOk();
    });
});

describe('demo restrictions', function () {
    beforeEach(function () {
        resetDemo();
        $this->demo = demoUser();
    });

    it("refuses to change the demo account's name, email, or password", function () {
        $this->actingAs($this->demo)
            ->put(route('user-profile-information.update'), ['name' => 'Hacked', 'email' => 'hacked@example.com'])
            ->assertSessionHasErrorsIn('updateProfileInformation', 'name');

        $this->actingAs($this->demo)
            ->put(route('user-password.update'), [
                'current_password' => 'demo-password',
                'password' => 'a-brand-new-password',
                'password_confirmation' => 'a-brand-new-password',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'current_password');

        expect($this->demo->fresh())
            ->name->toBe('Demo User')
            ->email->toBe('demo@example.test')
            ->and(password_verify('demo-password', $this->demo->fresh()->password))->toBeTrue();
    });

    it('refuses photo uploads from the demo account', function () {
        $memory = $this->demo->memories()->latest('date')->first();
        $before = MemoryPhoto::count();

        $this->actingAs($this->demo)
            ->postJson(route('days.photos.store', $memory->date->toDateString()), [
                'photo' => UploadedFile::fake()->image('upload.jpg'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photo' => 'Adding photos is turned off in the demo.']);

        expect(MemoryPhoto::count())->toBe($before);
    });

    it('refuses to turn on emails or change the time zone', function () {
        $this->actingAs($this->demo)
            ->patch(route('account.preferences'), ['reminder_emails' => true])
            ->assertSessionHasErrors('emails');

        $this->actingAs($this->demo)
            ->patch(route('account.preferences'), ['on_this_day_emails' => '1'])
            ->assertSessionHasErrors('emails');

        $this->actingAs($this->demo)
            ->patch(route('account.preferences'), ['timezone' => 'Asia/Tokyo'])
            ->assertSessionHasErrors('timezone');

        expect($this->demo->fresh())
            ->reminder_emails->toBeFalse()
            ->on_this_day_emails->toBeFalse()
            ->timezone->toBe('America/Chicago');
    });

    it('still lets visitors check in, change the theme, and hide quotes', function () {
        $this->actingAs($this->demo)
            ->put(route('days.update', '2026-11-07'), ['mood' => 'great', 'reasons' => ['friends'], 'body' => 'Trying the demo'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->demo)
            ->patch(route('account.preferences'), ['theme' => 'dark', 'show_quotes' => false, 'reminder_emails' => false])
            ->assertSessionHasNoErrors();

        expect($this->demo->memories()->where('date', '2026-11-07')->value('body'))->toBe('Trying the demo')
            ->and($this->demo->fresh()->show_quotes)->toBeFalse();
    });

    it('still lets a real account change its details', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('account.preferences'), ['reminder_emails' => true, 'timezone' => 'Asia/Tokyo'])
            ->assertSessionHasNoErrors();

        expect($user->fresh()->reminder_emails)->toBeTrue();
    });
});
