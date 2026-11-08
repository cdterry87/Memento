<script setup lang="ts">
/**
 * A year's recap as a scrolling story: the numbers, the year in pixels, how
 * the months felt, the mix of moods and reasons, and the good memories.
 */
import BarList, { type BarListItem } from '@/components/charts/BarList.vue';
import TrendChart from '@/components/charts/TrendChart.vue';
import YearInPixels from '@/components/charts/YearInPixels.vue';
import EmptyState from '@/components/EmptyState.vue';
import { useLinkNavigation } from '@/composables/useLinkNavigation';
import { formatLongDate, monthName } from '@/composables/useLocalDate';
import type { Recap } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    recap: Recap;
    years: number[];
    today: string;
}>();

const { navigate } = useLinkNavigation();

const heading = computed(() =>
    props.recap.isYearToDate ? `Your ${props.recap.year} so far` : `Your ${props.recap.year}`,
);

/** Years to switch between: every year with entries, plus this year, which always has a recap. */
const yearLinks = computed(() =>
    [...new Set([Number(props.today.slice(0, 4)), props.recap.year, ...props.years])].sort(
        (a, b) => b - a,
    ),
);

function plural(count: number, word: string): string {
    return `${count} ${word}${count === 1 ? '' : 's'}`;
}

const stats = computed(() => [
    {
        emoji: '📅',
        value: props.recap.checkIns,
        label: props.recap.checkIns === 1 ? 'check-in' : 'check-ins',
        detail: `out of ${plural(props.recap.daysInPeriod, 'day')}`,
    },
    {
        emoji: '🔥',
        value: props.recap.longestStreak,
        label: 'longest streak',
        detail: props.recap.longestStreak === 1 ? 'day' : 'days in a row',
    },
    {
        emoji: '📸',
        value: props.recap.photos,
        label: props.recap.photos === 1 ? 'photo' : 'photos',
        detail: 'kept',
    },
    {
        emoji: '💜',
        value: props.recap.averageScore ?? '–',
        label: 'average mood',
        detail: 'out of 5',
    },
]);

const moodBars = computed<BarListItem[]>(() =>
    props.recap.moodMix.map((mood) => ({
        key: mood.slug,
        emoji: mood.emoji,
        label: mood.label,
        value: mood.count,
        valueLabel: `${plural(mood.count, 'day')}, ${Math.round(mood.share * 100)}%`,
        color: `mood-${mood.score}`,
    })),
);

const reasonBars = computed<BarListItem[]>(() =>
    props.recap.topReasons.map((reason) => ({
        key: reason.slug,
        emoji: reason.emoji,
        label: reason.label,
        value: reason.count,
        valueLabel: plural(reason.count, 'day'),
    })),
);

const hasMonthlyTrend = computed(
    () => props.recap.months.filter((month) => month.average !== null).length > 1,
);
</script>

