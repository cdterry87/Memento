<script setup lang="ts">
/**
 * A day's photos as a tidy grid of thumbnails, cover first, followed by any
 * still uploading. Tapping a photo opens it in the lightbox.
 */
import { usePhotoUploads, type PendingUpload } from '@/composables/usePhotoUploads';
import type { Photo } from '@/types';
import { mdiClose, mdiReload } from '@mdi/js';
import { computed } from 'vue';

const props = defineProps<{
    photos: Photo[];
    pending?: PendingUpload[];
}>();

const emit = defineEmits<{
    open: [index: number];
}>();

const { retry, discard } = usePhotoUploads();

const pendingUploads = computed(() => props.pending ?? []);
const tileCount = computed(() => props.photos.length + pendingUploads.value.length);
const failures = computed(() => pendingUploads.value.filter((upload) => upload.error));
</script>

<template>
    <div v-if="tileCount" class="photo-grid-wrapper">
        <ul class="photo-grid" :class="`photo-grid--${Math.min(tileCount, 4)}`">
            <li v-for="(photo, index) in photos" :key="photo.id" class="photo-grid__tile">
                <button
                    type="button"
                    class="photo-grid__button"
                    :aria-label="`Open photo ${index + 1} of ${photos.length}`"
                    @click="emit('open', index)"
                >
                    <img
                        :src="photo.thumbnailUrl"
                        alt=""
                        loading="lazy"
                        class="photo-grid__image"
                    />
                </button>
            </li>

            <li
                v-for="upload in pendingUploads"
                :key="`pending-${upload.key}`"
                class="photo-grid__tile"
            >
                <img
                    :src="upload.previewUrl"
                    alt=""
                    class="photo-grid__image photo-grid__image--pending"
                />

                <div class="photo-grid__overlay">
                    <template v-if="upload.status === 'failed'">
                        <v-btn
                            :icon="mdiReload"
                            size="small"
                            color="surface"
                            :aria-label="`Retry uploading ${upload.file.name}`"
                            @click="retry(upload)"
                        />
                        <v-btn
                            :icon="mdiClose"
                            size="small"
                            color="surface"
                            :aria-label="`Remove ${upload.file.name}`"
                            @click="discard(upload)"
                        />
                    </template>
                    <v-progress-circular
                        v-else
                        :model-value="upload.progress"
                        :indeterminate="upload.status === 'waiting'"
                        color="white"
                        size="40"
                        width="4"
                        :aria-label="`Uploading ${upload.file.name}`"
                    />
                </div>
            </li>
        </ul>

        <p
            v-for="upload in failures"
            :key="`error-${upload.key}`"
            class="text-body-2 text-error mt-2"
            role="alert"
        >
            {{ upload.error }}
        </p>
    </div>
</template>

<style scoped>
.photo-grid {
    display: grid;
    gap: 6px;
    margin: 0;
    padding: 0;
    list-style: none;
    border-radius: 16px;
    overflow: hidden;
}

.photo-grid--1 {
    grid-template-columns: 1fr;
}

.photo-grid--2,
.photo-grid--4 {
    grid-template-columns: 1fr 1fr;
}

.photo-grid--3 {
    grid-template-columns: 1fr 1fr;
}

.photo-grid--3 .photo-grid__tile:first-child {
    grid-column: 1 / -1;
}

.photo-grid__tile {
    position: relative;
    aspect-ratio: 4 / 3;
    background: rgba(var(--v-theme-on-surface), 0.06);
}

.photo-grid--1 .photo-grid__tile,
.photo-grid--3 .photo-grid__tile:first-child {
    aspect-ratio: 16 / 10;
}

.photo-grid__button {
    display: block;
    width: 100%;
    height: 100%;
    padding: 0;
    border: 0;
    cursor: zoom-in;
}

.photo-grid__button:focus-visible {
    outline: 3px solid rgb(var(--v-theme-primary));
    outline-offset: -3px;
}

.photo-grid__image {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-grid__image--pending {
    filter: brightness(0.6);
}

.photo-grid__overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
</style>
