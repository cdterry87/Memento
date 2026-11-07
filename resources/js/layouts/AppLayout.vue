<script setup lang="ts">
/**
 * The signed-in shell: a top app bar with menu navigation, and a centred
 * column for each screen's content. On wide screens the links sit in the
 * bar. On narrow screens they collapse into a menu button.
 */
import { useLinkNavigation } from '@/composables/useLinkNavigation';
import { Link, usePage } from '@inertiajs/vue3';
import {
    mdiAccountCircleOutline,
    mdiBookOpenPageVariantOutline,
    mdiMenu,
    mdiStarShootingOutline,
    mdiWhiteBalanceSunny,
} from '@mdi/js';
import { computed } from 'vue';

const primaryItems = [
    { label: 'Today', href: '/today', icon: mdiWhiteBalanceSunny },
    { label: 'Timeline', href: '/timeline', icon: mdiBookOpenPageVariantOutline },
    { label: 'Recap', href: '/recap', icon: mdiStarShootingOutline },
];

const accountItem = { label: 'Account', href: '/account', icon: mdiAccountCircleOutline };

const allItems = [...primaryItems, accountItem];

const page = usePage();
const { navigate } = useLinkNavigation();

const activeHref = computed(() => {
    const path = page.url.split(/[?#]/)[0];

    return allItems.find((item) => path === item.href || path.startsWith(`${item.href}/`))?.href;
});

function isActive(href: string): boolean {
    return href === activeHref.value;
}
</script>

<template>
    <v-app class="app-background">
        <v-app-bar flat color="transparent" height="72">
            <div class="app-bar-content">
                <Link href="/today" class="font-logo text-h5 text-primary text-decoration-none">
                    Memento
                </Link>

                <nav class="d-none d-md-flex align-center ga-1 ms-8" aria-label="Main">
                    <v-btn
                        v-for="item in primaryItems"
                        :key="item.href"
                        :href="item.href"
                        :variant="isActive(item.href) ? 'tonal' : 'text'"
                        :color="isActive(item.href) ? 'primary' : undefined"
                        :aria-current="isActive(item.href) ? 'page' : undefined"
                        @click="navigate($event, item.href)"
                    >
                        {{ item.label }}
                    </v-btn>
                </nav>

                <v-spacer />

                <v-btn
                    class="d-none d-md-flex"
                    :href="accountItem.href"
                    :prepend-icon="accountItem.icon"
                    :variant="isActive(accountItem.href) ? 'tonal' : 'text'"
                    :color="isActive(accountItem.href) ? 'primary' : undefined"
                    :aria-current="isActive(accountItem.href) ? 'page' : undefined"
                    @click="navigate($event, accountItem.href)"
                >
                    {{ accountItem.label }}
                </v-btn>

                <v-menu location="bottom end">
                    <template #activator="{ props: activatorProps }">
                        <v-btn
                            v-bind="activatorProps"
                            class="d-md-none"
                            :icon="mdiMenu"
                            variant="text"
                            aria-label="Menu"
                        />
                    </template>

                    <v-list nav min-width="200" aria-label="Main">
                        <v-list-item
                            v-for="item in allItems"
                            :key="item.href"
                            :href="item.href"
                            :prepend-icon="item.icon"
                            :title="item.label"
                            :active="isActive(item.href)"
                            color="primary"
                            rounded="lg"
                            :aria-current="isActive(item.href) ? 'page' : undefined"
                            @click="navigate($event, item.href)"
                        />
                    </v-list>
                </v-menu>
            </div>
        </v-app-bar>

        <v-main>
            <div class="page-column pt-4">
                <slot />
            </div>
        </v-main>
    </v-app>
</template>

<style scoped>
.app-bar-content {
    display: flex;
    align-items: center;
    width: 100%;
    max-width: 1040px;
    margin-inline: auto;
    padding-inline: 16px;
}
</style>
