<?php

namespace App\Actions;

use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Services\ImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Adds a photo to a check-in. The image is processed and stored under a
 * random name, and the original filename is discarded.
 */
class StorePhoto
{
    public function __construct(private ImageProcessor $processor) {}

    public function handle(Memory $memory, UploadedFile $file): MemoryPhoto
    {
        $image = $this->processor->process($file->getRealPath());

        $name = Str::random(40);
        $path = "photos/{$name}.jpg";
        $thumbnailPath = "photos/{$name}_thumb.jpg";

        $disk = Storage::disk('media');
        $disk->put($path, $image->full);
        $disk->put($thumbnailPath, $image->thumbnail);

        try {
            return DB::transaction(function () use ($memory, $image, $path, $thumbnailPath) {
                // Locking the entry makes uploads for the same day take turns, so
                // two at once can't both slip under the limit.
                Memory::query()->whereKey($memory->id)->lockForUpdate()->first();

                $photos = $memory->photos();

                if ($photos->count() >= MemoryPhoto::MAX_PER_DAY) {
                    throw ValidationException::withMessages([
                        'photo' => 'A day can have up to '.MemoryPhoto::MAX_PER_DAY.' photos.',
                    ]);
                }

                return $photos->create([
                    'path' => $path,
                    'thumbnail_path' => $thumbnailPath,
                    'width' => $image->width,
                    'height' => $image->height,
                    'size_bytes' => strlen($image->full),
                    'position' => ((int) $memory->photos()->max('position')) + 1,
                ]);
            });
        } catch (Throwable $exception) {
            $disk->delete([$path, $thumbnailPath]);

            throw $exception;
        }
    }
}
