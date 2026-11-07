<?php

namespace App\Http\Resources;

use App\Models\Memory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A check-in, addressed by its date. Database IDs are never sent.
 *
 * @mixin Memory
 */
class MemoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->date->toDateString(),
            'mood' => (new MoodResource($this->mood))->resolve($request),
            'reasons' => ReasonResource::collection($this->reasons)->resolve($request),
            'body' => $this->body,
        ];
    }
}
