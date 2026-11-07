<script setup lang="ts">
/**
 * A grid of emoji choices built on real radio buttons or checkboxes, so it
 * works with the keyboard and screen readers. Every option shows its label.
 */
import type { EmojiOption } from '@/types';
import { computed, useId } from 'vue';

const props = defineProps<{
    options: EmojiOption[];
    legend: string;
    multiple?: boolean;
    /** The most columns to show. Narrow screens still drop to fewer, so labels never break mid-word. */
    columns?: number;
}>();

const selected = defineModel<string | string[] | null>({ required: true });

const name = useId();

function isSelected(slug: string): boolean {
    return Array.isArray(selected.value) ? selected.value.includes(slug) : selected.value === slug;
}

function toggle(slug: string): void {
    if (!props.multiple) {
        selected.value = slug;

        return;
    }

    const current = Array.isArray(selected.value) ? selected.value : [];

    selected.value = current.includes(slug)
        ? current.filter((item) => item !== slug)
        : [...current, slug];
}

const inputType = computed(() => (props.multiple ? 'checkbox' : 'radio'));

const MIN_OPTION_WIDTH = '92px';
const GAP = '10px';

/** Fits as many 92px options as the width allows, capped at `columns` when given. */
const gridColumns = computed(() => {
    const width = props.columns
        ? `max(${MIN_OPTION_WIDTH}, calc((100% - ${props.columns - 1} * ${GAP}) / ${props.columns}))`
        : MIN_OPTION_WIDTH;

    return `repeat(auto-fill, minmax(${width}, 1fr))`;
});
</script>

<template>
    <fieldset class="emoji-picker" :style="{ gridTemplateColumns: gridColumns }">
        <legend class="visually-hidden">{{ legend }}</legend>

        <label
            v-for="option in options"
            :key="option.slug"
            class="emoji-option"
            :class="{ 'emoji-option--selected': isSelected(option.slug) }"
        >
            <input
                class="visually-hidden"
                :type="inputType"
                :name="name"
                :value="option.slug"
                :checked="isSelected(option.slug)"
                @change="toggle(option.slug)"
            />
            <span class="emoji-option__emoji" aria-hidden="true">{{ option.emoji }}</span>
            <span class="emoji-option__label">{{ option.label }}</span>
        </label>
    </fieldset>
</template>

<style scoped>
.emoji-picker {
    display: grid;
    gap: 10px;
    border: 0;
    margin: 0;
    padding: 0;
    min-width: 0;
}

.emoji-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    min-height: 96px;
    padding: 14px 6px 10px;
    border-radius: 20px;
    background: rgba(var(--v-theme-on-surface), 0.04);
    border: 2px solid transparent;
    cursor: pointer;
    user-select: none;
    transition:
        transform 160ms ease,
        background-color 160ms ease,
        border-color 160ms ease;
}

.emoji-option:hover {
    transform: translateY(-2px);
}

.emoji-option:has(input:focus-visible) {
    outline: 3px solid rgb(var(--v-theme-primary));
    outline-offset: 2px;
}

.emoji-option--selected {
    background: rgba(var(--v-theme-primary), 0.12);
    border-color: rgb(var(--v-theme-primary));
}

.emoji-option--selected .emoji-option__emoji {
    animation: pop 320ms cubic-bezier(0.34, 1.56, 0.64, 1);
}

.emoji-option__emoji {
    font-size: 2.4rem;
    line-height: 1;
}

.emoji-option__label {
    font-size: 0.85rem;
    font-weight: 600;
    text-align: center;
}

@keyframes pop {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.25);
    }
    100% {
        transform: scale(1);
    }
}
</style>
