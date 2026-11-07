<?php

namespace App\Http\Resources;

use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The quote of the day, without its database ID.
 *
 * @mixin Quote
 */
class QuoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{text: string, author: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'text' => $this->text,
            'author' => $this->author,
        ];
    }
}
