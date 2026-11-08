<script setup lang="ts">
/**
 * A Cloudflare Turnstile check, so forms anyone can submit stay free of
 * bots. The token it produces is bound with v-model and sent with the form.
 * Tokens work once, so call `reset()` after every submission.
 *
 * Cloudflare's script loads only on pages that show the widget.
 */
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useTheme } from 'vuetify';

interface TurnstileApi {
    render: (element: HTMLElement, options: Record<string, unknown>) => string;
    reset: (widgetId: string) => void;
    remove: (widgetId: string) => void;
}

declare global {
    interface Window {
        turnstile?: TurnstileApi;
    }
}

const SCRIPT_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

let scriptLoading: Promise<TurnstileApi> | null = null;

function loadTurnstile(): Promise<TurnstileApi> {
    scriptLoading ??= new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = SCRIPT_URL;
        script.async = true;
        script.onload = () => (window.turnstile ? resolve(window.turnstile) : reject());
        script.onerror = () => {
            scriptLoading = null;
            reject(new Error('Turnstile failed to load'));
        };
        document.head.append(script);
    });

    return scriptLoading;
}

const props = defineProps<{
    siteKey: string | null;
    /** Shown in Cloudflare's analytics, such as "register" or "demo-login". */
    action?: string;
    /** "interaction-only" stays hidden unless Cloudflare needs a click. */
    appearance?: 'always' | 'execute' | 'interaction-only';
}>();

const token = defineModel<string>({ default: '' });

const container = ref<HTMLElement | null>(null);
const failed = ref(false);
const theme = useTheme();
let api: TurnstileApi | null = null;
let widgetId: string | null = null;

function render(): void {
    if (!api || !container.value || !props.siteKey) {
        return;
    }

    if (widgetId !== null) {
        api.remove(widgetId);
    }

    token.value = '';
    widgetId = api.render(container.value, {
        sitekey: props.siteKey,
        action: props.action,
        appearance: props.appearance ?? 'always',
        theme: theme.global.current.value.dark ? 'dark' : 'light',
        size: 'flexible',
        callback: (value: string) => (token.value = value),
        'expired-callback': () => (token.value = ''),
        'error-callback': () => (token.value = ''),
    });
}

/** Gets a fresh token after a submission, since each one works only once. */
function reset(): void {
    token.value = '';

    if (api && widgetId !== null) {
        api.reset(widgetId);
    }
}

defineExpose({ reset });

onMounted(async () => {
    if (!props.siteKey) {
        return;
    }

    try {
        api = await loadTurnstile();
        render();
    } catch {
        failed.value = true;
    }
});

watch(() => theme.global.current.value.dark, render);

onBeforeUnmount(() => {
    if (api && widgetId !== null) {
        api.remove(widgetId);
    }
});
</script>

<template>
    <div class="turnstile">
        <div ref="container" />
        <p v-if="failed" class="text-body-2 text-error mt-2" role="alert">
            The security check couldn't load. Turn off any content blockers for this page, then
            reload it.
        </p>
    </div>
</template>
