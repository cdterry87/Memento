<script setup lang="ts">
import { characterCount } from '@/composables/useLocalDate';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        maxCharacters?: number;
        errorMessages?: string | string[];
    }>(),
    { maxCharacters: 255, errorMessages: undefined },
);

const text = defineModel<string>({ required: true });

const isTooLong = computed(() => characterCount(text.value) > props.maxCharacters);

const messages = computed(() =>
    isTooLong.value
        ? `Keep it to ${props.maxCharacters} characters or fewer.`
        : props.errorMessages,
);
</script>

<template>
    <v-textarea
        v-model="text"
        label="Your note"
        placeholder="A small moment, a big feeling, anything you want to remember…"
        rows="4"
        auto-grow
        :counter="maxCharacters"
        :counter-value="characterCount"
        persistent-counter
        :error-messages="messages"
    />
</template>
