<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DayController;
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

    /*
     * Placeholder screen. A later milestone replaces it with a controller.
     */
    Route::inertia('/recap', 'Recap')->name('recap');

    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::patch('/account/preferences', [AccountController::class, 'updatePreferences'])->name('account.preferences');
});
