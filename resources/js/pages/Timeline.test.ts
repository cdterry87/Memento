import vuetify from '@/plugins/vuetify';
import type { Memory } from '@/types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { VApp } from 'vuetify/components';
import Timeline from './Timeline.vue';

const inertia = vi.hoisted(() => ({ visit: vi.fn() }));

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    // Imported here because the mock can load before this file's own imports.
    const { defineComponent, h } = await import('vue');

    return {
        ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
        Head: defineComponent({ render: () => null }),
        usePage: () => ({ props: { status: null } }),
        router: { visit: inertia.visit },
        InfiniteScroll: defineComponent({
            props: { as: { type: String, default: 'div' } },
            setup:
                (props, { slots }) =>
                () =>
                    h(props.as, slots.default?.()),
        }),
    };
});

function memory(date: string, overrides: Partial<Memory> = {}): Memory {
    return {
        date,
        mood: { slug: 'good', label: 'Good', emoji: '🙂', score: 4 },
        reasons: [],
        body: null,
        photos: [],
        ...overrides,
    };
}

function mountTimeline(memories: Memory[]) {
    const Wrapper = defineComponent({
        render: () => h(VApp, () => h(Timeline, { memories: { data: memories } })),
    });

    return mount(Wrapper, { global: { plugins: [vuetify] } });
}

describe('Timeline', () => {
    it('invites the first check-in when there are no entries', () => {
        const wrapper = mountTimeline([]);

        expect(wrapper.text()).toContain('Nothing here yet');
        expect(wrapper.find('a[href="/today"]').text()).toBe('Check in today');
    });

    it('puts a heading above the first entry of each month', () => {
        const wrapper = mountTimeline([
            memory('2026-11-06'),
            memory('2026-11-01'),
            memory('2026-10-31'),
            memory('2025-10-31'),
        ]);

        expect(wrapper.findAll('h2').map((heading) => heading.text())).toEqual([
            'November 2026',
            'October 2026',
            'October 2025',
        ]);
    });

    it('links each entry to its day', () => {
        const wrapper = mountTimeline([memory('2026-11-06'), memory('2026-11-01')]);

        expect(wrapper.findAll('li a').map((link) => link.attributes('href'))).toEqual([
            '/days/2026-11-06',
            '/days/2026-11-01',
        ]);
    });

    it('shows the mood, reasons, and message, with text for screen readers', () => {
        const wrapper = mountTimeline([
            memory('2026-11-06', {
                reasons: [
                    { slug: 'friends', label: 'Friends', emoji: '👯' },
                    { slug: 'food', label: 'Food', emoji: '🍕' },
                ],
                body: 'Pizza night.',
            }),
        ]);

        const entry = wrapper.find('li').text();

        expect(entry).toContain('Good');
        expect(entry).toContain('Because of Friends, Food');
        expect(entry).toContain('Pizza night.');
        expect(entry).toContain('Friday, November 6, 2026');
    });
});
