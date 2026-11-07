<?php

namespace App\Actions;

use App\Models\Memory;

/**
 * Deletes a check-in. Database cascades remove its reasons, but they don't
 * fire model events, so files attached in later milestones are cleaned up
 * here explicitly.
 */
class DeleteMemory
{
    public function handle(Memory $memory): void
    {
        $memory->delete();
    }
}
