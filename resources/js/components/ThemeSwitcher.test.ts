import { THEME_STORAGE_KEY, readStoredTheme } from '@/composables/useAppTheme';
import vuetify from '@/plugins/vuetify';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import ThemeSwitcher from './ThemeSwitcher.vue';

function mountSwitcher() {
    return mount(ThemeSwitcher, {
        global: {
            plugins: [vuetify],
        },
    });
}

async function choose(label: string): Promise<void> {
    const wrapper = mountSwitcher();

    await wrapper
        .findAll('button')
        .find((button) => button.text() === label)!
        .trigger('click');
}

describe('ThemeSwitcher', () => {
    beforeEach(() => {
        vuetify.theme.change('system');
        window.localStorage.clear();
        delete document.documentElement.dataset.theme;
    });

    it('switches to the dark theme and remembers it', async () => {
        await choose('Dark');

        expect(vuetify.theme.global.name.value).toBe('dark');
        expect(window.localStorage.getItem(THEME_STORAGE_KEY)).toBe('dark');
        expect(document.documentElement.dataset.theme).toBe('dark');
    });

    it('forgets the choice when going back to the device setting', async () => {
        await choose('Light');
        await choose('System');

        expect(vuetify.theme.isSystem.value).toBe(true);
        expect(window.localStorage.getItem(THEME_STORAGE_KEY)).toBeNull();
        expect(document.documentElement.dataset.theme).toBeUndefined();
    });
});

describe('readStoredTheme', () => {
    beforeEach(() => {
        window.localStorage.clear();
    });

    it('reads a saved choice', () => {
        window.localStorage.setItem(THEME_STORAGE_KEY, 'light');

        expect(readStoredTheme()).toBe('light');
    });

    it('falls back to the device setting for anything else', () => {
        window.localStorage.setItem(THEME_STORAGE_KEY, 'purple');

        expect(readStoredTheme()).toBe('system');
    });
});
