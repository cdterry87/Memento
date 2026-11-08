<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Short-lived signed URLs for files on the private `media` disk. Callers
 * authorize first, then redirect, so the file comes straight from storage
 * (with Range support) without passing through PHP.
 */
final class SignedMediaUrl
{
    /**
     * How long a signed URL works, in minutes.
     */
    public const MINUTES = 10;

    /**
     * A redirect to the file that browsers and proxies won't cache.
     *
     * @param  array<string, string>  $options  S3 request options, such as ResponseContentDisposition.
     */
    public static function redirect(string $path, array $options = []): RedirectResponse
    {
        $url = self::disk()->temporaryUrl($path, now()->addMinutes(self::MINUTES), $options);

        return redirect()->away($url)->header('Cache-Control', 'private, no-store');
    }

    /**
     * Under Sail the app reaches RustFS at its container hostname, which the
     * browser can't resolve. Signing needs no request, so a copy of the disk
     * pointed at the browser-facing host signs instead. Rewriting the host
     * afterwards would break the signature.
     */
    private static function disk(): Filesystem
    {
        $disk = Storage::disk('media');

        /** @var array<string, mixed> $config */
        $config = config('filesystems.disks.media');

        if (empty($config['public_endpoint']) || ! $disk instanceof AwsS3V3Adapter) {
            return $disk;
        }

        return Storage::build([...$config, 'endpoint' => $config['public_endpoint']]);
    }
}
