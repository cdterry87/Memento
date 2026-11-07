<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome', [
    'appName' => config('app.name'),
])->name('home');
