<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    appName: string;
}>();
</script>

<template>
    <Head title="Welcome" />

    <main>
        <h1>{{ appName }}</h1>
        <p>Your private, short-form journal.</p>
    </main>
</template>
