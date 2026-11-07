<?php

namespace App\Http\Resources;

use App\Models\MemoryPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A photo, known to the browser only by its ULID. The URLs point to the
 * authorizing media route, never to storage.
 *
 * @mixin MemoryPhoto
 */
class PhotoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ulid,
            'url' => route('media.photos', [$this->ulid, 'full'], absolute: false),
            'thumbnailUrl' => route('media.photos', [$this->ulid, 'thumb'], absolute: false),
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
