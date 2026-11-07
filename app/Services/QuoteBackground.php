<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Picks the background photo for the quote card from the images in
 * public/inspirational-quote-backgrounds. Adding or removing a file is all it
 * takes to change the set.
 *
 * The files are shuffled into a fixed order by a hash of their names, so
 * similar photos such as beach1 and beach2 don't fall on days in a row, and
 * every photo has its day before any repeats.
 */
class QuoteBackground
{
    public const DIRECTORY = 'inspirational-quote-backgrounds';

    private const EXTENSIONS = ['jpg', 'jpeg', 'webp'];

    public function __construct(private ?string $directory = null) {}

    /**
     * The public URL path of the photo for a date, or null when there are none.
     */
    public function forDate(CarbonInterface $date): ?string
    {
        $names = $this->imageNames();

        if ($names === []) {
            return null;
        }

        $name = $names[$date->dayOfYear % count($names)];

        return '/'.self::DIRECTORY.'/'.rawurlencode($name);
    }

    /**
     * @return list<string>
     */
    private function imageNames(): array
    {
        $directory = $this->directory ?? public_path(self::DIRECTORY);

        if (! File::isDirectory($directory)) {
            return [];
        }

        $names = collect(File::files($directory))
            ->filter(fn (SplFileInfo $file) => in_array(strtolower($file->getExtension()), self::EXTENSIONS, true))
            ->map(fn (SplFileInfo $file) => $file->getFilename())
            ->sortBy(fn (string $name) => md5($name))
            ->all();

        return array_values($names);
    }
}
