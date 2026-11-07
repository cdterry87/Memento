<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bindCalendarDates();
    }

    /**
     * Turns a `{date}` route segment into a date on the signed-in user's
     * calendar. Impossible dates, such as 2026-02-30, and dates after the
     * user's today are not found, so the future can never be written to.
     */
    private function bindCalendarDates(): void
    {
        Route::pattern('date', '\d{4}-\d{2}-\d{2}');

        Route::bind('date', function (string $value): CarbonImmutable {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, 'UTC');

            if (! $date instanceof CarbonImmutable || $date->toDateString() !== $value) {
                abort(404);
            }

            /** @var Request $request */
            $request = app('request');
            $today = $request->user()?->today()->toDateString();

            if ($today !== null && $value > $today) {
                abort(404);
            }

            return $date;
        });
    }
}
