<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\PasswordResetLinkRequested;
use App\Http\Responses\SignedOut;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SuccessfulPasswordResetLinkRequestResponse::class, PasswordResetLinkRequested::class);
        $this->app->singleton(FailedPasswordResetLinkRequestResponse::class, PasswordResetLinkRequested::class);
        $this->app->singleton(LogoutResponse::class, SignedOut::class);
    }

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
        $this->authenticateWithPasswords();
    }

    /**
     * Fortify's usual email and password check, except that the shared demo
     * account can't sign in this way. Its password is no secret, so it only
     * opens through the demo button, behind the Turnstile bot check.
     */
    private function authenticateWithPasswords(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()->where('email', $request->string(Fortify::username())->toString())->first();

            // Checking a password takes the same time whether or not the
            // account exists, so response times don't reveal who has one.
            $passwordMatches = Hash::check($request->string('password')->toString(), $user->password ?? Hash::make(Str::random(40)));

            return $user !== null && ! $user->is_demo && $passwordMatches ? $user : null;
        });
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
        // Five tries a minute for one address from one place, and 20 a minute
        // from one place across every address, which slows credential stuffing.
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->string(Fortify::username())->toString()).'|'.$request->ip());

            return [
                Limit::perMinute(5)->by($throttleKey),
                Limit::perMinute(20)->by('login-ip|'.$request->ip()),
            ];
        });

        RateLimiter::for('guest-forms', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
