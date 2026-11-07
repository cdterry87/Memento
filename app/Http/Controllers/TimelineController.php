<?php

namespace App\Http\Controllers;

use App\Http\Resources\MemoryResource;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimelineController extends Controller
{
    /**
     * Number of entries loaded per scroll.
     */
    public const PER_PAGE = 20;

    /**
     * Every day with an entry, newest first, loaded a page at a time as the
     * user scrolls. Days without an entry are simply absent.
     */
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Timeline', [
            'memories' => Inertia::scroll(fn () => MemoryResource::collection(
                $user->memories()
                    ->with(['mood', 'reasons', 'photos'])
                    ->orderByDesc('date')
                    ->cursorPaginate(self::PER_PAGE)
            )),
        ]);
    }
}
