import vuetify from '@/plugins/vuetify';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import EmojiPicker from './EmojiPicker.vue';

const options = [
    { slug: 'great', label: 'Great', emoji: '😄' },
    { slug: 'okay', label: 'Okay', emoji: '😐' },
    { slug: 'bad', label: 'Bad', emoji: '😞' },
];

describe('EmojiPicker', () => {
    it('labels every option and groups them under a legend', () => {
        const wrapper = mount(EmojiPicker, {
            props: { options, legend: 'Mood', modelValue: null },
            global: { plugins: [vuetify] },
        });

        expect(wrapper.find('legend').text()).toBe('Mood');
        expect(wrapper.findAll('label').map((label) => label.text())).toEqual([
            '😄Great',
            '😐Okay',
            '😞Bad',
        ]);
    });

    it('picks one option with radio buttons', async () => {
        const wrapper = mount(EmojiPicker, {
            props: { options, legend: 'Mood', modelValue: null },
            global: { plugins: [vuetify] },
        });

        expect(wrapper.findAll('input[type="radio"]')).toHaveLength(3);

        await wrapper.findAll('input')[1].trigger('change');

        expect(wrapper.emitted('update:modelValue')).toEqual([['okay']]);
    });

    it('toggles several options with checkboxes', async () => {
        const wrapper = mount(EmojiPicker, {
            props: { options, legend: 'Reasons', multiple: true, modelValue: ['great'] },
            global: { plugins: [vuetify] },
        });

        expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(3);
        expect((wrapper.findAll('input')[0].element as HTMLInputElement).checked).toBe(true);

        await wrapper.findAll('input')[2].trigger('change');
        await wrapper.findAll('input')[0].trigger('change');

        expect(wrapper.emitted('update:modelValue')).toEqual([[['great', 'bad']], [['bad']]]);
    });
});
