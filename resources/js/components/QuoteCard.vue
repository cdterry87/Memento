<script setup lang="ts">
/**
 * The quote of the day, with its author. With a background photo, a dark
 * overlay keeps the white text readable in both themes. The photo is
 * decorative, so screen readers skip it.
 */
import type { Quote } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    quote: Quote;
    backgroundUrl?: string | null;
}>();

const backgroundStyle = computed(() =>
    props.backgroundUrl ? { backgroundImage: `url("${props.backgroundUrl}")` } : undefined,
);
</script>

<template>
    <v-card
        class="quote-card pa-6"
        :class="{ 'quote-card--photo': backgroundUrl }"
        variant="flat"
        :style="backgroundStyle"
    >
        <h2 class="visually-hidden">Quote of the day</h2>
        <figure>
            <blockquote class="quote-card__text text-h6 font-weight-regular">
                {{ quote.text }}
            </blockquote>
            <figcaption class="quote-card__author text-body-2 mt-3">
                {{ quote.author }}
            </figcaption>
        </figure>
    </v-card>
</template>

<style scoped>
.quote-card {
    background-color: rgba(var(--v-theme-primary), 0.08);
}

.quote-card figure {
    position: relative;
    margin: 0;
}

.quote-card__text {
    font-size: 1.7rem;
    font-weight: 600;
    margin: 0;
    line-height: 1.5;
}

.quote-card__text::before {
    content: '“';
}

.quote-card__text::after {
    content: '”';
}

.quote-card__author {
    font-size: 1.3rem;
    font-weight: 500;
    opacity: var(--v-medium-emphasis-opacity);
}

.quote-card__author::before {
    content: '— ';
}

.quote-card--photo {
    display: flex;
    align-items: center;
    min-height: 200px;
    background-color: #2b2a3d;
    background-position: center;
    background-size: cover;
    color: #fff;
}

/* Darkens the photo enough for white text to pass WCAG AA on any image. */
.quote-card--photo::before {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(24, 22, 36, 0.6);
}

.quote-card--photo .quote-card__text,
.quote-card--photo .quote-card__author {
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

.quote-card--photo .quote-card__author {
    opacity: 1;
}
</style>
