<script setup lang="ts">
import { useLinkNavigation } from '@/composables/useLinkNavigation';
import { Head } from '@inertiajs/vue3';

defineProps<{
    appName: string;
}>();

const { navigate } = useLinkNavigation();
</script>

<template>
    <Head title="Welcome" />

    <section class="text-center">
        <h1 class="font-logo text-h2 text-primary mb-3">{{ appName }}</h1>
        <p class="text-h6 font-weight-regular text-medium-emphasis mb-8">
            A tiny, private journal for the moments worth keeping.
        </p>

        <!-- Goes straight in until sign-in arrives with accounts. -->
        <v-btn color="primary" size="x-large" href="/today" @click="navigate($event, '/today')">
            Take a look
        </v-btn>
    </section>
</template>
