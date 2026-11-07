<script setup lang="ts">
import EmojiPicker from '@/components/EmojiPicker.vue';
import MessageBox from '@/components/MessageBox.vue';
import PhotoPicker from '@/components/PhotoPicker.vue';
import { useDraft } from '@/composables/useDraft';
import { characterCount, formatLongDate } from '@/composables/useLocalDate';
import { usePhotoUploads } from '@/composables/usePhotoUploads';
import type { Memory, Mood, Reason } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { mdiArrowLeft, mdiArrowRight, mdiCheck } from '@mdi/js';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    date: string;
    isToday: boolean;
    memory: Memory | null;
    moods: Mood[];
    reasons: Reason[];
    initialMood: string | null;
}>();

const steps = [
    { title: props.isToday ? 'How was your day?' : 'How was that day?', short: 'Mood' },
    { title: 'What made it that way?', short: 'Reasons' },
    { title: 'Anything to remember?', short: 'Note' },
];

const step = ref(props.initialMood ? 2 : 1);

const draft = useDraft(props.date, props.memory?.body ?? '');

const form = useForm({
    mood: props.initialMood ?? props.memory?.mood.slug ?? null,
    reasons: props.memory?.reasons.map((reason) => reason.slug) ?? [],
    body: draft.text.value,
});

/** New photos wait here until the entry is saved, then upload one by one. */
const newPhotos = ref<File[]>([]);
const photoUploads = usePhotoUploads();
const uploadingForDay = photoUploads.pendingFor(props.date);

watch(draft.text, (value) => {
    form.body = value;
});

/** Picking a mood moves straight on to the next question. */
watch(
    () => form.mood,
    (mood, previous) => {
        if (mood && mood !== previous && step.value === 1) {
            step.value = 2;
        }
    },
);

/** Cancelling goes back to where an existing past entry lives, otherwise to Today. */
const cancelUrl = computed(() =>
    props.memory && !props.isToday ? `/days/${props.date}` : '/today',
);
const dayLabel = computed(() => (props.isToday ? 'Today' : formatLongDate(props.date)));
const selectedMood = computed(() => props.moods.find((mood) => mood.slug === form.mood));
const isMessageTooLong = computed(() => characterCount(draft.text.value) > 255);
const canSave = computed(() => !!form.mood && !isMessageTooLong.value && !form.processing);

function save(): void {
    form.transform((data) => ({ ...data, body: data.body.trim() })).put(`/days/${props.date}`, {
        onSuccess: () => {
            draft.clear();
            photoUploads.enqueue(props.date, newPhotos.value);
        },
    });
}
</script>

<template>
    <Head :title="memory ? 'Edit check-in' : 'Check in'" />

    <section class="check-in">
        <div class="d-flex align-center justify-space-between mb-2">
            <p class="text-overline text-medium-emphasis">
                {{ dayLabel }} · Step {{ step }} of {{ steps.length }}
            </p>
            <Link :href="cancelUrl" class="text-body-2 text-primary">Cancel</Link>
        </div>

        <v-progress-linear
            :model-value="(step / steps.length) * 100"
            height="6"
            class="mb-6"
            :aria-label="`Step ${step} of ${steps.length}`"
        />

        <h1 class="text-h4 mb-2">{{ steps[step - 1].title }}</h1>
        <p class="text-body-1 text-medium-emphasis mb-6" aria-live="polite">
            <template v-if="step === 1">Pick the one that fits best.</template>
            <template v-else-if="step === 2">
                <span v-if="selectedMood">
                    <span aria-hidden="true">{{ selectedMood.emoji }}</span>
                    {{ selectedMood.label }}.
                </span>
                Choose as many as you like, or none.
            </template>
            <template v-else>A few words or a photo, if you feel like it.</template>
        </p>

        <v-alert v-if="form.errors.mood" type="error" class="mb-4">{{ form.errors.mood }}</v-alert>

        <EmojiPicker v-if="step === 1" v-model="form.mood" :options="moods" legend="Mood" />

        <EmojiPicker
            v-else-if="step === 2"
            v-model="form.reasons"
            :options="reasons"
            legend="Reasons"
            multiple
        />

        <div v-else class="d-flex flex-column ga-4">
            <MessageBox v-model="draft.text.value" :error-messages="form.errors.body" />
            <PhotoPicker
                v-model="newPhotos"
                :existing="memory?.photos"
                :uploading-count="uploadingForDay.length"
            />
        </div>

        <div class="check-in__actions">
            <v-btn v-if="step > 1" variant="text" :prepend-icon="mdiArrowLeft" @click="step--">
                Back
            </v-btn>

            <v-spacer />

            <v-btn
                v-if="step < steps.length && form.mood"
                variant="tonal"
                color="primary"
                :append-icon="mdiArrowRight"
                @click="step++"
            >
                Next
            </v-btn>

            <v-btn
                v-if="form.mood"
                color="primary"
                :prepend-icon="mdiCheck"
                :disabled="!canSave"
                :loading="form.processing"
                @click="save"
            >
                Save
            </v-btn>
        </div>
    </section>
</template>

<style scoped>
.check-in__actions {
    position: sticky;
    bottom: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 24px;
    padding-block: 16px;
    background: linear-gradient(to top, rgb(var(--v-theme-background)) 70%, transparent);
}
</style>
