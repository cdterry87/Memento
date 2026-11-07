<?php

namespace App\Actions;

use App\Models\Memory;
use App\Models\MemoryPhoto;
use Illuminate\Support\Facades\DB;

/**
 * Puts a day's photos in a new order. The first becomes the cover.
 */
class ReorderPhotos
{
    /**
     * @param  list<string>  $ulids  The day's photos in the new order. Any left out keep their order after these.
     */
    public function handle(Memory $memory, array $ulids): void
    {
        $positions = array_flip($ulids);

        DB::transaction(function () use ($memory, $positions) {
            $memory->photos()->get()
                ->sortBy(fn (MemoryPhoto $photo) => $positions[$photo->ulid] ?? count($positions) + $photo->position)
                ->values()
                ->each(fn (MemoryPhoto $photo, int $index) => $photo->update(['position' => $index + 1]));
        });
    }
}
