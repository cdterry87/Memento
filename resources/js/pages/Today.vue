<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Today" />

    <section>
        <h1 class="text-h4 mb-6">How's today?</h1>

        <EmptyState
            emoji="🌤️"
            title="Check-ins are coming soon"
            text="You'll pick a mood and what caused it, right here."
        />
    </section>
</template>
