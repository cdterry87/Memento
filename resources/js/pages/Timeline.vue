<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import TimelineEntry from '@/components/TimelineEntry.vue';
import { useLinkNavigation } from '@/composables/useLinkNavigation';
import { formatMonthYear, monthKey } from '@/composables/useLocalDate';
import type { Memory } from '@/types';
import { Head, InfiniteScroll, usePage } from '@inertiajs/vue3';
import { mdiPlus } from '@mdi/js';
import { computed, ref } from 'vue';

const props = defineProps<{
    memories: { data: Memory[] };
}>();

const page = usePage();
const { navigate } = useLinkNavigation();

const snackbar = ref(page.props.status === 'memory-deleted');

/** Each entry, flagged when it is the first of its month so a heading goes above it. */
const entries = computed(() =>
    props.memories.data.map((memory, index, all) => ({
        memory,
        startsMonth: index === 0 || monthKey(all[index - 1].date) !== monthKey(memory.date),
    })),
);
</script>

<template>
    <Head title="Timeline" />

    <section>
        <h1 class="text-h4 mb-6">Timeline</h1>

        <EmptyState
            v-if="!entries.length"
            emoji="📖"
            title="Nothing here yet"
            text="Each day you check in will appear here, newest first."
        >
            <v-btn
                class="mt-6"
                color="primary"
                :prepend-icon="mdiPlus"
                href="/today"
                @click="navigate($event, '/today')"
            >
                Check in today
            </v-btn>
        </EmptyState>

        <InfiniteScroll v-else data="memories" as="ol" class="timeline" preserve-url :buffer="600">
            <li v-for="{ memory, startsMonth } in entries" :key="memory.date">
                <h2 v-if="startsMonth" class="timeline__month text-overline text-medium-emphasis">
                    {{ formatMonthYear(memory.date) }}
                </h2>
                <TimelineEntry :memory="memory" />
            </li>

            <template #loading>
                <div class="d-flex justify-center py-6">
                    <v-progress-circular
                        indeterminate
                        color="primary"
                        aria-label="Loading more days"
                    />
                </div>
            </template>
        </InfiniteScroll>

        <v-snackbar v-model="snackbar" :timeout="2500">Entry deleted.</v-snackbar>
    </section>
</template>

<style scoped>
.timeline {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 0 0 24px;
    list-style: none;
}

.timeline__month {
    margin: 12px 0 4px;
}

.timeline > li:first-child .timeline__month {
    margin-top: 0;
}
</style>
