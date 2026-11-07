<?php

namespace App\Actions;

use App\Models\Memory;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes a check-in and its files. Database cascades remove its reasons and
 * photo rows, but they don't fire model events, so the files themselves are
 * removed here explicitly.
 */
class DeleteMemory
{
    public function handle(Memory $memory): void
    {
        $paths = $memory->photos()
            ->get(['path', 'thumbnail_path'])
            ->flatMap(fn ($photo) => [$photo->path, $photo->thumbnail_path])
            ->all();

        $memory->delete();

        if ($paths !== []) {
            Storage::disk('media')->delete($paths);
        }
    }
}