<template>
    <Head :title="`Recap ${recap.year}`" />

    <article class="recap d-flex flex-column ga-8">
        <header class="recap__hero">
            <p class="text-overline text-primary">Year in review</p>
            <h1 class="text-h3 mb-2">{{ heading }}</h1>
            <p v-if="recap.checkIns" class="text-body-1 text-medium-emphasis">
                {{ plural(recap.checkIns, 'day') }} of memories. Here's how your year looked.
            </p>

            <nav v-if="yearLinks.length > 1" class="d-flex flex-wrap ga-2 mt-4" aria-label="Years">
                <v-chip
                    v-for="year in yearLinks"
                    :key="year"
                    :href="`/recap/${year}`"
                    :color="year === recap.year ? 'primary' : undefined"
                    :variant="year === recap.year ? 'flat' : 'tonal'"
                    :aria-current="year === recap.year ? 'page' : undefined"
                    @click="navigate($event, `/recap/${year}`)"
                >
                    {{ year }}
                </v-chip>
            </nav>
        </header>

        <EmptyState
            v-if="!recap.checkIns"
            emoji="📖"
            :title="`Your ${recap.year} story starts here`"
            text="Check in for a few days and your year will start to take shape."
        >
            <v-btn color="primary" class="mt-4" href="/today" @click="navigate($event, '/today')">
                Check in today
            </v-btn>
        </EmptyState>

        <template v-else>
            <section aria-labelledby="recap-numbers">
                <h2 id="recap-numbers" class="text-h5 mb-4">The year in numbers</h2>
                <ul class="recap__stats">
                    <li v-for="stat in stats" :key="stat.label">
                        <v-card class="recap__stat pa-4 h-100">
                            <span class="recap__stat-emoji" aria-hidden="true">{{
                                stat.emoji
                            }}</span>
                            <p class="text-h4 font-weight-bold">{{ stat.value }}</p>
                            <p class="text-body-2 font-weight-bold">{{ stat.label }}</p>
                            <p class="text-caption text-medium-emphasis">{{ stat.detail }}</p>
                        </v-card>
                    </li>
                </ul>
            </section>

            <section aria-labelledby="recap-pixels">
                <h2 id="recap-pixels" class="text-h5 mb-1">Your year in moods</h2>
                <p class="text-body-2 text-medium-emphasis mb-4">
                    Every day you checked in, in colour. Tap a day to open it.
                </p>
                <v-card class="pa-4">
                    <YearInPixels :year="recap.year" :pixels="recap.pixels" />
                </v-card>
            </section>

            <section v-if="hasMonthlyTrend" aria-labelledby="recap-trend">
                <h2 id="recap-trend" class="text-h5 mb-1">How the year felt</h2>
                <p class="text-body-2 text-medium-emphasis mb-4">
                    Your average mood each month, from 1 to 5.
                </p>
                <v-card class="pa-4">
                    <TrendChart
                        :months="recap.months"
                        :title="`Average mood by month in ${recap.year}`"
                    />
                </v-card>
            </section>

            <section v-if="recap.bestMonth" aria-labelledby="recap-best-month">
                <v-card class="recap__highlight pa-6">
                    <span class="recap__highlight-emoji" aria-hidden="true">🌟</span>
                    <p class="text-overline">Best month</p>
                    <h2 id="recap-best-month" class="text-h5 mb-1">
                        {{ monthName(recap.bestMonth.month) }} was your brightest month
                    </h2>
                    <p class="text-body-1">
                        Averaging {{ recap.bestMonth.average }} out of 5 across
                        {{ plural(recap.bestMonth.count, 'check-in') }}.
                    </p>
                </v-card>
            </section>

            <section aria-labelledby="recap-mood-mix">
                <h2 id="recap-mood-mix" class="text-h5 mb-4">Mood mix</h2>
                <v-card class="pa-5">
                    <BarList :items="moodBars" label="Mood mix" />
                </v-card>
            </section>

            <section v-if="reasonBars.length" aria-labelledby="recap-reasons">
                <h2 id="recap-reasons" class="text-h5 mb-4">What shaped your year</h2>
                <v-card class="pa-5">
                    <BarList :items="reasonBars" label="What shaped your year" />
                </v-card>
            </section>

            <section v-if="recap.lifted.length" aria-labelledby="recap-lifted">
                <h2 id="recap-lifted" class="text-h5 mb-4">What lifted you</h2>
                <ul class="recap__lifted">
                    <li v-for="reason in recap.lifted" :key="reason.slug">
                        <v-card class="d-flex align-center ga-4 pa-4">
                            <span class="recap__lifted-emoji" aria-hidden="true">{{
                                reason.emoji
                            }}</span>
                            <p class="text-body-1">
                                Days with <strong>{{ reason.label.toLowerCase() }}</strong> averaged
                                {{ reason.average }} out of 5.
                            </p>
                        </v-card>
                    </li>
                </ul>
            </section>

            <section v-if="recap.goodMemories.length" aria-labelledby="recap-memories">
                <h2 id="recap-memories" class="text-h5 mb-1">Your good memories</h2>
                <p class="text-body-2 text-medium-emphasis mb-4">
                    A few bright days from across the year.
                </p>
                <ul class="recap__memories">
                    <li v-for="memory in recap.goodMemories" :key="memory.date">
                        <v-card
                            class="recap__memory h-100"
                            :href="`/days/${memory.date}`"
                            @click="navigate($event, `/days/${memory.date}`)"
                        >
                            <img
                                v-if="memory.photos.length"
                                :src="memory.photos[0].thumbnailUrl"
                                alt=""
                                class="recap__memory-photo"
                                loading="lazy"
                            />
                            <div
                                v-else
                                class="recap__memory-photo recap__memory-emoji"
                                aria-hidden="true"
                            >
                                {{ memory.mood.emoji }}
                            </div>
                            <div class="pa-4">
                                <p class="text-caption text-medium-emphasis">
                                    {{ formatLongDate(memory.date) }}
                                </p>
                                <p class="text-subtitle-2 font-weight-bold">
                                    <span aria-hidden="true">{{ memory.mood.emoji }}</span>
                                    {{ memory.mood.label }}
                                </p>
                                <p v-if="memory.body" class="recap__memory-body text-body-2 mt-1">
                                    {{ memory.body }}
                                </p>
                            </div>
                        </v-card>
                    </li>
                </ul>
            </section>

            <p class="text-center text-body-1 text-medium-emphasis">
                Thanks for keeping these days. Here's to the next ones. 💜
            </p>
        </template>
    </article>
</template>

<style scoped>
.recap__stats,
.recap__lifted,
.recap__memories {
    margin: 0;
    padding: 0;
    list-style: none;
}

.recap__stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.recap__stat-emoji {
    font-size: 1.75rem;
    line-height: 1;
}

.recap__highlight {
    background: linear-gradient(
        135deg,
        rgba(var(--v-theme-mood-4), 0.5),
        rgba(var(--v-theme-mood-5), 0.35)
    );
}

.recap__highlight-emoji,
.recap__lifted-emoji {
    font-size: 2rem;
    line-height: 1;
}

.recap__lifted {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.recap__memories {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.recap__memory-photo {
    display: block;
    width: 100%;
    aspect-ratio: 4 / 3;
    object-fit: cover;
    background: rgba(var(--v-theme-primary), 0.1);
}

.recap__memory-emoji {
    display: grid;
    place-items: center;
    font-size: 3rem;
}

.recap__memory-body {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    overflow-wrap: anywhere;
}
</style>
