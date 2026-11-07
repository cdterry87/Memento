<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome', [
    'appName' => config('app.name'),
])->name('home');

/*
 * Placeholder screens for the app layout. Milestone 2 puts these behind
 * sign-in, and later milestones replace them with controllers.
 */
Route::inertia('/today', 'Today')->name('today');
Route::inertia('/timeline', 'Timeline')->name('timeline');
Route::inertia('/recap', 'Recap')->name('recap');
Route::inertia('/account', 'Account')->name('account');
