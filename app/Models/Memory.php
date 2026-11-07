<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\MemoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One day's check-in. Each user has at most one per date, and `date` is the
 * user's own calendar date, not derived from a timestamp.
 *
 * @property int $id
 * @property int $user_id
 * @property int $mood_id
 * @property CarbonImmutable $date
 * @property string|null $body
 * @property-read Mood $mood
 * @property-read Collection<int, Reason> $reasons
 * @property-read Collection<int, MemoryPhoto> $photos
 */
#[Fillable(['mood_id', 'date', 'body'])]
class Memory extends Model
{
    /** @use HasFactory<MemoryFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Mood, $this>
     */
    public function mood(): BelongsTo
    {
        return $this->belongsTo(Mood::class);
    }

    /**
     * @return BelongsToMany<Reason, $this>
     */
    public function reasons(): BelongsToMany
    {
        return $this->belongsToMany(Reason::class)->orderBy('position');
    }

    /**
     * The day's photos, cover first.
     *
     * @return HasMany<MemoryPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(MemoryPhoto::class)->orderBy('position')->orderBy('id');
    }
}
