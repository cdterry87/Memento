<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests run against Sail's MySQL "testing" database, which matches
| production. Each test is wrapped in a transaction that is rolled back.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Browser tests
|--------------------------------------------------------------------------
|
| End-to-end tests in a real browser, run with `--testsuite=Browser`, once
| for Chromium and once with `--browser safari` (WebKit). They use the production
| build, so a running Vite dev server can't leak into them.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        $this->withVite();
        Vite::useHotFile(storage_path('framework/testing/no-vite-hot-file'));
    })
    ->in('Browser');
