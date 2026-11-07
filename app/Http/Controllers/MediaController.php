<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Serves private media. Each request is authorized here, then redirected to
 * a short-lived signed URL, so the file itself comes straight from storage
 * (with Range support) without passing through PHP.
 */
class MediaController extends Controller
{
    /**
     * How long a signed URL works, in minutes.
     */
    public const SIGNED_URL_MINUTES = 10;

    /**
     * A photo or its thumbnail.
     */
    public function photo(Request $request, string $photo, string $variant): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $memoryPhoto = $user->photos()->where('memory_photos.ulid', $photo)->firstOrFail();

        return $this->redirectToSignedUrl($variant === 'thumb' ? $memoryPhoto->thumbnail_path : $memoryPhoto->path);
    }

    private function redirectToSignedUrl(string $path): RedirectResponse
    {
        $url = $this->signingDisk()->temporaryUrl($path, now()->addMinutes(self::SIGNED_URL_MINUTES));

        return redirect()->away($url)->header('Cache-Control', 'private, no-store');
    }

    /**
     * Under Sail the app reaches RustFS at its container hostname, which the
     * browser can't resolve. Signing needs no request, so a copy of the disk
     * pointed at the browser-facing host signs instead. Rewriting the host
     * afterwards would break the signature.
     */
    private function signingDisk(): Filesystem
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
