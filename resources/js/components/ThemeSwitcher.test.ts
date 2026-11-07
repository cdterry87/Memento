import { THEME_STORAGE_KEY, readInitialTheme } from '@/composables/useAppTheme';
import vuetify from '@/plugins/vuetify';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ThemeSwitcher from './ThemeSwitcher.vue';

const inertia = vi.hoisted(() => ({
    page: { props: { auth: { user: null as object | null } } },
    patch: vi.fn(),
}));

vi.mock('@inertiajs/vue3', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
    usePage: () => inertia.page,
    router: { patch: inertia.patch },
}));

async function choose(label: string): Promise<void> {
    const wrapper = mount(ThemeSwitcher, {
        global: {
            plugins: [vuetify],
        },
    });

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
        inertia.page.props.auth.user = null;
        inertia.patch.mockClear();
    });

    it('switches to the dark theme and remembers it in the browser', async () => {
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

    it('does not try to save the choice to an account when signed out', async () => {
        await choose('Dark');

        expect(inertia.patch).not.toHaveBeenCalled();
    });

    it("saves the choice to the signed-in user's account", async () => {
        inertia.page.props.auth.user = { name: 'Ada' };

        await choose('Light');

        expect(inertia.patch).toHaveBeenCalledWith(
            '/account/preferences',
            { theme: 'light' },
            expect.objectContaining({ preserveScroll: true }),
        );
    });
});

describe('readInitialTheme', () => {
    beforeEach(() => {
        delete document.documentElement.dataset.theme;
    });

    it('starts with the theme set on the page before the app loads', () => {
        document.documentElement.dataset.theme = 'light';

        expect(readInitialTheme()).toBe('light');
    });

    it('follows the device for anything else', () => {
        document.documentElement.dataset.theme = 'purple';

        expect(readInitialTheme()).toBe('system');
    });
});
