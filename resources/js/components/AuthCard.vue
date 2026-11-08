<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    title: string;
    subtitle?: string;
}>();
</script>

<template>
    <div class="auth-card">
        <Link
            href="/"
            class="d-block text-center font-logo text-h4 text-primary text-decoration-none mb-6"
        >
            Memento
        </Link>

        <v-card class="pa-6 pa-sm-8">
            <h1 class="text-h5 mb-1">{{ title }}</h1>
            <p v-if="subtitle" class="text-body-2 text-medium-emphasis mb-6">{{ subtitle }}</p>
            <div v-else class="mb-6" />

            <slot />
        </v-card>

        <div v-if="$slots.footer" class="text-center text-body-2 mt-6">
            <slot name="footer" />
        </div>
    </div>
</template>

<style scoped>
.auth-card {
    width: 100%;
    max-width: 420px;
    margin-inline: auto;
    padding-block: 32px;
}

.font-logo {
    font-size: 2rem;
}
</style>
