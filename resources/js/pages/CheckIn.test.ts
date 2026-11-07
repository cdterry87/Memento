import vuetify from '@/plugins/vuetify';
import type { Memory } from '@/types';
import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { h } from 'vue';
import { VApp } from 'vuetify/components';
import CheckIn from './CheckIn.vue';

const inertia = vi.hoisted(() => ({ put: vi.fn() }));

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const { defineComponent, h, reactive } = await import('vue');
    const original = await importOriginal<typeof import('@inertiajs/vue3')>();

    return {
        ...original,
        Head: defineComponent({ render: () => null }),
        Link: defineComponent({
            setup:
                (_, { slots }) =>
                () =>
                    h('a', slots.default?.()),
        }),
        useForm: <T extends object>(data: T) => {
            const form = reactive({
                ...data,
                errors: {} as Record<string, string>,
                processing: false,
                transform(callback: (value: T) => T) {
                    return {
                        put: (url: string, options: unknown) =>
                            inertia.put(
                                url,
                                callback(
                                    Object.fromEntries(
                                        Object.keys(data).map((key) => [
                                            key,
                                            form[key as keyof typeof form],
                                        ]),
                                    ) as T,
                                ),
                                options,
                            ),
                    };
                },
            });

            return form;
        },
    };
});

const moods = [
    { slug: 'great', label: 'Great', emoji: '😄', score: 5 },
    { slug: 'okay', label: 'Okay', emoji: '😐', score: 3 },
];
const reasons = [
    { slug: 'work', label: 'Work', emoji: '💼' },
    { slug: 'health', label: 'Health', emoji: '💪' },
];

function mountCheckIn(props: { memory?: Memory | null; initialMood?: string | null } = {}) {
    return mount(
        {
            render: () =>
                h(VApp, () =>
                    h(CheckIn, {
                        date: '2026-11-07',
                        isToday: true,
                        moods,
                        reasons,
                        memory: null,
                        initialMood: null,
                        ...props,
                    }),
                ),
        },
        { global: { plugins: [vuetify] } },
    );
}

function button(wrapper: ReturnType<typeof mountCheckIn>, label: string) {
    return wrapper.findAll('button').find((item) => item.text() === label);
}

describe('CheckIn', () => {
    beforeEach(() => {
        inertia.put.mockClear();
        window.localStorage.clear();
    });

    it('starts by asking for a mood, with no way to save yet', () => {
        const wrapper = mountCheckIn();

        expect(wrapper.find('h1').text()).toBe('How was your day?');
        expect(button(wrapper, 'Save')).toBeUndefined();
    });

    it('moves on as soon as a mood is picked', async () => {
        const wrapper = mountCheckIn();

        await wrapper.findAll('input[type="radio"]')[0].trigger('change');
        await flushPromises();

        expect(wrapper.find('h1').text()).toBe('What made it that way?');
        expect(button(wrapper, 'Save')).toBeDefined();
    });

    it('starts at the reasons when a mood was picked on today', () => {
        expect(mountCheckIn({ initialMood: 'great' }).find('h1').text()).toBe(
            'What made it that way?',
        );
    });

    it('saves the mood, reasons, and trimmed note for the date', async () => {
        const wrapper = mountCheckIn({ initialMood: 'okay' });

        await wrapper.findAll('input[type="checkbox"]')[1].trigger('change');
        await button(wrapper, 'Next')!.trigger('click');
        await wrapper.find('textarea').setValue('  A quiet day.  ');
        await button(wrapper, 'Save')!.trigger('click');

        expect(inertia.put).toHaveBeenCalledWith(
            '/days/2026-11-07',
            { mood: 'okay', reasons: ['health'], body: 'A quiet day.' },
            expect.anything(),
        );
    });

    it('keeps an unsaved note as a draft for the day', async () => {
        const wrapper = mountCheckIn({ initialMood: 'okay' });

        await button(wrapper, 'Next')!.trigger('click');
        await wrapper.find('textarea').setValue('Do not lose me');

        expect(window.localStorage.getItem('memento.draft.2026-11-07')).toBe('Do not lose me');
    });
});
