<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { mdiThemeLightDark, mdiWeatherNight, mdiWhiteBalanceSunny } from '@mdi/js';
import { computed } from 'vue';
import { useTheme } from 'vuetify';

defineProps<{
    appName: string;
}>();

const theme = useTheme();

const themeOptions = [
    { value: 'system', label: 'System', icon: mdiThemeLightDark },
    { value: 'light', label: 'Light', icon: mdiWhiteBalanceSunny },
    { value: 'dark', label: 'Dark', icon: mdiWeatherNight },
];

const selectedTheme = computed({
    get: () => (theme.isSystem.value ? 'system' : theme.global.name.value),
    set: (name: string) => theme.change(name),
});
</script>

<template>
    <Head title="Welcome" />

    <v-app>
        <v-main class="d-flex align-center justify-center">
            <v-card class="pa-8 text-center" max-width="420" width="100%">
                <h1 class="font-logo text-h3 text-primary mb-2">{{ appName }}</h1>
                <p class="text-medium-emphasis mb-6">Your private, short-form journal.</p>

                <v-btn-toggle
                    v-model="selectedTheme"
                    mandatory
                    color="primary"
                    rounded="pill"
                    density="comfortable"
                    aria-label="Theme"
                >
                    <v-btn
                        v-for="option in themeOptions"
                        :key="option.value"
                        :value="option.value"
                        :prepend-icon="option.icon"
                    >
                        {{ option.label }}
                    </v-btn>
                </v-btn-toggle>
            </v-card>
        </v-main>
    </v-app>
</template>
