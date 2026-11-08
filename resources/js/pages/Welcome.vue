<script setup lang="ts">
import { useLinkNavigation } from '@/composables/useLinkNavigation';
import { Head, usePage } from '@inertiajs/vue3';

defineProps<{
    appName: string;
}>();

const { navigate } = useLinkNavigation();
const page = usePage();
</script>

<template>
    <Head title="Welcome" />

    <section class="text-center">
        <h1 class="font-logo text-h2 text-primary mb-3">{{ appName }}</h1>
        <p class="text-h6 font-weight-regular text-medium-emphasis mb-8">
            A tiny, private journal for the moments worth keeping.
        </p>

        <div class="d-flex flex-wrap justify-center ga-3">
            <v-btn
                v-if="page.props.demoMode"
                color="primary"
                size="x-large"
                href="/login"
                @click="navigate($event, '/login')"
            >
                Try the demo
            </v-btn>
            <v-btn
                v-else
                color="primary"
                size="x-large"
                href="/register"
                @click="navigate($event, '/register')"
            >
                Create an account
            </v-btn>
            <v-btn
                variant="tonal"
                color="primary"
                size="x-large"
                href="/login"
                @click="navigate($event, '/login')"
            >
                Sign in
            </v-btn>
        </div>
    </section>
</template>

<style scoped>
.font-logo {
    font-size: 4rem;
}
</style>
