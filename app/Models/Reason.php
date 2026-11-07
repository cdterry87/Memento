<?php

namespace App\Models;

use Database\Factories\ReasonFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A reason behind a mood. Rows come from database/data/reasons.php.
 *
 * @property int $id
 * @property string $slug
 * @property string $label
 * @property string $emoji
 * @property int $position
 * @property bool $is_active
 */
class Reason extends Model
{
    /** @use HasFactory<ReasonFactory> */
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
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Reasons that can be picked for a new check-in, in picker order.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function pickable(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('position');
    }
}
