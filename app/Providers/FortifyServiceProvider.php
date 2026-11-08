<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        $this->registerViews();
        $this->registerRateLimiters();
    }

    /**
     * Render Fortify's screens as Inertia pages.
     */
    private function registerViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => true,
            'turnstileSiteKey' => config('memento.turnstile.site_key'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/Register', [
            'turnstileSiteKey' => config('memento.turnstile.site_key'),
        ]));

        Fortify::requestPasswordResetLinkView(fn () => Inertia::render('auth/ForgotPassword'));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'token' => $request->route('token'),
            'email' => $request->string('email')->toString(),
        ]));

        Fortify::verifyEmailView(fn () => Inertia::render('auth/VerifyEmail'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Limits for the forms anyone can submit. Fortify throttles sign-in
     * itself, and ThrottleGuestForms applies the guest-forms limit to
     * registration and password resets.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->string(Fortify::username())->toString()).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('guest-forms', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
