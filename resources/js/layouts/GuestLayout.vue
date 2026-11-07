<template>
    <v-app class="app-background">
        <v-main>
            <div class="page-column guest-column">
                <slot />
            </div>
        </v-main>
    </v-app>
</template>

<style scoped>
.guest-column {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 100dvh;
}
</style>
