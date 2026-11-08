<?php

use App\Enums\EmailType;
use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Models\Mood;
use App\Models\Reason;
use App\Models\User;
use App\Notifications\DailyEmail;
use Carbon\CarbonImmutable;
use Database\Seeders\LookupSeeder;
use Database\Seeders\QuoteSeeder;
use Illuminate\Support\Facades\Storage;

/*
 * WCAG 2.2 AA checks with axe on every screen, in both themes and on a
 * phone-sized screen. Level 3 fails on any violation, however minor.
 */

beforeEach(function () {
    $this->seed([LookupSeeder::class, QuoteSeeder::class]);
    Storage::fake('media');
    CarbonImmutable::setTestNow('2026-11-07 15:00:00');

    $this->user = User::factory()->create(['name' => 'Ada Lovelace', 'timezone' => 'UTC']);

    $moods = Mood::pickable()->get();
    $reasons = Reason::pickable()->get();

    foreach (['2025-11-07', '2026-10-20', '2026-11-01', '2026-11-05', '2026-11-06'] as $index => $date) {
        $memory = Memory::factory()->for($this->user)->create([
            'date' => $date,
            'mood_id' => $moods[$index % $moods->count()]->id,
            'body' => "A memory from {$date}.",
        ]);
        $memory->reasons()->attach($reasons->take(2)->pluck('id'));
    }

    $photo = MemoryPhoto::factory()->for($this->user->memories()->where('date', '2026-11-06')->sole())->create();
    Storage::disk('media')->put($photo->path, 'photo');
    Storage::disk('media')->put($photo->thumbnail_path, 'photo');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

dataset('themes', ['light', 'dark']);

it('has no accessibility issues on guest pages', function (string $path, string $theme) {
    $page = $theme === 'dark' ? visit($path)->inDarkMode() : visit($path)->inLightMode();

    $page->assertNoJavaScriptErrors()->assertNoAccessibilityIssues(3);
})->with([
    'welcome' => '/',
    'login' => '/login',
    'register' => '/register',
    'forgot password' => '/forgot-password',
    'reset password' => '/reset-password/a-token?email=ada@example.com',
    'privacy' => '/privacy',
])->with('themes');

it('has no accessibility issues on signed-in pages', function (string $path, string $theme) {
    $this->actingAs($this->user);

    $page = $theme === 'dark' ? visit($path)->inDarkMode() : visit($path)->inLightMode();

    $page->assertNoJavaScriptErrors()->assertNoAccessibilityIssues(3);
})->with([
    'today' => '/today',
    'timeline' => '/timeline',
    'day' => '/days/2026-11-06',
    'check-in' => '/days/2026-11-07/check-in',
    'edit check-in' => '/days/2026-11-06/check-in',
    'recap' => '/recap/2026',
    'account' => '/account',
])->with('themes');

it('has no accessibility issues on the unsubscribe page', function (string $theme) {
    $url = DailyEmail::unsubscribeUrl($this->user, EmailType::CheckInReminder);
    $page = $theme === 'dark' ? visit($url)->inDarkMode() : visit($url)->inLightMode();

    $page->assertNoAccessibilityIssues(3);
})->with('themes');

it('has no accessibility issues on the verify email page', function () {
    $this->actingAs(User::factory()->unverified()->create());

    visit('/email/verify')->assertNoAccessibilityIssues(3);
});

it('has no accessibility issues through each check-in step', function () {
    $this->actingAs($this->user);

    visit('/days/2026-11-07/check-in')
        ->click('Great')
        ->assertSee('Step 2 of 3')
        ->assertNoAccessibilityIssues(3)
        ->click('Family')
        ->press('Next')
        ->assertSee('Step 3 of 3')
        ->assertNoAccessibilityIssues(3);
});

it('has no accessibility issues in dialogs and menus', function () {
    $this->actingAs($this->user);

    visit('/account')
        ->press('Delete my account')
        ->assertSee('Delete your account?')
        ->wait(1)
        ->assertNoAccessibilityIssues(3);

    visit('/days/2026-11-06')
        ->click('Delete')
        ->assertNoAccessibilityIssues(3);

    // An open menu sits in Vuetify's overlay container, outside the page
    // landmarks, which trips axe's "region" best practice. That isn't a WCAG
    // rule, so menus are held to serious and critical issues.
    visit('/today')
        ->click('Ada Lovelace')
        ->assertSee('Sign out')
        ->assertNoAccessibilityIssues(1);
});

it('has no accessibility issues on a phone', function (string $path) {
    $this->actingAs($this->user);

    visit($path)->on()->mobile()->assertNoAccessibilityIssues(3);
})->with(['/today', '/timeline', '/days/2026-11-06', '/recap/2026', '/account']);

it('lets keyboard users skip straight to the page content', function () {
    $this->actingAs($this->user);

    visit('/today')
        ->assertScript("document.querySelector('a[href], button').classList.contains('skip-link')")
        ->keys('.skip-link', 'Enter')
        ->assertScript('document.activeElement.id', 'main-content');
});
