<?php

namespace App\Models;

use Database\Factories\MemoryPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A photo on a check-in. The image and its thumbnail live on the private
 * `media` disk, and `path` and `thumbnail_path` are keys on that disk, never
 * URLs. The browser knows a photo only by its ULID.
 *
 * @property int $id
 * @property string $ulid
 * @property int $memory_id
 * @property string $path
 * @property string $thumbnail_path
 * @property int $width
 * @property int $height
 * @property int $size_bytes
 * @property int $position
 * @property-read Memory $memory
 */
#[Fillable(['path', 'thumbnail_path', 'width', 'height', 'size_bytes', 'position'])]
class MemoryPhoto extends Model
{
    /** @use HasFactory<MemoryPhotoFactory> */
    use HasFactory, HasUlids;

    /**
     * The most photos one day can have.
     */
    public const MAX_PER_DAY = 4;

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * @return BelongsTo<Memory, $this>
     */
    public function memory(): BelongsTo
    {
        return $this->belongsTo(Memory::class);
    }
}
