<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DayController;
use App\Http\Controllers\TodayController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome', [
    'appName' => config('app.name'),
])->middleware('guest')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/today', TodayController::class)->name('today');

    Route::get('/days/{date}/check-in', [DayController::class, 'edit'])->name('days.check-in');
    Route::put('/days/{date}', [DayController::class, 'update'])->name('days.update');

    /*
     * Placeholder screens. Later milestones replace them with controllers.
     */
    Route::inertia('/timeline', 'Timeline')->name('timeline');
    Route::inertia('/recap', 'Recap')->name('recap');

    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::patch('/account/preferences', [AccountController::class, 'updatePreferences'])->name('account.preferences');
});
