<?php

namespace App\Services;

use App\Models\Memory;
use App\Models\Reason;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Builds one user's recap for a year, computed on request (spec section 6.5).
 * A year is at most 366 entries, so everything is worked out in memory from a
 * single query. The current year counts as year to date.
 *
 * @phpstan-type MoodShare array{slug: string, label: string, emoji: string, score: int, count: int, share: float}
 * @phpstan-type ReasonCount array{slug: string, label: string, emoji: string, count: int}
 * @phpstan-type MonthAverage array{month: int, average: float|null, count: int}
 * @phpstan-type Pixel array{date: string, score: int, emoji: string, label: string}
 * @phpstan-type RecapData array{
 *     year: int,
 *     isYearToDate: bool,
 *     daysInPeriod: int,
 *     checkIns: int,
 *     longestStreak: int,
 *     photos: int,
 *     averageScore: float|null,
 *     pixels: list<Pixel>,
 *     months: list<MonthAverage>,
 *     moodMix: list<MoodShare>,
 *     topReasons: list<ReasonCount>,
 *     lifted: list<array{slug: string, label: string, emoji: string, average: float}>,
 *     bestMonth: MonthAverage|null,
 *     goodMemories: EloquentCollection<int, Memory>,
 * }
 */
class RecapBuilder
{
    /** How many reasons "What shaped your year" lists. */
    public const TOP_REASONS = 6;

    /** "What lifted you" needs this many check-ins in the year before it says anything. */
    public const LIFTED_MIN_CHECK_INS = 20;

    /** A reason must appear on this many days to count towards "What lifted you". */
    public const LIFTED_MIN_USES = 5;

    /** How far above the year's average a reason's days must score to have lifted it. */
    public const LIFTED_MARGIN = 0.25;

    public const LIFTED_LIMIT = 3;

    /** A month needs this many check-ins to be the best month, so one great day can't win it. */
    public const BEST_MONTH_MIN_CHECK_INS = 5;

    public const GOOD_MEMORY_MIN_SCORE = 4;

    public const GOOD_MEMORY_LIMIT = 12;

    /**
     * The years the user has entries in, newest first.
     *
     * @return list<int>
     */
    public function yearsWithEntries(User $user): array
    {
        return array_values($user->memories()
            ->pluck('date')
            ->map(fn (CarbonImmutable $date) => $date->year)
            ->unique()
            ->sortDesc()
            ->all());
    }

    /**
     * @return RecapData
     */
    public function build(User $user, int $year, CarbonImmutable $today): array
    {
        $isYearToDate = $year === $today->year;
        $start = CarbonImmutable::parse("{$year}-01-01");
        $end = $isYearToDate ? $today : $start->endOfYear()->startOfDay();

        $memories = $user->memories()
            ->with(['mood', 'reasons', 'photos'])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get();

        $averageScore = $memories->isEmpty() ? null : round((float) $memories->avg('mood.score'), 1);
        $months = $this->monthlyAverages($memories, $end);

        return [
            'year' => $year,
            'isYearToDate' => $isYearToDate,
            'daysInPeriod' => $end->dayOfYear,
            'checkIns' => $memories->count(),
            'longestStreak' => $this->longestStreak($memories),
            'photos' => $memories->sum(fn (Memory $memory) => $memory->photos->count()),
            'averageScore' => $averageScore,
            'pixels' => $this->pixels($memories),
            'months' => $months,
            'moodMix' => $this->moodMix($memories),
            'topReasons' => $this->topReasons($memories),
            'lifted' => $this->lifted($memories),
            'bestMonth' => $this->bestMonth($months),
            'goodMemories' => $this->goodMemories($memories),
        ];
    }

    /**
     * The most days in a row with an entry, counting only days inside the year.
     *
     * @param  EloquentCollection<int, Memory>  $memories  in date order
     */
    private function longestStreak(EloquentCollection $memories): int
    {
        $longest = 0;
        $current = 0;
        $previous = null;

        foreach ($memories as $memory) {
            $current = $previous !== null && $previous->addDay()->isSameDay($memory->date) ? $current + 1 : 1;
            $longest = max($longest, $current);
            $previous = $memory->date;
        }

        return $longest;
    }

    /**
     * @param  EloquentCollection<int, Memory>  $memories
     * @return list<Pixel>
     */
    private function pixels(EloquentCollection $memories): array
    {
        return array_values($memories->map(fn (Memory $memory) => [
            'date' => $memory->date->toDateString(),
            'score' => $memory->mood->score,
            'emoji' => $memory->mood->emoji,
            'label' => $memory->mood->label,
        ])->all());
    }

    /**
     * The average mood score for each month up to the end of the period. A
     * month with no entries has no average, so a chart can leave a gap.
     *
     * @param  EloquentCollection<int, Memory>  $memories
     * @return list<MonthAverage>
     */
    private function monthlyAverages(EloquentCollection $memories, CarbonImmutable $end): array
    {
        $byMonth = $memories->groupBy(fn (Memory $memory) => $memory->date->month);

        return array_values(collect(range(1, $end->month))->map(function (int $month) use ($byMonth) {
            /** @var Collection<int, Memory> $entries */
            $entries = $byMonth->get($month, collect());

            return [
                'month' => $month,
                'average' => $entries->isEmpty() ? null : round((float) $entries->avg('mood.score'), 1),
                'count' => $entries->count(),
            ];
        })->all());
    }

