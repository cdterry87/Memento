<?php

namespace App\Http\Controllers;

use App\Actions\DeleteMemory;
use App\Actions\StoreMemory;
use App\Http\Requests\StoreMemoryRequest;
use App\Http\Resources\MemoryResource;
use App\Http\Resources\MoodResource;
use App\Http\Resources\ReasonResource;
use App\Models\Memory;
use App\Models\Mood;
use App\Models\Reason;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A single day, addressed by date. Every query goes through the signed-in
 * user, so another person's entry can never be read or changed.
 */
class DayController extends Controller
{
    /**
     * The day view. A day without an entry goes straight to the check-in.
     */
    public function show(Request $request, CarbonImmutable $date): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $memory = $this->memoryFor($user, $date);

        if (! $memory) {
            return to_route('days.check-in', $date->toDateString());
        }

        return Inertia::render('Day', [
            'date' => $date->toDateString(),
            'isToday' => $this->isToday($user, $date),
            'memory' => (new MemoryResource($memory))->resolve($request),
        ]);
    }

    /**
     * The check-in flow for a new or existing entry.
     */
    public function edit(Request $request, CarbonImmutable $date): Response
    {
        /** @var User $user */
        $user = $request->user();
        $memory = $this->memoryFor($user, $date);

        $moods = Mood::pickable()->get();
        $reasons = Reason::pickable()->get();

        // A retired option stays available on the entry that already uses it.
        if ($memory && ! $moods->contains($memory->mood)) {
            $moods->push($memory->mood);
        }

        $reasons = $reasons->merge($memory->reasons ?? [])->unique('id')->sortBy('position')->values();

        $initialMood = $request->string('mood')->toString();

        return Inertia::render('CheckIn', [
            'date' => $date->toDateString(),
            'isToday' => $this->isToday($user, $date),
            'memory' => $memory ? (new MemoryResource($memory))->resolve($request) : null,
            'moods' => MoodResource::collection($moods)->resolve($request),
            'reasons' => ReasonResource::collection($reasons)->resolve($request),
            'initialMood' => $moods->contains('slug', $initialMood) ? $initialMood : null,
        ]);
    }

    /**
     * Create or update the entry for this date.
     */
    public function update(StoreMemoryRequest $request, CarbonImmutable $date, StoreMemory $storeMemory): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $storeMemory->handle($user, $date, $request->mood(), $request->reasons(), $request->body());

        $destination = $this->isToday($user, $date)
            ? to_route('today')
            : to_route('days.show', $date->toDateString());

        return $destination->with('status', 'check-in-saved');
    }

    /**
     * Delete the entry for this date.
     */
    public function destroy(Request $request, CarbonImmutable $date, DeleteMemory $deleteMemory): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $deleteMemory->handle($user->memories()->where('date', $date->toDateString())->firstOrFail());

        return to_route('timeline')->with('status', 'memory-deleted');
    }

    private function isToday(User $user, CarbonImmutable $date): bool
    {
        return $date->isSameDay($user->today());
    }

    private function memoryFor(User $user, CarbonImmutable $date): ?Memory
    {
        return $user->memories()
            ->with(['mood', 'reasons', 'photos'])
            ->where('date', $date->toDateString())
            ->first();
    }
}
