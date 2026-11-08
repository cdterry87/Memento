import vuetify from '@/plugins/vuetify';
import type { Recap } from '@/types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { VApp } from 'vuetify/components';
import RecapPage from './Recap.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    // Imported here because the mock can load before this file's own imports.
    const { defineComponent } = await import('vue');

    return {
        ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
        Head: defineComponent({ render: () => null }),
        router: { visit: vi.fn() },
    };
});

const great = { slug: 'great', label: 'Great', emoji: '😄', score: 5 };

const emptyRecap: Recap = {
    year: 2026,
    isYearToDate: true,
    daysInPeriod: 311,
    checkIns: 0,
    longestStreak: 0,
    photos: 0,
    averageScore: null,
    pixels: [],
    months: [],
    moodMix: [],
    topReasons: [],
    lifted: [],
    bestMonth: null,
    goodMemories: [],
};

const fullRecap: Recap = {
    ...emptyRecap,
    year: 2025,
    isYearToDate: false,
    daysInPeriod: 365,
    checkIns: 3,
    longestStreak: 2,
    photos: 1,
    averageScore: 4.3,
    pixels: [
        { date: '2025-01-01', score: 5, emoji: '😄', label: 'Great' },
        { date: '2025-01-02', score: 4, emoji: '🙂', label: 'Good' },
        { date: '2025-03-15', score: 4, emoji: '🙂', label: 'Good' },
    ],
    months: [
        { month: 1, average: 4.5, count: 2 },
        { month: 2, average: null, count: 0 },
        { month: 3, average: 4, count: 1 },
    ],
    moodMix: [{ ...great, count: 3, share: 1 }],
    topReasons: [{ slug: 'family', label: 'Family', emoji: '🏠', count: 2 }],
    lifted: [{ slug: 'family', label: 'Family', emoji: '🏠', average: 4.8 }],
    bestMonth: { month: 1, average: 4.5, count: 5 },
    goodMemories: [
        { date: '2025-01-01', mood: great, reasons: [], body: 'New year walk', photos: [] },
    ],
};

function mountRecap(recap: Recap, years: number[] = []) {
    const Wrapper = defineComponent({
        render: () => h(VApp, () => h(RecapPage, { recap, years, today: '2026-11-07' })),
    });

    return mount(Wrapper, { attachTo: document.body, global: { plugins: [vuetify] } });
}

describe('Recap', () => {
    it('invites a first check-in for a year with no entries', () => {
        const wrapper = mountRecap(emptyRecap);

        expect(wrapper.find('h1').text()).toBe('Your 2026 so far');
        expect(wrapper.text()).toContain('Your 2026 story starts here');
        expect(wrapper.find('#recap-numbers').exists()).toBe(false);
        wrapper.unmount();
    });

    it('tells the story of a year', () => {
        const wrapper = mountRecap(fullRecap, [2026, 2025]);

        expect(wrapper.find('h1').text()).toBe('Your 2025');
        expect(wrapper.find('.recap__stats').text()).toContain('3check-insout of 365 days');
        expect(wrapper.text()).toContain('January was your brightest month');
        expect(wrapper.text()).toContain('Days with family averaged 4.8 out of 5.');
        expect(wrapper.find('.recap__memories a').attributes('href')).toBe('/days/2025-01-01');
        expect(wrapper.text()).toContain('New year walk');
        wrapper.unmount();
    });

    it('switches between years, always offering this year', () => {
        const wrapper = mountRecap(fullRecap, [2025]);
        const links = wrapper.findAll('nav[aria-label="Years"] a');

        expect(links.map((link) => [link.text(), link.attributes('aria-current')])).toEqual([
            ['2026', undefined],
            ['2025', 'page'],
        ]);
        wrapper.unmount();
    });

    it('describes every mood in words, not only as bars', () => {
        const wrapper = mountRecap(fullRecap);

        expect(wrapper.find('ul[aria-label="Mood mix"]').text()).toContain('Great3 days, 100%');
        wrapper.unmount();
    });

    it('gives the trend chart a table of the figures, with gaps named', () => {
        const wrapper = mountRecap(fullRecap);
        const rows = wrapper.findAll('.trend-chart tbody tr').map((row) => row.text());

        expect(rows).toEqual(['January4.5', 'FebruaryNo entries', 'March4']);
        expect(wrapper.find('.trend-chart svg').attributes('role')).toBe('img');
        wrapper.unmount();
    });

    it('links each day with an entry in the year in pixels, as one tab stop', async () => {
        const wrapper = mountRecap(fullRecap);
        const tiles = wrapper.findAll('.year-in-pixels a');

        expect(tiles.map((tile) => tile.attributes('aria-label'))).toEqual([
            'Wednesday, January 1: Great',
            'Thursday, January 2: Good',
            'Saturday, March 15: Good',
        ]);
        expect(tiles.map((tile) => tile.attributes('tabindex'))).toEqual(['0', '-1', '-1']);

        await tiles[0].trigger('keydown', { key: 'ArrowRight' });

        expect(document.activeElement?.getAttribute('data-date')).toBe('2025-01-02');

        await wrapper.find('.year-in-pixels__grid').trigger('keydown', { key: 'End' });

        expect(document.activeElement?.getAttribute('data-date')).toBe('2025-03-15');
        wrapper.unmount();
    });

    it('leaves out days that do not exist, such as February 30', () => {
        const wrapper = mountRecap(fullRecap);

        // 31 rows of 12 months, less Feb 29–31, Apr 31, Jun 31, Sep 31, and Nov 31 in 2025.
        expect(wrapper.findAll('.year-in-pixels__cell--none')).toHaveLength(7);
        wrapper.unmount();
    });
});
