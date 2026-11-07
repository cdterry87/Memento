<script setup lang="ts">
import { useAppTheme } from '@/composables/useAppTheme';
import { mdiThemeLightDark, mdiWeatherNight, mdiWhiteBalanceSunny } from '@mdi/js';

const { preference } = useAppTheme();

const themeOptions = [
    { value: 'system', label: 'System', icon: mdiThemeLightDark },
    { value: 'light', label: 'Light', icon: mdiWhiteBalanceSunny },
    { value: 'dark', label: 'Dark', icon: mdiWeatherNight },
];
</script>

<template>
    <v-btn-toggle v-model="preference" mandatory density="comfortable" aria-label="Theme">
        <v-btn
            v-for="option in themeOptions"
            :key="option.value"
            :value="option.value"
            :prepend-icon="option.icon"
        >
            {{ option.label }}
        </v-btn>
    </v-btn-toggle>
</template>
