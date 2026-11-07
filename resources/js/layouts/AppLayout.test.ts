import vuetify from '@/plugins/vuetify';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h, reactive } from 'vue';
import AppLayout from './AppLayout.vue';

const inertia = vi.hoisted(() => ({
    page: { url: '/today' },
    visit: vi.fn(),
}));

vi.mock('@inertiajs/vue3', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
    usePage: () => inertia.page,
    router: { visit: inertia.visit },
    Link: defineComponent({
        props: { href: { type: String, required: true } },
        setup:
            (props, { slots }) =>
            () =>
                h('a', { href: props.href }, slots.default?.()),
    }),
}));

function mountLayout(url: string) {
    inertia.page = reactive({ url });

    return mount(AppLayout, {
        attachTo: document.body,
        slots: { default: () => h('p', 'Page content') },
        global: {
            plugins: [vuetify],
        },
    });
}

type Layout = ReturnType<typeof mountLayout>;

/** The menu links shown in the app bar on wide screens. */
function barLinks(wrapper: Layout) {
    return wrapper.findAll('a.v-btn');
}

function barLink(wrapper: Layout, label: string) {
    return barLinks(wrapper).find((link) => link.text() === label)!;
}

async function openMenu(wrapper: Layout): Promise<HTMLAnchorElement[]> {
    await wrapper.find('button[aria-label="Menu"]').trigger('click');
    await flushPromises();

    return [...document.body.querySelectorAll<HTMLAnchorElement>('.v-overlay .v-list a')];
}

describe('AppLayout', () => {
    let wrapper: Layout | undefined;

    beforeEach(() => {
        inertia.visit.mockClear();
    });

    afterEach(() => {
        wrapper?.unmount();
        wrapper = undefined;
    });

    it('renders the page inside the layout', () => {
        wrapper = mountLayout('/today');

        expect(wrapper.find('main').text()).toContain('Page content');
    });

    it('links to each main screen from the app bar', () => {
        wrapper = mountLayout('/today');

        expect(barLinks(wrapper).map((link) => [link.text(), link.attributes('href')])).toEqual([
            ['Today', '/today'],
            ['Timeline', '/timeline'],
            ['Recap', '/recap'],
            ['Account', '/account'],
        ]);
    });

    it('marks the current screen, including pages beneath it', () => {
        wrapper = mountLayout('/recap/2026?from=today');

        expect(barLink(wrapper, 'Recap').attributes('aria-current')).toBe('page');
        expect(barLink(wrapper, 'Today').attributes('aria-current')).toBeUndefined();
    });

    it('navigates with Inertia on a plain click', async () => {
        wrapper = mountLayout('/today');

        await barLink(wrapper, 'Timeline').trigger('click');

        expect(inertia.visit).toHaveBeenCalledWith('/timeline');
    });

    it('leaves modifier clicks to the browser, so links can open in a new tab', async () => {
        wrapper = mountLayout('/today');

        await barLink(wrapper, 'Timeline').trigger('click', { metaKey: true });

        expect(inertia.visit).not.toHaveBeenCalled();
    });

    it('offers every screen from the menu button on narrow screens', async () => {
        wrapper = mountLayout('/timeline');

        const links = await openMenu(wrapper);

        expect(links.map((link) => [link.textContent?.trim(), link.getAttribute('href')])).toEqual([
            ['Today', '/today'],
            ['Timeline', '/timeline'],
            ['Recap', '/recap'],
            ['Account', '/account'],
        ]);
        expect(links[1].getAttribute('aria-current')).toBe('page');
    });
});
