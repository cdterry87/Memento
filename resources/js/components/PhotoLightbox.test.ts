import vuetify from '@/plugins/vuetify';
import type { Photo } from '@/types';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import { defineComponent, h, ref } from 'vue';
import { VApp } from 'vuetify/components';
import PhotoLightbox from './PhotoLightbox.vue';

const photos: Photo[] = ['a', 'b', 'c'].map((id) => ({
    id,
    url: `/media/photos/${id}/full`,
    thumbnailUrl: `/media/photos/${id}/thumb`,
    width: 100,
    height: 80,
}));

const open = ref(true);
const index = ref(0);

function mountLightbox() {
    open.value = true;
    index.value = 0;

    const Wrapper = defineComponent({
        render: () =>
            h(VApp, () =>
                h(PhotoLightbox, {
                    photos,
                    label: 'Saturday, November 7, 2026',
                    modelValue: open.value,
                    'onUpdate:modelValue': (value: boolean) => (open.value = value),
                    index: index.value,
                    'onUpdate:index': (value: number) => (index.value = value),
                }),
            ),
    });

    return mount(Wrapper, { attachTo: document.body, global: { plugins: [vuetify] } });
}

function image(): HTMLImageElement {
    return document.body.querySelector<HTMLImageElement>('.lightbox__image')!;
}

function keydown(key: string): void {
    document.body
        .querySelector('.lightbox')!
        .dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true }));
}

describe('PhotoLightbox', () => {
    let wrapper: ReturnType<typeof mountLightbox> | undefined;

    afterEach(() => {
        wrapper?.unmount();
    });

    it('shows the full-size photo with a description', async () => {
        wrapper = mountLightbox();
        await flushPromises();

        expect(image().getAttribute('src')).toBe('/media/photos/a/full');
        expect(image().getAttribute('alt')).toBe('Photo 1 of 3 from Saturday, November 7, 2026');
    });

    it('moves between photos with the arrow keys, stopping at the ends', async () => {
        wrapper = mountLightbox();
        await flushPromises();

        keydown('ArrowLeft');
        expect(index.value).toBe(0);

        for (let press = 0; press < 3; press++) {
            keydown('ArrowRight');
            await flushPromises();
        }

        expect(index.value).toBe(2);
    });

    it('moves to the next photo on a swipe left', async () => {
        wrapper = mountLightbox();
        await flushPromises();

        const stage = document.body.querySelector('.lightbox__stage')!;
        stage.dispatchEvent(
            new MouseEvent('pointerdown', { clientX: 300, clientY: 100 }) as PointerEvent,
        );
        stage.dispatchEvent(
            new MouseEvent('pointerup', { clientX: 150, clientY: 110 }) as PointerEvent,
        );

        expect(index.value).toBe(1);
    });

    it('closes from the close button', async () => {
        wrapper = mountLightbox();
        await flushPromises();

        document.body.querySelector<HTMLElement>('button[aria-label="Close"]')!.click();

        expect(open.value).toBe(false);
    });
});
