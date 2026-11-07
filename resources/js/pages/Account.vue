<script setup lang="ts">
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Account" />

    <section>
        <h1 class="text-h4 mb-6">Account</h1>

        <v-card class="pa-6">
            <h2 class="text-subtitle-1 font-weight-bold mb-1">Appearance</h2>
            <p class="text-body-2 text-medium-emphasis mb-4">
                Follow your device, or always use light or dark.
            </p>

            <ThemeSwitcher />
        </v-card>
    </section>
</template>
