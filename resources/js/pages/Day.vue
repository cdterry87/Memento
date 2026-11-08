<script setup lang="ts">
import MemoryCard from '@/components/MemoryCard.vue';
import { useLinkNavigation } from '@/composables/useLinkNavigation';
import { formatLongDate } from '@/composables/useLocalDate';
import type { Memory, Photo } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    mdiArrowLeft,
    mdiChevronLeft,
    mdiChevronRight,
    mdiDeleteOutline,
    mdiImageAlbum,
    mdiPencil,
} from '@mdi/js';
import { computed, ref } from 'vue';

const props = defineProps<{
    date: string;
    isToday: boolean;
    memory: Memory;
}>();

const page = usePage();
const { navigate } = useLinkNavigation();

const longDate = computed(() => formatLongDate(props.date, { withYear: true }));
const checkInUrl = computed(() => `/days/${props.date}/check-in`);

const justSaved = computed(() => page.props.status === 'check-in-saved');
const snackbar = ref(justSaved.value ? 'Saved.' : '');

const confirmingDelete = ref(false);
const deleting = ref(false);

const photoVisitOptions = { preserveScroll: true, preserveState: true } as const;

/** Moves a photo to a new place. Whichever is first becomes the cover. */
function movePhoto(from: number, to: number): void {
    const ids = props.memory.photos.map((photo) => photo.id);
    const [moved] = ids.splice(from, 1);
    ids.splice(to, 0, moved);

    router.patch(`/days/${props.date}/photos/order`, { photos: ids }, photoVisitOptions);
}

const photoToDelete = ref<Photo | null>(null);
const deletingPhoto = ref(false);

function deletePhoto(): void {
    if (!photoToDelete.value) {
        return;
    }

    router.delete(`/days/${props.date}/photos/${photoToDelete.value.id}`, {
        ...photoVisitOptions,
        onStart: () => (deletingPhoto.value = true),
        onSuccess: () => (snackbar.value = 'Photo deleted.'),
        onFinish: () => {
            deletingPhoto.value = false;
            photoToDelete.value = null;
        },
    });
}

function deleteEntry(): void {
    router.delete(`/days/${props.date}`, {
        onStart: () => (deleting.value = true),
        onFinish: () => (deleting.value = false),
    });
}
</script>

<template>
    <Head :title="longDate" />

    <section class="d-flex flex-column ga-6">
        <header>
            <Link href="/timeline" class="back-link text-body-2 text-primary">
                <v-icon :icon="mdiArrowLeft" size="small" />
                Timeline
            </Link>
            <h1 class="text-h4 mt-3">{{ isToday ? 'Today' : longDate }}</h1>
            <p v-if="isToday" class="text-body-1 text-medium-emphasis">{{ longDate }}</p>
        </header>

        <MemoryCard :memory="memory" :celebrate="justSaved">
            <template #actions>
                <div class="d-flex flex-wrap ga-2">
                    <v-btn
                        variant="tonal"
                        color="primary"
                        :prepend-icon="mdiPencil"
                        :href="checkInUrl"
                        @click="navigate($event, checkInUrl)"
                    >
                        Edit
                    </v-btn>
                    <v-btn
                        variant="text"
                        color="error"
                        :prepend-icon="mdiDeleteOutline"
                        @click="confirmingDelete = true"
                    >
                        Delete
                    </v-btn>
                </div>
            </template>
            <template #photo-actions="{ photo, index }">
                <template v-if="photo && memory.photos.length > 1">
                    <v-btn
                        v-if="index > 0"
                        :icon="mdiImageAlbum"
                        variant="text"
                        aria-label="Make this the cover photo"
                        title="Make cover"
                        @click="movePhoto(index, 0)"
                    />
                    <v-btn
                        :icon="mdiChevronLeft"
                        variant="text"
                        :disabled="index === 0"
                        aria-label="Move this photo earlier"
                        title="Move earlier"
                        @click="movePhoto(index, index - 1)"
                    />
                    <v-btn
                        :icon="mdiChevronRight"
                        variant="text"
                        :disabled="index === memory.photos.length - 1"
                        aria-label="Move this photo later"
                        title="Move later"
                        @click="movePhoto(index, index + 1)"
                    />
                </template>
                <v-btn
                    v-if="photo"
                    :icon="mdiDeleteOutline"
                    variant="text"
                    aria-label="Delete this photo"
                    title="Delete photo"
                    @click="photoToDelete = photo"
                />
            </template>
        </MemoryCard>

        <v-dialog
            :model-value="!!photoToDelete"
            max-width="420"
            aria-labelledby="delete-photo-title"
            aria-describedby="delete-photo-text"
            @update:model-value="photoToDelete = null"
        >
            <v-card>
                <v-card-title id="delete-photo-title" class="text-h6 pt-5 px-6"
                    >Delete this photo?</v-card-title
                >
                <v-card-text id="delete-photo-text" class="px-6"
                    >It will be removed for good. This can't be undone.</v-card-text
                >
                <v-card-actions class="px-6 pb-5">
                    <v-spacer />
                    <v-btn variant="text" @click="photoToDelete = null">Keep it</v-btn>
                    <v-btn
                        color="error"
                        variant="flat"
                        :loading="deletingPhoto"
                        @click="deletePhoto"
                    >
                        Delete
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog
            v-model="confirmingDelete"
            max-width="420"
            aria-labelledby="delete-day-title"
            aria-describedby="delete-day-text"
        >
            <v-card>
                <v-card-title id="delete-day-title" class="text-h6 pt-5 px-6"
                    >Delete this day?</v-card-title
                >
                <v-card-text id="delete-day-text" class="px-6">
                    Your check-in for {{ longDate }}, including its photos, will be removed for
                    good. This can't be undone.
                </v-card-text>
                <v-card-actions class="px-6 pb-5">
                    <v-spacer />
                    <v-btn variant="text" @click="confirmingDelete = false">Keep it</v-btn>
                    <v-btn color="error" variant="flat" :loading="deleting" @click="deleteEntry">
                        Delete
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar
            :model-value="!!snackbar"
            :timeout="2500"
            @update:model-value="(open: boolean) => !open && (snackbar = '')"
        >
            {{ snackbar }}
        </v-snackbar>
    </section>
</template>

<style scoped>
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    min-height: 44px;
    text-decoration: none;
}

.back-link:hover {
    text-decoration: underline;
}
</style>
