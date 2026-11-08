<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Rules\Turnstile;
use DateTimeZone;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
            'turnstile' => ['required', new Turnstile(request()->ip())],
        ], [
            'turnstile.required' => 'Please complete the security check and try again.',
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'timezone' => $this->detectedTimezone($input['timezone'] ?? null),
        ]);
    }

    /**
     * The time zone the browser reported, or UTC if it sent nothing usable.
     * The user can correct it in Account, so a bad value never blocks sign-up.
     */
    private function detectedTimezone(?string $timezone): string
    {
        $isKnown = is_string($timezone)
            && in_array($timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true);

        return $isKnown ? $timezone : 'UTC';
    }
}
