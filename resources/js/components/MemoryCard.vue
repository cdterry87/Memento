<script setup lang="ts">
import PhotoGrid from '@/components/PhotoGrid.vue';
import PhotoLightbox from '@/components/PhotoLightbox.vue';
import { formatLongDate } from '@/composables/useLocalDate';
import { usePhotoUploads } from '@/composables/usePhotoUploads';
import type { Memory, Photo } from '@/types';
import { ref, watch } from 'vue';

const props = defineProps<{
    memory: Memory;
    celebrate?: boolean;
}>();

defineSlots<{
    actions?: () => unknown;
    /** Controls for the photo shown in the lightbox. */
    'photo-actions'?: (scope: { photo: Photo | undefined; index: number }) => unknown;
}>();

const pending = usePhotoUploads().pendingFor(props.memory.date);

const lightboxOpen = ref(false);
const lightboxIndex = ref(0);

/** Keeps the lightbox on the same photo when the order changes. */
watch(
    () => props.memory.photos.map((photo) => photo.id),
    (ids, previousIds) => {
        const shownId = previousIds[lightboxIndex.value];
        const newIndex = ids.indexOf(shownId);

        if (newIndex !== -1) {
            lightboxIndex.value = newIndex;
        }
    },
);

function openPhoto(index: number): void {
    lightboxIndex.value = index;
    lightboxOpen.value = true;
}
</script>

<template>
    <v-card class="memory-card pa-6">
        <div class="d-flex align-center ga-4">
            <span
                class="memory-card__mood"
                :class="{ 'memory-card__mood--celebrate': celebrate }"
                aria-hidden="true"
            >
                {{ memory.mood.emoji }}
            </span>
            <div>
                <p class="text-overline text-medium-emphasis">Feeling</p>
                <p class="text-h5">{{ memory.mood.label }}</p>
            </div>
        </div>

        <PhotoGrid :photos="memory.photos" :pending="pending" class="mt-5" @open="openPhoto" />

        <div v-if="memory.reasons.length" class="d-flex flex-wrap ga-2 mt-5">
            <v-chip v-for="reason in memory.reasons" :key="reason.slug" size="small">
                <span class="me-1" aria-hidden="true">{{ reason.emoji }}</span>
                {{ reason.label }}
            </v-chip>
        </div>

        <p v-if="memory.body" class="memory-card__body text-body-1 mt-5">{{ memory.body }}</p>

        <div v-if="$slots.actions" class="mt-6">
            <slot name="actions" />
        </div>

        <PhotoLightbox
            v-model="lightboxOpen"
            v-model:index="lightboxIndex"
            :photos="memory.photos"
            :label="formatLongDate(memory.date, { withYear: true })"
        >
            <template #actions="scope">
                <slot name="photo-actions" v-bind="scope" />
            </template>
        </PhotoLightbox>
    </v-card>
</template>

<style scoped>
.memory-card__mood {
    font-size: 3.5rem;
    line-height: 1;
}

.memory-card__mood--celebrate {
    animation: celebrate 900ms cubic-bezier(0.34, 1.56, 0.64, 1);
}

.memory-card__body {
    white-space: pre-line;
    overflow-wrap: anywhere;
}

@keyframes celebrate {
    0% {
        transform: scale(0.4) rotate(-20deg);
    }
    60% {
        transform: scale(1.3) rotate(8deg);
    }
    100% {
        transform: scale(1) rotate(0);
    }
}
</style>
