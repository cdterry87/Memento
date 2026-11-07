<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Timeline" />

    <section>
        <h1 class="text-h4 mb-6">Timeline</h1>

        <EmptyState
            emoji="📖"
            title="Nothing here yet"
            text="Your past days will appear here, newest first."
        />
    </section>
</template>
