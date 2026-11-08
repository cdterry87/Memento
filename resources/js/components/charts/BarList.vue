<script setup lang="ts">
/**
 * Horizontal bars with an emoji and a label, as a plain list, so screen
 * readers hear each item and its value as text.
 */
import { computed } from 'vue';

export interface BarListItem {
    key: string;
    emoji: string;
    label: string;
    value: number;
    /** What the value means in words, such as "42 days, 30%". */
    valueLabel: string;
    /** A theme colour name for the bar, such as "mood-5". Defaults to primary. */
    color?: string;
}

const props = defineProps<{
    items: BarListItem[];
    label: string;
}>();

const largest = computed(() => Math.max(1, ...props.items.map((item) => item.value)));
</script>

<template>
    <ul class="bar-list" :aria-label="label">
        <li v-for="item in items" :key="item.key" class="bar-list__item">
            <span class="bar-list__emoji" aria-hidden="true">{{ item.emoji }}</span>
            <div class="bar-list__body">
                <div class="d-flex justify-space-between ga-2 text-body-2">
                    <span class="font-weight-bold">{{ item.label }}</span>
                    <span class="text-medium-emphasis">{{ item.valueLabel }}</span>
                </div>
                <div class="bar-list__track" aria-hidden="true">
                    <div
                        class="bar-list__bar"
                        :style="{
                            width: `${(item.value / largest) * 100}%`,
                            backgroundColor: `rgb(var(--v-theme-${item.color ?? 'primary'}))`,
                        }"
                    />
                </div>
            </div>
        </li>
    </ul>
</template>

<style scoped>
.bar-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.bar-list__item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.bar-list__emoji {
    flex: none;
    width: 2rem;
    font-size: 1.6rem;
    line-height: 1;
    text-align: center;
}

.bar-list__body {
    flex: 1;
    min-width: 0;
}

.bar-list__track {
    height: 10px;
    margin-top: 4px;
    border-radius: 999px;
    background: rgba(var(--v-theme-on-surface), 0.06);
    overflow: hidden;
}

.bar-list__bar {
    height: 100%;
    min-width: 10px;
    border-radius: 999px;
}

@media (prefers-reduced-motion: no-preference) {
    .bar-list__bar {
        animation: grow 600ms cubic-bezier(0.22, 1, 0.36, 1);
        transform-origin: left;
    }
}

@keyframes grow {
    from {
        transform: scaleX(0);
    }
}
</style>
