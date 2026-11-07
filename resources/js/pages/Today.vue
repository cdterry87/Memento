<script setup lang="ts">
import EmojiPicker from '@/components/EmojiPicker.vue';
import MemoryCard from '@/components/MemoryCard.vue';
import OnThisDayCarousel from '@/components/OnThisDayCarousel.vue';
import QuoteCard from '@/components/QuoteCard.vue';
import RecapBanner from '@/components/RecapBanner.vue';
import { useLinkNavigation } from '@/composables/useLinkNavigation';
import { formatLongDate, greetingFor } from '@/composables/useLocalDate';
import type { Memory, Mood, Quote } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { mdiPencil } from '@mdi/js';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    today: string;
    memory: Memory | null;
    moods: Mood[];
    onThisDay: Memory[];
    quote: Quote | null;
    quoteBackground: string | null;
    recapYear: number | null;
}>();

const page = usePage();
const { navigate } = useLinkNavigation();

const user = computed(() => page.props.auth.user!);
const firstName = computed(() => user.value.name.split(' ')[0]);
const greeting = computed(() => greetingFor(user.value.timezone));
const checkInUrl = computed(() => `/days/${props.today}/check-in`);

const justSaved = computed(() => page.props.status === 'check-in-saved');
const snackbar = ref(justSaved.value);

/** Tapping a mood here has already started the check-in. */
const pickedMood = ref<string | null>(null);

watch(pickedMood, (mood) => {
    if (mood) {
        router.visit(`${checkInUrl.value}?mood=${encodeURIComponent(mood)}`);
    }
});
</script>

<template>
    <Head title="Today" />

    <section class="d-flex flex-column ga-6">
        <header>
            <h1 class="text-h4">{{ greeting }}, {{ firstName }}</h1>
            <p class="text-body-1 text-medium-emphasis">{{ formatLongDate(today) }}</p>
        </header>

        <MemoryCard v-if="memory" :memory="memory" :celebrate="justSaved">
            <template #actions>
                <v-btn
                    variant="tonal"
                    color="primary"
                    :prepend-icon="mdiPencil"
                    :href="checkInUrl"
                    @click="navigate($event, checkInUrl)"
                >
                    Edit today
                </v-btn>
            </template>
        </MemoryCard>

        <v-card v-else class="pa-6">
            <h2 class="text-h6 mb-4">How's today going?</h2>
            <EmojiPicker v-model="pickedMood" :options="moods" legend="How's today going?" />
        </v-card>

        <OnThisDayCarousel v-if="onThisDay.length" :memories="onThisDay" :today="today" />

        <QuoteCard v-if="quote" :quote="quote" :background-url="quoteBackground" />

        <RecapBanner v-if="recapYear" :year="recapYear" />

        <v-snackbar v-model="snackbar" :timeout="2500">Saved. Nice one.</v-snackbar>
    </section>
</template>
