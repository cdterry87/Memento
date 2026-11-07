import vuetify from '@/plugins/vuetify';
import type { Photo } from '@/types';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import PhotoPicker from './PhotoPicker.vue';

vi.mock('@inertiajs/vue3', () => ({ usePage: () => ({ props: {} }), router: {} }));

function photo(name: string, type = 'image/jpeg'): File {
    return new File(['x'], name, { type });
}

const saved: Photo = { id: 'a', url: '/full', thumbnailUrl: '/thumb', width: 10, height: 10 };

function mountPicker(props: { existing?: Photo[]; uploadingCount?: number } = {}) {
    let files: File[] = [];

    const wrapper = mount(PhotoPicker, {
        props: {
            ...props,
            modelValue: files,
            'onUpdate:modelValue': (value: File[]) => {
                files = value;
                void wrapper.setProps({ modelValue: value });
            },
        },
        global: { plugins: [vuetify] },
    });

    return { wrapper, files: () => files };
}

async function choose(wrapper: ReturnType<typeof mountPicker>['wrapper'], chosen: File[]) {
    const input = wrapper.find('input[type="file"]');
    Object.defineProperty(input.element, 'files', { value: chosen, configurable: true });
    await input.trigger('change');
}

describe('PhotoPicker', () => {
    beforeEach(() => {
        URL.createObjectURL = () => 'blob:preview';
        URL.revokeObjectURL = vi.fn();
    });

    it('only accepts JPEG, PNG, and WebP photos', () => {
        const { wrapper } = mountPicker();

        expect(wrapper.find('input[type="file"]').attributes('accept')).toBe(
            'image/jpeg,image/png,image/webp',
        );
    });

    it('adds chosen photos and explains any it leaves out', async () => {
        const { wrapper, files } = mountPicker();

        await choose(wrapper, [photo('one.jpg'), photo('clip.gif', 'image/gif')]);

        expect(files().map((file) => file.name)).toEqual(['one.jpg']);
        expect(wrapper.text()).toContain("clip.gif isn't a JPEG, PNG, or WebP photo.");
    });

    it('counts saved and uploading photos toward the limit of four', async () => {
        const { wrapper, files } = mountPicker({ existing: [saved], uploadingCount: 1 });

        await choose(wrapper, [photo('a.jpg'), photo('b.jpg'), photo('c.jpg')]);

        expect(files().map((file) => file.name)).toEqual(['a.jpg', 'b.jpg']);
        expect(wrapper.text()).toContain('A day can have up to 4 photos, so c.jpg was left out.');
        const addButton = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Add photo')!;

        expect(addButton.attributes('disabled')).toBeDefined();
    });

    it('lets a new photo be removed before saving', async () => {
        const { wrapper, files } = mountPicker();

        await choose(wrapper, [photo('one.jpg')]);
        await wrapper.find('button[aria-label="Remove one.jpg"]').trigger('click');

        expect(files()).toEqual([]);
    });
});
