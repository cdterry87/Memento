<script setup lang="ts">
/**
 * One day on the timeline: a compact card linking to the day view.
 */
import { useLinkNavigation } from '@/composables/useLinkNavigation';
import { dayOfMonth, formatLongDate, formatShortWeekday } from '@/composables/useLocalDate';
import type { Memory } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    memory: Memory;
}>();

const { navigate } = useLinkNavigation();

const href = computed(() => `/days/${props.memory.date}`);
const reasonLabels = computed(() => props.memory.reasons.map((reason) => reason.label).join(', '));
</script>

<template>
    <v-card class="timeline-entry pa-4" :href="href" @click="navigate($event, href)">
        <div class="d-flex ga-4">
            <div class="timeline-entry__date" aria-hidden="true">
                <span class="text-caption text-medium-emphasis">
                    {{ formatShortWeekday(memory.date) }}
                </span>
                <span class="text-h6">{{ dayOfMonth(memory.date) }}</span>
            </div>

            <div class="timeline-entry__content">
                <p class="visually-hidden">{{ formatLongDate(memory.date, { withYear: true }) }}</p>

                <div class="d-flex align-center flex-wrap ga-2">
                    <span class="timeline-entry__mood" aria-hidden="true">{{
                        memory.mood.emoji
                    }}</span>
                    <span class="text-subtitle-1 font-weight-medium">{{ memory.mood.label }}</span>
                    <span
                        v-if="memory.reasons.length"
                        class="timeline-entry__reasons"
                        :title="reasonLabels"
                    >
                        <span aria-hidden="true">
                            {{ memory.reasons.map((reason) => reason.emoji).join(' ') }}
                        </span>
                        <span class="visually-hidden">Because of {{ reasonLabels }}</span>
                    </span>
                </div>

                <p
                    v-if="memory.body"
                    class="timeline-entry__body text-body-2 text-medium-emphasis mt-1"
                >
                    {{ memory.body }}
                </p>
            </div>
        </div>
    </v-card>
</template>

<style scoped>
.timeline-entry__date {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: none;
    width: 52px;
    height: 56px;
    border-radius: 12px;
    background: rgba(var(--v-theme-primary), 0.08);
    line-height: 1.2;
}

.timeline-entry__content {
    min-width: 0;
    flex: 1;
}

.timeline-entry__mood {
    font-size: 1.75rem;
    line-height: 1;
}

.timeline-entry__reasons {
    font-size: 1.1rem;
    letter-spacing: 0.1em;
}

.timeline-entry__photos {
    display: flex;
    gap: 6px;
}

.timeline-entry__photo {
    width: 56px;
    height: 56px;
    border-radius: 10px;
    object-fit: cover;
    background: rgba(var(--v-theme-on-surface), 0.06);
}

.timeline-entry__body {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    overflow-wrap: anywhere;
}
</style>
