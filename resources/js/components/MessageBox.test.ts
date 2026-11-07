import vuetify from '@/plugins/vuetify';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import MessageBox from './MessageBox.vue';

function mountBox(text: string) {
    return mount(MessageBox, {
        props: { modelValue: text },
        global: { plugins: [vuetify] },
    });
}

describe('MessageBox', () => {
    it('counts emoji as single characters', () => {
        expect(mountBox('Hi 🎉🎉').text()).toContain('5 / 255');
    });

    it('warns once the message is too long', () => {
        const wrapper = mountBox('😀'.repeat(256));

        expect(wrapper.text()).toContain('256 / 255');
        expect(wrapper.text()).toContain('Keep it to 255 characters or fewer.');
    });

    it('allows exactly 255 characters', () => {
        expect(mountBox('😀'.repeat(255)).text()).not.toContain('Keep it to');
    });
});