    /**
     * @param  EloquentCollection<int, Memory>  $memories
     * @return list<MoodShare>
     */
    private function moodMix(EloquentCollection $memories): array
    {
        $total = $memories->count();

        return array_values($memories->groupBy('mood_id')
            ->map(function (Collection $entries) use ($total) {
                /** @var Memory $first */
                $first = $entries->first();

                return [
                    'slug' => $first->mood->slug,
                    'label' => $first->mood->label,
                    'emoji' => $first->mood->emoji,
                    'score' => $first->mood->score,
                    'count' => $entries->count(),
                    'share' => round($entries->count() / $total, 3),
                ];
            })
            ->sortBy([['count', 'desc'], ['score', 'desc']])
            ->all());
    }

    /**
     * @param  EloquentCollection<int, Memory>  $memories
     * @return Collection<int, array{reason: Reason, memories: Collection<int, Memory>}>
     */
    private function daysByReason(EloquentCollection $memories): Collection
    {
        $reasons = [];
        $days = [];

        foreach ($memories as $memory) {
            foreach ($memory->reasons as $reason) {
                $reasons[$reason->id] = $reason;
                $days[$reason->id][] = $memory;
            }
        }

        return collect(array_keys($reasons))->map(fn (int $id) => [
            'reason' => $reasons[$id],
            'memories' => collect($days[$id]),
        ]);
    }

    /**
     * @param  EloquentCollection<int, Memory>  $memories
     * @return list<ReasonCount>
     */
    private function topReasons(EloquentCollection $memories): array
    {
        return array_values($this->daysByReason($memories)
            ->sortBy([
                fn (array $a, array $b) => $b['memories']->count() <=> $a['memories']->count(),
                fn (array $a, array $b) => $a['reason']->position <=> $b['reason']->position,
            ])
            ->take(self::TOP_REASONS)
            ->map(fn (array $group) => [
                'slug' => $group['reason']->slug,
                'label' => $group['reason']->label,
                'emoji' => $group['reason']->emoji,
                'count' => $group['memories']->count(),
            ])
            ->all());
    }

    /**
     * Reasons whose days scored clearly above the year's average. Shown only
     * once there is enough data for the comparison to mean something.
     *
     * @param  EloquentCollection<int, Memory>  $memories
     * @return list<array{slug: string, label: string, emoji: string, average: float}>
     */
    private function lifted(EloquentCollection $memories): array
    {
        if ($memories->count() < self::LIFTED_MIN_CHECK_INS) {
            return [];
        }

        $yearAverage = (float) $memories->avg('mood.score');

        return array_values($this->daysByReason($memories)
            ->filter(fn (array $group) => $group['memories']->count() >= self::LIFTED_MIN_USES)
            ->map(fn (array $group) => [
                'slug' => $group['reason']->slug,
                'label' => $group['reason']->label,
                'emoji' => $group['reason']->emoji,
                'average' => round((float) $group['memories']->avg('mood.score'), 1),
                'exact' => (float) $group['memories']->avg('mood.score'),
            ])
            ->filter(fn (array $reason) => $reason['exact'] >= $yearAverage + self::LIFTED_MARGIN)
            ->sortByDesc('exact')
            ->take(self::LIFTED_LIMIT)
            ->map(fn (array $reason) => [
                'slug' => $reason['slug'],
                'label' => $reason['label'],
                'emoji' => $reason['emoji'],
                'average' => $reason['average'],
            ])
            ->all());
    }

    /**
     * The month with the highest average, among months with enough entries.
     * Ties go to the month with more entries, then the earlier month.
     *
     * @param  list<MonthAverage>  $months
     * @return MonthAverage|null
     */
    private function bestMonth(array $months): ?array
    {
        return collect($months)
            ->filter(fn (array $month) => $month['count'] >= self::BEST_MONTH_MIN_CHECK_INS)
            ->sortBy([['average', 'desc'], ['count', 'desc'], ['month', 'asc']])
            ->first();
    }

    /**
     * Up to 12 days that scored 4 or 5, preferring those with a photo or a
     * message, and spread across the year by taking from each month in turn.
     *
     * @param  EloquentCollection<int, Memory>  $memories
     * @return EloquentCollection<int, Memory>
     */
    private function goodMemories(EloquentCollection $memories): EloquentCollection
    {
        $richness = fn (Memory $memory) => ($memory->photos->isNotEmpty() ? 2 : 0) + ($memory->body !== null ? 1 : 0);

        $queues = $memories
            ->filter(fn (Memory $memory) => $memory->mood->score >= self::GOOD_MEMORY_MIN_SCORE)
            ->groupBy(fn (Memory $memory) => $memory->date->month)
            ->map(fn (Collection $entries) => $entries
                ->sortBy([
                    fn (Memory $a, Memory $b) => $richness($b) <=> $richness($a),
                    fn (Memory $a, Memory $b) => $b->mood->score <=> $a->mood->score,
                    fn (Memory $a, Memory $b) => $a->date <=> $b->date,
                ])
                ->values()
                ->all())
            ->all();

        $picked = new EloquentCollection;

        while ($picked->count() < self::GOOD_MEMORY_LIMIT && $queues !== []) {
            foreach (array_keys($queues) as $month) {
                if ($picked->count() >= self::GOOD_MEMORY_LIMIT) {
                    break;
                }

                $picked->push(array_shift($queues[$month]));

                if ($queues[$month] === []) {
                    unset($queues[$month]);
                }
            }
        }

        return $picked->sortBy('date')->values();
    }
}
