<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Everyone shares the demo account, so some changes are refused on the
 * server for it, not just hidden in the interface (spec section 6.9).
 */
final class DemoRestriction
{
    /**
     * @throws ValidationException when the user is the demo account.
     */
    public static function refuse(?User $user, string $field, string $message, string $errorBag = 'default'): void
    {
        if ($user?->is_demo) {
            throw ValidationException::withMessages([$field => $message])->errorBag($errorBag);
        }
    }
}
