import vuetify from '@/plugins/vuetify';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { VApp } from 'vuetify/components';
import Welcome from './Welcome.vue';

const inertia = vi.hoisted(() => ({ demoMode: false, status: null as string | null }));

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    // Imported here because the mock can load before this file's own imports.
    const { defineComponent } = await import('vue');

    return {
        ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
        Head: defineComponent({ render: () => null }),
        usePage: () => ({ props: { demoMode: inertia.demoMode, status: inertia.status } }),
    };
});

function mountWelcome(demoMode = false, status: string | null = null) {
    inertia.demoMode = demoMode;
    inertia.status = status;

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

    it('confirms a deleted account', () => {
        expect(mountWelcome(false, 'account-deleted').text()).toContain(
            'Your account, entries, and photos have been deleted.',
        );
        expect(mountWelcome().text()).not.toContain('have been deleted');
    });
});
