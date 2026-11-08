<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DataExportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A ZIP of one user's journal, built by a queued job (spec section 6.7). The
 * file lives on the private `media` disk and is deleted with its row once
 * the download link expires. The browser knows an export only by its ULID.
 *
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property string|null $path
 * @property int|null $size_bytes
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $failed_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable $created_at
 * @property-read User $user
 */
#[Fillable(['path', 'size_bytes', 'completed_at', 'failed_at', 'expires_at'])]
class DataExport extends Model
{
    /** @use HasFactory<DataExportFactory> */
    use HasFactory, HasUlids, Prunable;

    /**
     * How long the download link works, in hours.
     */
    public const EXPIRES_AFTER_HOURS = 24;

    /**
     * A job that hasn't finished after this many hours is treated as lost,
     * so a new export can be asked for and the row can be pruned.
     */
    public const STALE_AFTER_HOURS = 2;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether the ZIP is built and its link still works.
     */
    public function isReady(): bool
    {
        return $this->completed_at !== null
            && $this->path !== null
            && $this->expires_at?->isFuture() === true;
    }

    /**
     * Whether the job is still expected to finish.
     */
    public function isPending(): bool
    {
        return $this->completed_at === null
            && $this->failed_at === null
            && $this->created_at->isAfter(now()->subHours(self::STALE_AFTER_HOURS));
    }

    /**
     * The file name the download is saved as, dated on the user's calendar.
     */
    public function downloadName(): string
    {
        $madeAt = ($this->completed_at ?? $this->created_at)->setTimezone($this->user->timezone);

        return 'memento-export-'.$madeAt->format('Y-m-d').'.zip';
    }

    /**
     * Expired exports, failed ones, and jobs that never finished.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()
            ->where('expires_at', '<=', now())
            ->orWhere(fn (Builder $query) => $query
                ->whereNull('completed_at')
                ->where('created_at', '<=', now()->subHours(self::STALE_AFTER_HOURS)));
    }

    /**
     * Deletes the ZIP before its row is pruned.
     */
    protected function pruning(): void
    {
        $this->deleteFile();
    }

    public function deleteFile(): void
    {
        if ($this->path !== null) {
            Storage::disk('media')->delete($this->path);
        }
    }
}
