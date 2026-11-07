<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome', [
    'appName' => config('app.name'),
])->middleware('guest')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    /*
     * Placeholder screens. Later milestones replace them with controllers.
     */
    Route::inertia('/today', 'Today')->name('today');
    Route::inertia('/timeline', 'Timeline')->name('timeline');
    Route::inertia('/recap', 'Recap')->name('recap');

    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::patch('/account/preferences', [AccountController::class, 'updatePreferences'])->name('account.preferences');
});
