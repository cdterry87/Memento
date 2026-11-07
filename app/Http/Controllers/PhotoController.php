<?php

namespace App\Http\Controllers;

use App\Actions\DeletePhoto;
use App\Actions\ReorderPhotos;
use App\Actions\StorePhoto;
use App\Http\Requests\ReorderPhotosRequest;
use App\Http\Requests\StorePhotoRequest;
use App\Http\Resources\PhotoResource;
use App\Models\Memory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Photos on a day's entry, always reached through the signed-in user.
 */
class PhotoController extends Controller
{
    /**
     * Add one photo. Uploads are separate requests made after the check-in
     * is saved, so a failed upload never loses the entry.
     */
    public function store(StorePhotoRequest $request, CarbonImmutable $date, StorePhoto $storePhoto): JsonResponse
    {
        $photo = $storePhoto->handle($this->memoryFor($request, $date), $request->file('photo'));

        return (new PhotoResource($photo))->response()->setStatusCode(201);
    }

    /**
     * Put the day's photos in a new order.
     */
    public function order(ReorderPhotosRequest $request, CarbonImmutable $date, ReorderPhotos $reorderPhotos): RedirectResponse
    {
        $reorderPhotos->handle($this->memoryFor($request, $date), $request->photos());

        return back();
    }

    /**
     * Delete one photo and its files.
     */
    public function destroy(Request $request, CarbonImmutable $date, string $photo, DeletePhoto $deletePhoto): RedirectResponse
    {
        $deletePhoto->handle($this->memoryFor($request, $date)->photos()->where('ulid', $photo)->firstOrFail());

        return back()->with('status', 'photo-deleted');
    }

    private function memoryFor(Request $request, CarbonImmutable $date): Memory
    {
        /** @var User $user */
        $user = $request->user();

        return $user->memories()->where('date', $date->toDateString())->firstOrFail();
    }
}
