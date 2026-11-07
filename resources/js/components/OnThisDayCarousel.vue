<script setup lang="ts">
/**
 * Entries from this day in earlier years, as a row of cards that can be
 * swiped or scrolled sideways. Each card opens its day.
 */
import TimelineEntry from '@/components/TimelineEntry.vue';
import { yearsAgoLabel, yearsBetween } from '@/composables/useLocalDate';
import type { Memory } from '@/types';

defineProps<{
    memories: Memory[];
    today: string;
}>();
</script>

<template>
    <section aria-labelledby="on-this-day-heading">
        <h2 id="on-this-day-heading" class="text-h6 mb-3">On this day</h2>

        <ul class="on-this-day" :class="{ 'on-this-day--single': memories.length === 1 }">
            <li v-for="memory in memories" :key="memory.date" class="on-this-day__item">
                <p class="text-overline text-medium-emphasis mb-1">
                    {{ yearsAgoLabel(yearsBetween(memory.date, today)) }}
                </p>
                <TimelineEntry :memory="memory" />
            </li>
        </ul>
    </section>
</template>

<style scoped>
.on-this-day {
    display: flex;
    gap: 12px;
    margin: 0;
    padding: 0 0 8px;
    list-style: none;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scrollbar-width: thin;
}

.on-this-day__item {
    flex: 0 0 min(85%, 340px);
    scroll-snap-align: start;
}

.on-this-day--single .on-this-day__item {
    flex-basis: 100%;
}
</style>
