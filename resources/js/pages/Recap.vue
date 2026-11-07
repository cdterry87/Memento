<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Recap" />

    <section>
        <h1 class="text-h4 mb-6">Recap</h1>

        <EmptyState
            emoji="🌠"
            title="Your year, in a story"
            text="Once you've checked in for a while, your yearly recap will live here."
        />
    </section>
</template>
