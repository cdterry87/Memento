import vuetify from '@/plugins/vuetify';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { VApp } from 'vuetify/components';
import Welcome from './Welcome.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
    Head: defineComponent({ render: () => null }),
}));

function mountWelcome() {
    const Wrapper = defineComponent({
        render: () => h(VApp, () => h(Welcome, { appName: 'Memento' })),
    });

    return mount(Wrapper, {
        global: {
            plugins: [vuetify],
        },
    });
}

describe('Welcome', () => {
    it('shows the app name', () => {
        expect(mountWelcome().find('h1').text()).toBe('Memento');
    });

    it('links into the app', () => {
        const link = mountWelcome().find('a');

        expect(link.text()).toBe('Take a look');
        expect(link.attributes('href')).toBe('/today');
    });
});
