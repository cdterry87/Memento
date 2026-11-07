import vuetify from '@/plugins/vuetify';
import type { Memory } from '@/types';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { VApp } from 'vuetify/components';
import Day from './Day.vue';

const inertia = vi.hoisted(() => ({ visit: vi.fn(), delete: vi.fn(), patch: vi.fn() }));

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    // Imported here because the mock can load before this file's own imports.
    const { defineComponent, h } = await import('vue');

    return {
        ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
        Head: defineComponent({ render: () => null }),
        Link: defineComponent({
            props: { href: { type: String, required: true } },
            setup:
                (props, { slots }) =>
                () =>
                    h('a', { href: props.href }, slots.default?.()),
        }),
        usePage: () => ({ props: { status: null } }),
        router: { visit: inertia.visit, delete: inertia.delete, patch: inertia.patch },
    };
});

const memory: Memory = {
    date: '2026-10-31',
    mood: { slug: 'great', label: 'Great', emoji: '😄', score: 5 },
    reasons: [{ slug: 'family', label: 'Family', emoji: '👪' }],
    body: 'Carved pumpkins.',
    photos: [],
};

const photos = ['first', 'second'].map((id) => ({
    id,
    url: `/media/photos/${id}/full`,
    thumbnailUrl: `/media/photos/${id}/thumb`,
    width: 100,
    height: 80,
}));

function mountDay(isToday = false, entry: Memory = memory) {
    const Wrapper = defineComponent({
        render: () => h(VApp, () => h(Day, { date: entry.date, isToday, memory: entry })),
    });

    return mount(Wrapper, { attachTo: document.body, global: { plugins: [vuetify] } });
}

function buttonNamed(label: string): HTMLElement {
    return [...document.body.querySelectorAll<HTMLElement>('button, a')].find(
        (element) => element.textContent?.trim() === label,
    )!;
}

describe('Day', () => {
    let wrapper: ReturnType<typeof mountDay> | undefined;

    beforeEach(() => {
        inertia.visit.mockClear();
        inertia.delete.mockClear();
        inertia.patch.mockClear();
    });

    afterEach(() => {
        wrapper?.unmount();
        wrapper = undefined;
    });

    it('shows the full date and the entry', () => {
        wrapper = mountDay();

        expect(wrapper.find('h1').text()).toBe('Saturday, October 31, 2026');
        expect(wrapper.text()).toContain('Great');
        expect(wrapper.text()).toContain('Carved pumpkins.');
    });

    it('calls today "Today"', () => {
        wrapper = mountDay(true);

        expect(wrapper.find('h1').text()).toBe('Today');
    });

    it('links to the check-in to edit the day', () => {
        wrapper = mountDay();

        expect(buttonNamed('Edit').getAttribute('href')).toBe('/days/2026-10-31/check-in');
    });

    it('asks before deleting, then deletes', async () => {
        wrapper = mountDay();

        buttonNamed('Delete').click();
        await flushPromises();

        expect(document.body.textContent).toContain('Delete this day?');
        expect(inertia.delete).not.toHaveBeenCalled();

        const confirm = [...document.body.querySelectorAll<HTMLElement>('.v-dialog button')].find(
            (button) => button.textContent?.trim() === 'Delete',
        )!;
        confirm.click();

        expect(inertia.delete).toHaveBeenCalledWith('/days/2026-10-31', expect.any(Object));
    });

    it('can back out of deleting', async () => {
        wrapper = mountDay();

        buttonNamed('Delete').click();
        await flushPromises();
        buttonNamed('Keep it').click();

        expect(inertia.delete).not.toHaveBeenCalled();
    });

    it('makes a photo the cover from the lightbox', async () => {
        wrapper = mountDay(false, { ...memory, photos });

        document.body.querySelector<HTMLElement>('button[aria-label="Open photo 2 of 2"]')!.click();
        await flushPromises();
        document.body
            .querySelector<HTMLElement>('button[aria-label="Make this the cover photo"]')!
            .click();

        expect(inertia.patch).toHaveBeenCalledWith(
            '/days/2026-10-31/photos/order',
            { photos: ['second', 'first'] },
            expect.objectContaining({ preserveState: true }),
        );
    });

    it('asks before deleting a photo, then deletes it', async () => {
        wrapper = mountDay(false, { ...memory, photos });

        document.body.querySelector<HTMLElement>('button[aria-label="Open photo 1 of 2"]')!.click();
        await flushPromises();
        document.body.querySelector<HTMLElement>('button[aria-label="Delete this photo"]')!.click();
        await flushPromises();

        expect(document.body.textContent).toContain('Delete this photo?');
        expect(inertia.delete).not.toHaveBeenCalled();

        const dialogs = [...document.body.querySelectorAll('.v-dialog')];
        const confirm = [...dialogs.at(-1)!.querySelectorAll<HTMLElement>('button')].find(
            (button) => button.textContent?.trim() === 'Delete',
        )!;
        confirm.click();

        expect(inertia.delete).toHaveBeenCalledWith(
            '/days/2026-10-31/photos/first',
            expect.any(Object),
        );
    });
});
