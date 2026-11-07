<?php

namespace App\Actions;

use App\Models\MemoryPhoto;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes a photo and every stored variant of it.
 */
class DeletePhoto
{
    public function handle(MemoryPhoto $photo): void
    {
        $photo->delete();

        Storage::disk('media')->delete([$photo->path, $photo->thumbnail_path]);
    }
}
