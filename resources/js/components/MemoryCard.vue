<script setup lang="ts">
import type { Memory } from '@/types';

defineProps<{
    memory: Memory;
    celebrate?: boolean;
}>();
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
