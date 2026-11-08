import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import AppFooter from './AppFooter.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    // Imported here because the mock can load before this file's own imports.
    const { defineComponent, h } = await import('vue');

    return {
        ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
        Link: defineComponent({
            props: { href: { type: String, required: true } },
            setup:
                (props, { slots }) =>
                () =>
                    h('a', { href: props.href }, slots.default?.()),
        }),
    };
});

describe('AppFooter', () => {
    it('shows the copyright for this year, the privacy policy, and the credit', () => {
        const wrapper = mount(AppFooter);
        const year = new Date().getFullYear();

        expect(wrapper.text()).toContain(`© ${year}. All rights reserved.`);
        expect(wrapper.find('a[href="/privacy"]').text()).toBe('Privacy Policy');
    });

    it('opens the developer site in a new tab, and says so to screen readers', () => {
        const link = mount(AppFooter).find('a[href="https://chaseterry.com"]');

        expect(link.attributes('target')).toBe('_blank');
        expect(link.attributes('rel')).toBe('noopener');
        expect(link.text()).toBe('Chase Terry (opens in a new tab)');
    });
});
