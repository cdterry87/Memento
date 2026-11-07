<?php

namespace App\Http\Controllers;

use App\Http\Resources\MemoryResource;
use App\Http\Resources\MoodResource;
use App\Http\Resources\QuoteResource;
use App\Models\Mood;
use App\Models\User;
use App\Services\DailyQuote;
use App\Services\OnThisDay;
use App\Services\QuoteBackground;
use App\Services\RecapPromotion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TodayController extends Controller
{
    /**
     * The home screen: a greeting, today's check-in, entries from this day
     * in earlier years, the quote of the day, and the recap when it's due.
     */
    public function __invoke(
        Request $request,
        OnThisDay $onThisDay,
        DailyQuote $dailyQuote,
        RecapPromotion $recapPromotion,
        QuoteBackground $quoteBackground,
    ): Response {
        /** @var User $user */
        $user = $request->user();
        $today = $user->today();

        $memory = $user->memories()
            ->with(['mood', 'reasons', 'photos'])
            ->where('date', $today->toDateString())
            ->first();

        $quote = $user->show_quotes ? $dailyQuote->forDate($today) : null;

        return Inertia::render('Today', [
            'today' => $today->toDateString(),
            'memory' => $memory ? (new MemoryResource($memory))->resolve($request) : null,
            'moods' => $memory ? [] : MoodResource::collection(Mood::pickable()->get())->resolve($request),
            'onThisDay' => MemoryResource::collection($onThisDay->for($user, $today))->resolve($request),
            'quote' => $quote ? (new QuoteResource($quote))->resolve($request) : null,
            'quoteBackground' => $quote ? $quoteBackground->forDate($today) : null,
            'recapYear' => $recapPromotion->yearFor($user, $today),
        ]);
    }
}
