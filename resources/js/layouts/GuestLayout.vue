<script setup lang="ts">
import AppFooter from '@/components/AppFooter.vue';
</script>

<template>
    <v-app class="app-background">
        <v-main>
            <div class="page-column guest-column">
                <slot />
            </div>
            <AppFooter />
        </v-main>
    </v-app>
</template>

<style scoped>
.guest-column {
    display: flex;
    flex-direction: column;
    justify-content: center;
    /* Leaves room for the footer, so it sits just below the fold's centred card. */
    min-height: calc(100dvh - 72px);
}
</style>
