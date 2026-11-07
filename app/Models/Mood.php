<?php

namespace App\Models;

use Database\Factories\MoodFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A mood a check-in can record. Rows come from database/data/moods.php.
 *
 * @property int $id
 * @property string $slug
 * @property string $label
 * @property string $emoji
 * @property int $score
 * @property int $position
 * @property bool $is_active
 */
class Mood extends Model
{
    /** @use HasFactory<MoodFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Moods that can be picked for a new check-in, in picker order.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function pickable(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('position');
    }
}
