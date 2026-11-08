<script setup lang="ts">
/**
 * Full-size photos, one at a time. Arrow keys and swipes move between them,
 * and Escape closes. The actions slot holds controls for the current photo.
 */
import type { Photo } from '@/types';
import { mdiChevronLeft, mdiChevronRight, mdiClose } from '@mdi/js';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    photos: Photo[];
    label: string;
}>();

const open = defineModel<boolean>({ required: true });
const index = defineModel<number>('index', { required: true });

const current = computed<Photo | undefined>(() => props.photos[index.value]);
const hasPrevious = computed(() => index.value > 0);
const hasNext = computed(() => index.value < props.photos.length - 1);

function previous(): void {
    if (hasPrevious.value) {
        index.value--;
    }
}

function next(): void {
    if (hasNext.value) {
        index.value++;
    }
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'ArrowLeft') {
        previous();
    } else if (event.key === 'ArrowRight') {
        next();
    }
}

/** A horizontal swipe of at least 50px moves to the neighbouring photo. */
const swipeStart = ref<{ x: number; y: number } | null>(null);

function onPointerDown(event: PointerEvent): void {
    swipeStart.value = { x: event.clientX, y: event.clientY };
}

function onPointerUp(event: PointerEvent): void {
    if (!swipeStart.value) {
        return;
    }

    const dx = event.clientX - swipeStart.value.x;
    const dy = event.clientY - swipeStart.value.y;
    swipeStart.value = null;

    if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
        if (dx < 0) {
            next();
        } else {
            previous();
        }
    }
}

/** Keeps the position valid when photos are removed while open. */
watch(
    () => props.photos.length,
    (length) => {
        if (length === 0) {
            open.value = false;
        } else if (index.value > length - 1) {
            index.value = length - 1;
        }
    },
);
</script>

<template>
    <v-dialog
        v-model="open"
        fullscreen
        :scrim="false"
        transition="fade-transition"
        :aria-label="`${label}, photos`"
    >
        <v-theme-provider theme="dark" class="h-100">
            <div
                class="lightbox"
                role="group"
                :aria-label="`${label}, photo ${index + 1} of ${photos.length}`"
                @keydown="onKeydown"
            >
                <div class="lightbox__bar">
                    <span class="text-body-2">{{ index + 1 }} of {{ photos.length }}</span>
                    <v-spacer />
                    <slot name="actions" :photo="current" :index="index" />
                    <v-btn
                        :icon="mdiClose"
                        variant="text"
                        aria-label="Close"
                        @click="open = false"
                    />
                </div>

                <div class="lightbox__stage" @pointerdown="onPointerDown" @pointerup="onPointerUp">
                    <img
                        v-if="current"
                        :key="current.id"
                        :src="current.url"
                        :width="current.width"
                        :height="current.height"
                        :alt="`Photo ${index + 1} of ${photos.length} from ${label}`"
                        class="lightbox__image"
                        draggable="false"
                    />

                    <v-btn
                        v-if="hasPrevious"
                        class="lightbox__nav lightbox__nav--previous"
                        :icon="mdiChevronLeft"
                        variant="tonal"
                        aria-label="Previous photo"
                        @click="previous"
                    />
                    <v-btn
                        v-if="hasNext"
                        class="lightbox__nav lightbox__nav--next"
                        :icon="mdiChevronRight"
                        variant="tonal"
                        aria-label="Next photo"
                        @click="next"
                    />
                </div>
            </div>
        </v-theme-provider>
    </v-dialog>
</template>

<style scoped>
.lightbox {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: rgba(10, 9, 20, 0.96);
    color: #fff;
}

.lightbox__bar {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 8px 8px 8px 16px;
}

.lightbox__stage {
    position: relative;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 0;
    padding: 0 16px 16px;
    touch-action: pan-y;
}

.lightbox__image {
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
    border-radius: 8px;
    user-select: none;
}

.lightbox__nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
}

.lightbox__nav--previous {
    left: 16px;
}

.lightbox__nav--next {
    right: 16px;
}
</style>
