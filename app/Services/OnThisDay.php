<?php

namespace App\Services;

use App\Models\Memory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Entries from the same month and day in earlier years, newest first. On
 * February 28 in a year with no February 29, entries from February 29 are
 * included, so a leap-day entry still comes round.
 */
class OnThisDay
{
    /**
     * @return Collection<int, Memory>
     */
    public function for(User $user, CarbonImmutable $today): Collection
    {
        $includeLeapDay = $today->month === 2 && $today->day === 28 && ! $today->isLeapYear();

        return $user->memories()
            ->with(['mood', 'reasons', 'photos'])
            ->whereYear('date', '<', $today->year)
            ->whereMonth('date', $today->month)
            ->where(function (Builder $query) use ($today, $includeLeapDay) {
                $query->whereDay('date', $today->day);

                if ($includeLeapDay) {
                    $query->orWhereDay('date', 29);
                }
            })
            ->orderByDesc('date')
            ->get();
    }
}
