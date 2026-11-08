<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DayController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\RecapController;
use App\Http\Controllers\TimelineController;
use App\Http\Controllers\TodayController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome', [
    'appName' => config('app.name'),
])->middleware('guest')->name('home');

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
