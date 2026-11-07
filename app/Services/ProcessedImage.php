<?php

namespace App\Services;

/**
 * A photo ready to store: the full image and its thumbnail, both JPEG with
 * no metadata.
 */
final readonly class ProcessedImage
{
    public function __construct(
        public string $full,
        public string $thumbnail,
        public int $width,
        public int $height,
    ) {}
}
