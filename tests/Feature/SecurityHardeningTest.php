<?php

use App\Http\Responses\PasswordResetLinkRequested;
use App\Models\Memory;
use App\Models\User;
use Database\Seeders\LookupSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/*
 * Protections against account takeover and abuse, beyond the cross-user
 * checks in CrossUserAccessTest.
 */

describe('changing the email address', function () {
    it('needs the current password', function () {
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->actingAs($user)
            ->put(route('user-profile-information.update'), ['name' => $user->name, 'email' => 'attacker@example.com'])
            ->assertSessionHasErrorsIn('updateProfileInformation', 'current_password');

        $this->actingAs($user)
            ->put(route('user-profile-information.update'), [
                'name' => $user->name,
                'email' => 'attacker@example.com',
                'current_password' => 'wrong-password',
            ])
            ->assertSessionHasErrorsIn('updateProfileInformation', 'current_password');

        expect($user->fresh()->email)->toBe('ada@example.com');
    });

    it('works with the current password', function () {
        Notification::fake();
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->actingAs($user)
            ->put(route('user-profile-information.update'), [
                'name' => $user->name,
                'email' => 'new@example.com',
                'current_password' => 'password',
            ])
            ->assertSessionHasNoErrors();

        expect($user->fresh()->email)->toBe('new@example.com')
            ->and($user->fresh()->hasVerifiedEmail())->toBeFalse();
    });

    it("doesn't need the password for a new name or a change of letter case", function () {
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->actingAs($user)
            ->put(route('user-profile-information.update'), ['name' => 'Ada King', 'email' => 'Ada@Example.com'])
            ->assertSessionHasNoErrors();

        expect($user->fresh()->name)->toBe('Ada King')
            ->and($user->fresh()->email)->toBe('ada@example.com')
            ->and($user->fresh()->hasVerifiedEmail())->toBeTrue();
    });
});

describe('changing the password', function () {
    it('signs out other sessions', function () {
        $user = User::factory()->create();
        $oldHash = $user->password;

        $user->forceFill(['password' => Hash::make('a-brand-new-password')])->save();

        // A session from before the change still carries the old password hash.
        $this->actingAs($user)
            ->withSession(['password_hash_web' => $oldHash])
            ->get(route('today'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    });

    it('keeps the session that made the change, and ends remembered sign-ins', function () {
        $user = User::factory()->create(['remember_token' => 'old-token']);

        $this->actingAs($user)
            ->withSession(['password_hash_web' => $user->password])
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'a-brand-new-password',
                'password_confirmation' => 'a-brand-new-password',
            ])
            ->assertSessionHasNoErrors();

        $this->get(route('today'))->assertOk();
        expect($user->fresh()->remember_token)->not->toBe('old-token');
    });
});

describe('forgot password', function () {
    it('gives the same reply whether or not the address has an account', function () {
        Notification::fake();
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->post(route('password.email'), ['email' => 'ada@example.com'])
            ->assertSessionHas('status', PasswordResetLinkRequested::MESSAGE)
            ->assertSessionHasNoErrors();

        $this->post(route('password.email'), ['email' => 'nobody@example.com'])
            ->assertSessionHas('status', PasswordResetLinkRequested::MESSAGE)
            ->assertSessionHasNoErrors();

        // A second request straight away is throttled by the broker, silently.
        $this->post(route('password.email'), ['email' => 'ada@example.com'])
            ->assertSessionHas('status', PasswordResetLinkRequested::MESSAGE)
            ->assertSessionHasNoErrors();

        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    });
});

describe('signing in', function () {
    it("doesn't let the demo account sign in with its password", function () {
        User::factory()->create(['email' => 'demo@example.test', 'is_demo' => true]);

        $this->post(route('login.store'), ['email' => 'demo@example.test', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    it('still signs in everyone else, with any letter case in the address', function () {
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->post(route('login.store'), ['email' => 'Ada@Example.com', 'password' => 'password'])
            ->assertRedirect(route('today'));

        $this->assertAuthenticatedAs($user);
    });

    it('limits attempts from one place across many addresses', function () {
        foreach (range(1, 20) as $attempt) {
            $this->post(route('login.store'), ['email' => "person{$attempt}@example.com", 'password' => 'guess'])
                ->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), ['email' => 'one-more@example.com', 'password' => 'guess'])
            ->assertTooManyRequests();
    });
});

describe('uploads', function () {
    /**
     * A tiny PNG whose header claims a huge image, which would take gigabytes
     * of memory to decode.
     */
    function pngClaiming(int $width, int $height): UploadedFile
    {
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        $png = "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            .$chunk('IDAT', (string) gzcompress(str_repeat("\0", 64)))
            .$chunk('IEND', '');

        return UploadedFile::fake()->createWithContent('huge.png', $png);
    }

    it('refuses images too large to decode safely', function () {
        $this->seed(LookupSeeder::class);
        Storage::fake('media');
        $user = User::factory()->create();
        Memory::factory()->for($user)->create(['date' => now()->toDateString()]);

        $this->actingAs($user)
            ->post(route('days.photos.store', now()->toDateString()), ['photo' => pngClaiming(9000, 9000)])
            ->assertSessionHasErrors(['photo' => 'That photo is too large to process.']);

        expect(Storage::disk('media')->allFiles())->toBe([]);
    });
});

describe('the attack surface', function () {
    it('limits the rate of every signed-in route', function () {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => in_array('verified', $route->gatherMiddleware(), true));

        expect($routes)->not->toBeEmpty();

        foreach ($routes as $route) {
            expect($route->gatherMiddleware())->toContain('throttle:app');
        }
    });

    it('serves no files from local storage', function () {
        expect(Route::has('storage.local'))->toBeFalse()
            ->and(Route::has('storage.local.upload'))->toBeFalse();
    });

    it('refuses breached passwords in production', function () {
        Http::fake([
            'api.pwnedpasswords.com/*' => Http::response(strtoupper(substr(sha1('password1234'), 5)).':12345'),
        ]);
        $check = fn (string $password) => Validator::make(['password' => $password], ['password' => [Password::default()]])->passes();

        expect($check('password1234'))->toBeTrue();

        app()->detectEnvironment(fn () => 'production');

        expect($check('password1234'))->toBeFalse()
            ->and($check('a-rare-passphrase-42'))->toBeTrue();
    });
});

describe('browser history', function () {
    it('encrypts the history of signed-in pages', function () {
        $page = $this->actingAs(User::factory()->create())->get(route('today'))->viewData('page');

        expect($page['encryptHistory'])->toBeTrue();
    });

    it('clears the history when signing out', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect('/');

        expect($this->get('/')->viewData('page')['clearHistory'])->toBeTrue();
    });
});
