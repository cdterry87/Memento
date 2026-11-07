import vuetify from '@/plugins/vuetify';
import type { Memory, Quote } from '@/types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { VApp } from 'vuetify/components';
import Today from './Today.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    // Imported here because the mock can load before this file's own imports.
    const { defineComponent } = await import('vue');

    return {
        ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
        Head: defineComponent({ render: () => null }),
        usePage: () => ({
            props: { status: null, auth: { user: { name: 'Ada Lovelace', timezone: 'UTC' } } },
        }),
        router: { visit: vi.fn() },
    };
});

function entryOn(date: string): Memory {
    return {
        date,
        mood: { slug: 'great', label: 'Great', emoji: '😄', score: 5 },
        reasons: [],
        body: `Written on ${date}`,
        photos: [],
    };
}

const quote: Quote = { text: 'Earth laughs in flowers.', author: 'Ralph Waldo Emerson' };

function mountToday(
    props: {
        onThisDay?: Memory[];
        quote?: Quote | null;
        quoteBackground?: string | null;
        recapYear?: number | null;
    } = {},
) {
    const Wrapper = defineComponent({
        render: () =>
            h(VApp, () =>
                h(Today, {
                    today: '2026-11-07',
                    memory: null,
                    moods: [],
                    onThisDay: [],
                    quote: null,
                    quoteBackground: null,
                    recapYear: null,
                    ...props,
                }),
            ),
    });

    return mount(Wrapper, { global: { plugins: [vuetify] } });
}

describe('Today', () => {
    it('shows only the greeting and check-in on a quiet day', () => {
        const wrapper = mountToday();

        expect(wrapper.find('h1').text()).toContain('Ada');
        expect(wrapper.text()).not.toContain('On this day');
        expect(wrapper.find('blockquote').exists()).toBe(false);
        expect(wrapper.text()).not.toContain('in a story');
    });

    it('labels each earlier entry by how long ago it was', () => {
        const wrapper = mountToday({ onThisDay: [entryOn('2025-11-07'), entryOn('2023-11-07')] });

        expect(wrapper.find('#on-this-day-heading').text()).toBe('On this day');
        expect(wrapper.findAll('.on-this-day__item').map((item) => item.find('p').text())).toEqual([
            '1 year ago',
            '3 years ago',
        ]);
        expect(wrapper.find('.on-this-day a').attributes('href')).toBe('/days/2025-11-07');
    });

    it('shows the quote of the day with its author', () => {
        const wrapper = mountToday({ quote });

        expect(wrapper.find('blockquote').text()).toBe('Earth laughs in flowers.');
        expect(wrapper.find('figcaption').text()).toBe('Ralph Waldo Emerson');
    });

    it('puts the photo for the day behind the quote', () => {
        const card = mountToday({
            quote,
            quoteBackground: '/inspirational-quote-backgrounds/forest1.jpg',
        }).find('.quote-card');

        expect(card.classes()).toContain('quote-card--photo');
        expect(card.attributes('style')).toContain('/inspirational-quote-backgrounds/forest1.jpg');
    });

    it('keeps the plain card when there is no photo', () => {
        const card = mountToday({ quote }).find('.quote-card');

        expect(card.classes()).not.toContain('quote-card--photo');
    });

    it('links to the recap when one is promoted', () => {
        const banner = mountToday({ recapYear: 2026 }).find('a[href="/recap/2026"]');

        expect(banner.text()).toContain('Your 2026 in a story');
    });
});
