<?php

namespace App\Http\Controllers;

use App\Http\Resources\MemoryResource;
use App\Models\User;
use App\Services\RecapBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecapController extends Controller
{
    /**
     * Opens the latest year with entries, or this year when there are none.
     */
    public function index(Request $request, RecapBuilder $recaps): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $year = $recaps->yearsWithEntries($user)[0] ?? $user->today()->year;

        return redirect()->route('recap.show', $year);
    }

    /**
     * One year's recap. Any year with entries is available, and this year
     * always is, as year to date. Other years return not found.
     */
    public function show(Request $request, int $year, RecapBuilder $recaps): Response
    {
        /** @var User $user */
        $user = $request->user();
        $today = $user->today();
        $years = $recaps->yearsWithEntries($user);

        abort_unless($year === $today->year || in_array($year, $years, true), 404);

        $recap = $recaps->build($user, $year, $today);

        return Inertia::render('Recap', [
            'recap' => [
                ...$recap,
                'goodMemories' => MemoryResource::collection($recap['goodMemories'])->resolve($request),
            ],
            'years' => $years,
            'today' => $today->toDateString(),
        ]);
    }
}
