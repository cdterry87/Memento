<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\DemoRestriction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        DemoRestriction::refuse($user, 'name', "The demo account's name and email can't be changed.", 'updateProfileInformation');

        $input['email'] = Str::lower(trim((string) ($input['email'] ?? '')));
        $isChangingEmail = $input['email'] !== Str::lower($user->email);

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],

            // The email address is how an account is recovered, so changing it
            // needs the password. Otherwise anyone holding a stolen session
            // could change it, then reset the password and take the account.
            'current_password' => $isChangingEmail ? ['required', 'string', 'current_password:web'] : ['nullable'],
        ], [
            'current_password.required' => 'Enter your password to change your email address.',
            'current_password.current_password' => "That password isn't right.",
        ])->validateWithBag('updateProfileInformation');

        if ($isChangingEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
            ])->save();
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
