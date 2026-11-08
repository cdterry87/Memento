<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\SignedMediaUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Serves private media. Each request is authorized here, then redirected to
 * a short-lived signed URL.
 */
class MediaController extends Controller
{
    /**
     * A photo or its thumbnail.
     */
    public function photo(Request $request, string $photo, string $variant): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $memoryPhoto = $user->photos()->where('memory_photos.ulid', $photo)->firstOrFail();

        return SignedMediaUrl::redirect($variant === 'thumb' ? $memoryPhoto->thumbnail_path : $memoryPhoto->path);
    }
}
