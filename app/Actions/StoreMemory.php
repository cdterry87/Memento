<?php

namespace App\Actions;

use App\Models\Memory;
use App\Models\Mood;
use App\Models\Reason;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a user's check-in for one date. Running it again for
 * the same date updates the existing entry, so saving is idempotent.
 */
class StoreMemory
{
    /**
     * @param  Collection<int, Reason>  $reasons
     */
    public function handle(User $user, CarbonImmutable $date, Mood $mood, Collection $reasons, ?string $body): Memory
    {
        return DB::transaction(function () use ($user, $date, $mood, $reasons, $body) {
            $memory = $user->memories()->updateOrCreate(
                ['date' => $date->toDateString()],
                ['mood_id' => $mood->id, 'body' => $body],
            );

            $memory->reasons()->sync($reasons->pluck('id'));

            return $memory;
        });
    }
}
