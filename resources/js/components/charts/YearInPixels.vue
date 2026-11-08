<script setup lang="ts">
/**
 * Every day of a year as a small tile coloured by that day's mood: months
 * across, days down. Days with an entry are links to the day.
 *
 * Only one tile is in the tab order. The arrow keys move between entries in
 * date order, Home and End jump to the first and last, so the grid is a
 * single stop for keyboard users instead of hundreds.
 */
import { useLinkNavigation } from '@/composables/useLinkNavigation';
import { formatLongDate, monthName } from '@/composables/useLocalDate';
import type { MoodPixel } from '@/types';
import { computed, nextTick, ref } from 'vue';

const props = defineProps<{
    year: number;
    pixels: MoodPixel[];
}>();

const { navigate } = useLinkNavigation();

const pixelsByDate = computed(() => new Map(props.pixels.map((pixel) => [pixel.date, pixel])));

function dateFor(month: number, day: number): string | null {
    const date = new Date(Date.UTC(props.year, month - 1, day));

    return date.getUTCMonth() === month - 1 ? date.toISOString().slice(0, 10) : null;
}

const rows = computed(() =>
    Array.from({ length: 31 }, (_, index) => {
        const day = index + 1;

        return {
            day,
            cells: Array.from({ length: 12 }, (_, monthIndex) => {
                const date = dateFor(monthIndex + 1, day);

                return {
                    key: `${monthIndex + 1}-${day}`,
                    date,
                    pixel: date ? pixelsByDate.value.get(date) : undefined,
                };
            }),
        };
    }),
);

/** The entry that is in the tab order. */
const activeDate = ref(props.pixels[0]?.date ?? null);
const grid = ref<HTMLElement | null>(null);

async function moveFocus(event: KeyboardEvent): Promise<void> {
    const dates = props.pixels.map((pixel) => pixel.date);
    const current = dates.indexOf(activeDate.value ?? '');
    const targets: Record<string, number> = {
        ArrowRight: current + 1,
        ArrowDown: current + 1,
        ArrowLeft: current - 1,
        ArrowUp: current - 1,
        Home: 0,
        End: dates.length - 1,
    };

    if (!(event.key in targets)) {
        return;
    }

    event.preventDefault();
    const next = Math.min(Math.max(targets[event.key], 0), dates.length - 1);
    activeDate.value = dates[next];

    await nextTick();
    grid.value?.querySelector<HTMLElement>(`[data-date="${dates[next]}"]`)?.focus();
}

function pixelLabel(pixel: MoodPixel): string {
    return `${formatLongDate(pixel.date)}: ${pixel.label}`;
}
</script>

<template>
    <figure class="year-in-pixels">
        <div ref="grid" class="year-in-pixels__grid" @keydown="moveFocus">
            <span aria-hidden="true" />
            <span
                v-for="month in 12"
                :key="month"
                class="year-in-pixels__month"
                aria-hidden="true"
                :title="monthName(month)"
            >
                {{ monthName(month, { short: true }).slice(0, 1) }}
            </span>

            <template v-for="row in rows" :key="row.day">
                <span class="year-in-pixels__day" aria-hidden="true">
                    {{ row.day % 5 === 0 || row.day === 1 ? row.day : '' }}
                </span>
                <template v-for="cell in row.cells" :key="cell.key">
                    <a
                        v-if="cell.pixel"
                        :href="`/days/${cell.date}`"
                        :data-date="cell.date"
                        class="year-in-pixels__cell year-in-pixels__cell--entry"
                        :style="{ backgroundColor: `rgb(var(--v-theme-mood-${cell.pixel.score}))` }"
                        :aria-label="pixelLabel(cell.pixel)"
                        :title="`${cell.pixel.emoji} ${pixelLabel(cell.pixel)}`"
                        :tabindex="cell.date === activeDate ? 0 : -1"
                        @focus="activeDate = cell.pixel.date"
                        @click="navigate($event, `/days/${cell.date}`)"
                    />
                    <span
                        v-else
                        class="year-in-pixels__cell"
                        :class="{ 'year-in-pixels__cell--none': !cell.date }"
                        aria-hidden="true"
                    />
                </template>
            </template>
        </div>

        <figcaption class="year-in-pixels__legend text-caption text-medium-emphasis">
            <span>Harder days</span>
            <span
                v-for="score in [1, 2, 3, 4, 5]"
                :key="score"
                class="year-in-pixels__swatch"
                :style="{ backgroundColor: `rgb(var(--v-theme-mood-${score}))` }"
                aria-hidden="true"
            />
            <span>Brighter days</span>
            <span class="visually-hidden">
                Each tile is a day, coloured by mood. Use the arrow keys to move between days with
                an entry.
            </span>
        </figcaption>
    </figure>
</template>

<style scoped>
.year-in-pixels {
    margin: 0;
}

.year-in-pixels__grid {
    display: grid;
    grid-template-columns: 24px repeat(12, minmax(0, 1fr));
    grid-auto-rows: 22px;
    gap: 2px 4px;
    align-items: center;
}

.year-in-pixels__month,
.year-in-pixels__day {
    font-size: 0.75rem;
    color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
    text-align: center;
}

.year-in-pixels__cell {
    display: block;
    height: 100%;
    border-radius: 6px;
    background: rgba(var(--v-theme-on-surface), 0.06);
}

.year-in-pixels__cell--none {
    background: transparent;
}

.year-in-pixels__cell--entry {
    transition: transform 120ms ease;
}

.year-in-pixels__cell--entry:hover {
    transform: scale(1.12);
}

.year-in-pixels__cell--entry:focus-visible {
    outline: 3px solid rgb(var(--v-theme-primary));
    outline-offset: 2px;
}

.year-in-pixels__legend {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 16px;
}

.year-in-pixels__swatch {
    width: 16px;
    height: 16px;
    border-radius: 4px;
}
</style>
