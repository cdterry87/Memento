<script setup lang="ts">
defineProps<{
    emoji: string;
    title: string;
    text: string;
}>();
</script>

<template>
    <v-card class="pa-8 text-center">
        <div class="empty-state-emoji mb-4" aria-hidden="true">{{ emoji }}</div>
        <h2 class="text-h6 mb-2">{{ title }}</h2>
        <p class="text-medium-emphasis">{{ text }}</p>
        <slot />
    </v-card>
</template>

<style scoped>
.empty-state-emoji {
    font-size: 3.5rem;
    line-height: 1;
}
</style>
