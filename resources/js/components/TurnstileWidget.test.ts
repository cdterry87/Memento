import vuetify from '@/plugins/vuetify';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import TurnstileWidget from './TurnstileWidget.vue';

/** Stands in for Cloudflare's script: loading it installs a fake `window.turnstile`. */
function fakeCloudflare() {
    const api = {
        callback: undefined as ((token: string) => void) | undefined,
        render: vi.fn((_element: HTMLElement, options: Record<string, unknown>) => {
            api.callback = options.callback as (token: string) => void;

            return 'widget-1';
        }),
        reset: vi.fn(),
        remove: vi.fn(),
    };

    vi.spyOn(document.head, 'append').mockImplementation((script) => {
        window.turnstile = api;
        (script as HTMLScriptElement).onload?.(new Event('load'));
    });

    return api;
}

describe('TurnstileWidget', () => {
    afterEach(() => {
        vi.restoreAllMocks();
        delete window.turnstile;
    });

    it('renders the check with the site key and passes its token up', async () => {
        const api = fakeCloudflare();
        const wrapper = mount(TurnstileWidget, {
            props: { siteKey: 'site-key', action: 'register', modelValue: '' },
            global: { plugins: [vuetify] },
        });
        await flushPromises();

        expect(api.render).toHaveBeenCalledWith(
            expect.any(HTMLElement),
            expect.objectContaining({ sitekey: 'site-key', action: 'register' }),
        );

        api.callback?.('a-token');

        expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['a-token']);
    });

    it('clears the token and asks for a new one on reset', async () => {
        const api = fakeCloudflare();
        const wrapper = mount(TurnstileWidget, {
            props: { siteKey: 'site-key', modelValue: 'used-token' },
            global: { plugins: [vuetify] },
        });
        await flushPromises();

        (wrapper.vm as unknown as { reset: () => void }).reset();

        expect(api.reset).toHaveBeenCalledWith('widget-1');
        expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['']);
    });

    it('loads nothing without a site key', async () => {
        const append = vi.spyOn(document.head, 'append');

        mount(TurnstileWidget, {
            props: { siteKey: null, modelValue: '' },
            global: { plugins: [vuetify] },
        });
        await flushPromises();

        expect(append).not.toHaveBeenCalled();
    });
});
