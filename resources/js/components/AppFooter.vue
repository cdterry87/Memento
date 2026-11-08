<script setup lang="ts">
/**
 * The footer under every page: copyright, privacy policy, and credit.
 */
import { Link } from '@inertiajs/vue3';

const year = new Date().getFullYear();
</script>

<template>
    <footer class="app-footer text-caption text-medium-emphasis">
        <span>© {{ year }}. All rights reserved.</span>
        <Link href="/privacy" class="app-footer__link">Privacy Policy</Link>
        <span>
            Developed by
            <a
                href="https://chaseterry.com"
                target="_blank"
                rel="noopener"
                class="app-footer__link"
            >
                Chase Terry<span class="visually-hidden"> (opens in a new tab)</span>
            </a>
        </span>
    </footer>
</template>

<style scoped>
.app-footer {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    gap: 4px 16px;
    padding: 24px 16px;
}

/* Padding lifts the small links to a 24px touch target. */
.app-footer__link {
    display: inline-block;
    padding-block: 4px;
    color: inherit;
}

.app-footer__link:hover {
    color: rgb(var(--v-theme-primary));
}
</style>
