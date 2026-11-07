<?php

namespace App\Http\Resources;

use App\Models\Reason;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Reason
 */
class ReasonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{slug: string, label: string, emoji: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'label' => $this->label,
            'emoji' => $this->emoji,
        ];
    }
}
