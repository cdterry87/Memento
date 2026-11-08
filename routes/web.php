<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DayController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\RecapController;
use App\Http\Controllers\TimelineController;
use App\Http\Controllers\TodayController;
use App\Http\Controllers\UnsubscribeController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome', [
    'appName' => config('app.name'),
])->middleware('guest')->name('home');

Route::inertia('/privacy', 'Privacy')->name('privacy');

Route::post('/demo', [DemoController::class, 'store'])
    ->middleware(['guest', 'throttle:guest-forms'])
    ->name('demo');

/*
 * Signed links from emails. They work signed in or out, and the signature
 * is the only proof needed, so an old email can always turn emails off.
 */
Route::middleware('signed')->group(function () {
    Route::get('/unsubscribe/{user}/{type}', [UnsubscribeController::class, 'show'])->name('unsubscribe');
    Route::post('/unsubscribe/{user}/{type}', [UnsubscribeController::class, 'store'])->name('unsubscribe.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/today', TodayController::class)->name('today');

    Route::get('/timeline', TimelineController::class)->name('timeline');

    Route::get('/days/{date}', [DayController::class, 'show'])->name('days.show');
    Route::get('/days/{date}/check-in', [DayController::class, 'edit'])->name('days.check-in');
    Route::put('/days/{date}', [DayController::class, 'update'])->name('days.update');
    Route::delete('/days/{date}', [DayController::class, 'destroy'])->name('days.destroy');

    Route::post('/days/{date}/photos', [PhotoController::class, 'store'])
        ->middleware('throttle:uploads')
        ->name('days.photos.store');
    Route::patch('/days/{date}/photos/order', [PhotoController::class, 'order'])->name('days.photos.order');
    Route::delete('/days/{date}/photos/{photo}', [PhotoController::class, 'destroy'])->name('days.photos.destroy');

    Route::get('/media/photos/{photo}/{variant}', [MediaController::class, 'photo'])
        ->whereIn('variant', ['full', 'thumb'])
        ->name('media.photos');

    Route::get('/recap', [RecapController::class, 'index'])->name('recap');
    Route::get('/recap/{year}', [RecapController::class, 'show'])->whereNumber('year')->name('recap.show');

    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::patch('/account/preferences', [AccountController::class, 'updatePreferences'])->name('account.preferences');
});
