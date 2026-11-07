<?php

namespace App\Services;

use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;

/**
 * Prepares an uploaded photo for storage. Decoding applies the EXIF
 * orientation, and re-encoding strips every piece of metadata, including GPS
 * location. Re-encoding also neutralizes anything hidden in the original file.
 */
class ImageProcessor
{
    /**
     * Longest edge of the stored photo, in pixels.
     */
    public const FULL_EDGE = 2048;

    /**
     * Longest edge of the thumbnail, in pixels.
     */
    public const THUMBNAIL_EDGE = 640;

    public function __construct(private ImageManager $images) {}

    public function process(string $path): ProcessedImage
    {
        $image = $this->images->decodePath($path)->scaleDown(self::FULL_EDGE, self::FULL_EDGE);

        $width = $image->width();
        $height = $image->height();
        $full = (string) $image->encode(new JpegEncoder(quality: 85, progressive: true, strip: true));

        $thumbnail = (string) $image
            ->scaleDown(self::THUMBNAIL_EDGE, self::THUMBNAIL_EDGE)
            ->encode(new JpegEncoder(quality: 80, progressive: true, strip: true));

        return new ProcessedImage($full, $thumbnail, $width, $height);
    }
}
