import vuetify from '@/plugins/vuetify';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { VApp } from 'vuetify/components';
import Welcome from './Welcome.vue';

const inertia = vi.hoisted(() => ({ demoMode: false }));

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    // Imported here because the mock can load before this file's own imports.
    const { defineComponent } = await import('vue');

    return {
        ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
        Head: defineComponent({ render: () => null }),
        usePage: () => ({ props: { demoMode: inertia.demoMode } }),
    };
});

function mountWelcome(demoMode = false) {
    inertia.demoMode = demoMode;

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

    it('offers to create an account or sign in', () => {
        const links = mountWelcome().findAll('a');

        expect(links.map((link) => [link.text(), link.attributes('href')])).toEqual([
            ['Create an account', '/register'],
            ['Sign in', '/login'],
        ]);
    });

    it('offers the demo instead of registration in demo mode', () => {
        const links = mountWelcome(true).findAll('a');

        expect(links.map((link) => [link.text(), link.attributes('href')])).toEqual([
            ['Try the demo', '/login'],
            ['Sign in', '/login'],
        ]);
    });
});
