<script setup lang="ts">
/**
 * Chooses photos to add during the check-in. They upload after the entry is
 * saved, so they are only held here until then.
 */
import {
    ACCEPTED_PHOTO_TYPES,
    MAX_PHOTOS_PER_DAY,
    photoProblem,
} from '@/composables/usePhotoUploads';
import type { Photo } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { mdiClose, mdiImagePlusOutline } from '@mdi/js';
import { computed, onBeforeUnmount, ref, useId, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        /** Photos already on the entry or still uploading, which count toward the limit. */
        existing?: Photo[];
        uploadingCount?: number;
    }>(),
    { existing: () => [], uploadingCount: 0 },
);

const files = defineModel<File[]>({ required: true });

/** The shared demo account can't upload, so visitors can't add files to storage. */
const page = usePage();
const isDemo = computed(() => !!page?.props?.auth?.user?.isDemo);

const inputId = useId();
const input = ref<HTMLInputElement | null>(null);
const problems = ref<string[]>([]);

const previews = ref<{ file: File; url: string }[]>([]);

watch(
    files,
    (current) => {
        for (const preview of previews.value) {
            if (!current.includes(preview.file)) {
                URL.revokeObjectURL(preview.url);
            }
        }

        previews.value = current.map(
            (file) =>
                previews.value.find((preview) => preview.file === file) ?? {
                    file,
                    url: URL.createObjectURL(file),
                },
        );
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    previews.value.forEach((preview) => URL.revokeObjectURL(preview.url));
});

const used = computed(() => props.existing.length + props.uploadingCount + files.value.length);
const remaining = computed(() => Math.max(0, MAX_PHOTOS_PER_DAY - used.value));

function choose(): void {
    input.value?.click();
}

function onChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    const chosen = [...(target.files ?? [])];
    target.value = '';

    const found: string[] = [];
    const accepted: File[] = [];

    for (const file of chosen) {
        const problem = photoProblem(file);

        if (problem) {
            found.push(problem);
        } else if (accepted.length < remaining.value) {
            accepted.push(file);
        } else {
            found.push(
                `A day can have up to ${MAX_PHOTOS_PER_DAY} photos, so ${file.name} was left out.`,
            );
        }
    }

    problems.value = found;
    files.value = [...files.value, ...accepted];
}

function removeFile(file: File): void {
    files.value = files.value.filter((item) => item !== file);
}
</script>

<template>
    <div class="photo-picker">
        <ul v-if="existing.length || previews.length" class="photo-picker__list">
            <li v-for="(photo, index) in existing" :key="photo.id" class="photo-picker__tile">
                <img
                    :src="photo.thumbnailUrl"
                    :alt="`Saved photo ${index + 1}`"
                    class="photo-picker__image"
                />
            </li>
            <li v-for="preview in previews" :key="preview.url" class="photo-picker__tile">
                <img
                    :src="preview.url"
                    :alt="`New photo: ${preview.file.name}`"
                    class="photo-picker__image"
                />
                <v-btn
                    class="photo-picker__remove"
                    :icon="mdiClose"
                    size="x-small"
                    color="surface"
                    :aria-label="`Remove ${preview.file.name}`"
                    @click="removeFile(preview.file)"
                />
            </li>
        </ul>

        <input
            :id="inputId"
            ref="input"
            type="file"
            class="visually-hidden"
            :accept="ACCEPTED_PHOTO_TYPES.join(',')"
            multiple
            tabindex="-1"
            aria-hidden="true"
            @change="onChange"
        />

        <div class="d-flex align-center flex-wrap ga-3">
            <v-btn
                variant="tonal"
                :prepend-icon="mdiImagePlusOutline"
                :disabled="isDemo || remaining === 0"
                @click="choose"
            >
                Add photo
            </v-btn>
            <span class="text-body-2 text-medium-emphasis">
                {{
                    isDemo
                        ? 'Adding photos is turned off in the demo.'
                        : remaining === 0
                          ? 'That’s the most for one day.'
                          : `Up to ${MAX_PHOTOS_PER_DAY} photos a day.`
                }}
            </span>
        </div>

        <p
            v-for="problem in problems"
            :key="problem"
            class="text-body-2 text-error mt-2"
            role="alert"
        >
            {{ problem }}
        </p>
    </div>
</template>

<style scoped>
.photo-picker__list {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin: 0 0 12px;
    padding: 0;
    list-style: none;
}

.photo-picker__tile {
    position: relative;
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
    background: rgba(var(--v-theme-on-surface), 0.06);
}

.photo-picker__image {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-picker__remove {
    position: absolute;
    top: 4px;
    right: 4px;
}
</style>
