<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bindCalendarDates();

        // Surfaces N+1 queries in development and tests. Production logs nothing extra.
        Model::preventLazyLoading(! $this->app->isProduction());

        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));
        $this->writeAccountEmails();

        // In production, new passwords are also checked against known breaches,
        // using the Have I Been Pwned range API, which never sees the password.
        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(8)->uncompromised()
            : Password::min(8));

        // A generous ceiling on everything a signed-in person does, so a stolen
        // session or a runaway script can't flood the app.
        RateLimiter::for('app', fn (Request $request) => Limit::perMinute(300)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('exports', fn (Request $request) => Limit::perDay(5)->by($request->user()?->id ?: $request->ip()));
    }

    /**
     * Memento's own wording for the address confirmation and password reset
     * emails, in place of Laravel's defaults.
     */
    private function writeAccountEmails(): void
    {
        VerifyEmail::toMailUsing(fn (object $notifiable, string $url) => (new MailMessage)
            ->subject('Confirm your email address')
            ->greeting('Welcome to Memento')
            ->line('Confirm your email address to start keeping your days.')
            ->action('Confirm email address', $url)
            ->line("If you didn't make an account, you can ignore this email."));

        ResetPassword::toMailUsing(function (CanResetPassword $notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
            $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('Reset your Memento password')
                ->greeting('Reset your password')
                ->line('Someone asked to reset the password for your Memento account. If it was you, choose a new one here.')
                ->action('Choose a new password', $url)
                ->line("The link works for {$minutes} minutes. If you didn't ask for this, you can ignore this email, and your password stays the same.");
        });
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
