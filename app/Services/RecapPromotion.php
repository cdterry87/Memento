<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Decides which year's recap to promote on the Today screen. The window runs
 * from December 26 to January 31: the year that is ending, then the year
 * that has just ended. A year with no entries is never promoted.
 */
class RecapPromotion
{
    public function yearFor(User $user, CarbonImmutable $today): ?int
    {
        $year = match (true) {
            $today->month === 12 && $today->day >= 26 => $today->year,
            $today->month === 1 => $today->year - 1,
            default => null,
        };

        if ($year === null || ! $user->memories()->whereYear('date', $year)->exists()) {
            return null;
        }

        return $year;
    }
}
