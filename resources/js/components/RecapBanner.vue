<script setup lang="ts">
/**
 * Promotes a year's recap on the Today screen, around the turn of the year.
 */
import { useLinkNavigation } from '@/composables/useLinkNavigation';
import { mdiArrowRight } from '@mdi/js';
import { computed } from 'vue';

const props = defineProps<{
    year: number;
}>();

const { navigate } = useLinkNavigation();

const href = computed(() => `/recap/${props.year}`);
</script>

<template>
    <v-card class="recap-banner pa-6" variant="flat" :href="href" @click="navigate($event, href)">
        <div class="d-flex align-center ga-4">
            <span class="recap-banner__emoji" aria-hidden="true">🌠</span>
            <div class="flex-grow-1">
                <h2 class="text-h6">Your {{ year }} in a story</h2>
                <p class="text-body-2 text-medium-emphasis">
                    The moods, moments, and memories that made your year.
                </p>
            </div>
            <v-icon :icon="mdiArrowRight" color="primary" />
        </div>
    </v-card>
</template>

<style scoped>
.recap-banner {
    background: linear-gradient(
        135deg,
        rgba(var(--v-theme-primary), 0.16),
        rgba(var(--v-theme-secondary), 0.16)
    );
}

.recap-banner__emoji {
    font-size: 2.5rem;
    line-height: 1;
}
</style>
