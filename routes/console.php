<?php

use App\Models\DataExport;
use Illuminate\Support\Facades\Schedule;

Schedule::command('emails:send-daily')->hourly()->withoutOverlapping();

/*
 * Deletes data exports, and their ZIPs, once their download links expire.
 */
Schedule::command('model:prune', ['--model' => [DataExport::class]])->hourly();

/*
 * Rebuilds the demo account in the small hours of the demo's own day, so
 * visitors always find a fresh account with today left empty.
 */
Schedule::command('demo:reset')
    ->dailyAt('03:00')
    ->timezone((string) config('memento.demo.timezone'))
    ->when(fn () => (bool) config('memento.demo_mode'))
    ->withoutOverlapping();
