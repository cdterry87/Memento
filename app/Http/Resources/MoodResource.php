<?php

namespace App\Http\Resources;

use App\Models\Mood;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Mood
 */
class MoodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{slug: string, label: string, emoji: string, score: int}
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'label' => $this->label,
            'emoji' => $this->emoji,
            'score' => $this->score,
        ];
    }
}
