<?php

namespace App\Http\Controllers;

use App\Http\Resources\MemoryResource;
use App\Http\Resources\MoodResource;
use App\Models\Mood;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TodayController extends Controller
{
    /**
     * The home screen: a greeting and today's check-in.
     */
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $today = $user->today();

        $memory = $user->memories()
            ->with(['mood', 'reasons'])
            ->where('date', $today->toDateString())
            ->first();

        return Inertia::render('Today', [
            'today' => $today->toDateString(),
            'memory' => $memory ? (new MemoryResource($memory))->resolve($request) : null,
            'moods' => $memory ? [] : MoodResource::collection(Mood::pickable()->get())->resolve($request),
        ]);
    }
}
