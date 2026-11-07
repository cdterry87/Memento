<script setup lang="ts">
import MemoryCard from '@/components/MemoryCard.vue';
import { useLinkNavigation } from '@/composables/useLinkNavigation';
import { formatLongDate } from '@/composables/useLocalDate';
import type { Memory } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { mdiArrowLeft, mdiDeleteOutline, mdiPencil } from '@mdi/js';
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
const snackbar = ref(justSaved.value);

const confirmingDelete = ref(false);
const deleting = ref(false);

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
        </MemoryCard>

        <v-dialog v-model="confirmingDelete" max-width="420">
            <v-card>
                <v-card-title class="text-h6 pt-5 px-6">Delete this day?</v-card-title>
                <v-card-text class="px-6">
                    Your check-in for {{ longDate }} will be removed for good. This can't be undone.
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

        <v-snackbar v-model="snackbar" :timeout="2500">Saved.</v-snackbar>
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
