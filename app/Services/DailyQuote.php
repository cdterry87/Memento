<?php

namespace App\Services;

use App\Models\Quote;
use Carbon\CarbonInterface;

/**
 * Picks the quote for a calendar date. Everyone sees the same quote on the
 * same date, and the list repeats once every quote has had its day.
 */
class DailyQuote
{
    public function forDate(CarbonInterface $date): ?Quote
    {
        $count = Quote::count();

        if ($count === 0) {
            return null;
        }

        return Quote::orderBy('id')
            ->skip($date->dayOfYear % $count)
            ->first();
    }
}
