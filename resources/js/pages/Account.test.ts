import vuetify from '@/plugins/vuetify';
import type { CurrentUser, DataExportStatus } from '@/types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { VApp } from 'vuetify/components';
import Account from './Account.vue';

const inertia = vi.hoisted(() => ({
    patch: vi.fn(),
    post: vi.fn(),
    pollStart: vi.fn(),
    pollStop: vi.fn(),
    user: {} as CurrentUser,
}));

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    // Imported here because the mock can load before this file's own imports.
    const { defineComponent, h } = await import('vue');

    return {
        ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
        Head: defineComponent({ render: () => null }),
        Form: defineComponent({
            setup:
                (_, { slots }) =>
                () =>
                    h('form', slots.default?.({ errors: {}, processing: false })),
        }),
        usePage: () => ({ props: { status: null, errors: {}, auth: { user: inertia.user } } }),
        router: { patch: inertia.patch, post: inertia.post },
        usePoll: () => ({ start: inertia.pollStart, stop: inertia.pollStop }),
    };
});

function mountAccount(user: Partial<CurrentUser> = {}, dataExport: DataExportStatus | null = null) {
    inertia.user = {
        name: 'Ada Lovelace',
        email: 'ada@example.com',
        emailVerified: true,
        timezone: 'UTC',
        theme: 'system',
        showQuotes: true,
        reminderEmails: false,
        reminderTime: '20:00',
        onThisDayEmails: false,
        isDemo: false,
        ...user,
    };
    inertia.patch.mockClear();
    inertia.post.mockClear();
    inertia.pollStart.mockClear();

    const Wrapper = defineComponent({
        render: () => h(VApp, () => h(Account, { timezones: ['UTC'], dataExport })),
    });

    return mount(Wrapper, { global: { plugins: [vuetify] } });
}

function switchNamed(wrapper: ReturnType<typeof mountAccount>, label: string) {
    return wrapper
        .findAll('.v-switch')
        .find((item) => item.find('label').text() === label)!
        .find('input');
}

describe('Account emails', () => {
    it('turns the daily reminder on', async () => {
        const wrapper = mountAccount();

        await switchNamed(wrapper, 'Daily check-in reminder').setValue(true);

        expect(inertia.patch).toHaveBeenCalledWith(
            '/account/preferences',
            { reminder_emails: true },
            expect.anything(),
        );
    });

    it('offers a reminder time only while reminders are on', () => {
        expect(mountAccount().text()).not.toContain('Reminder time');
        expect(mountAccount({ reminderEmails: true }).text()).toContain('Reminder time');
    });

    it('waits for a confirmed address before emails can be turned on', () => {
        const wrapper = mountAccount({ emailVerified: false });

        expect(wrapper.text()).toContain('Confirm your email address to get emails.');
        expect(switchNamed(wrapper, 'On this day').attributes('disabled')).toBeDefined();
    });
});

describe('Account data', () => {
    function buttonNamed(wrapper: ReturnType<typeof mountAccount>, label: string) {
        return wrapper.findAll('button, a').find((item) => item.text() === label);
    }

    it('asks for an export', async () => {
        const wrapper = mountAccount();

        await buttonNamed(wrapper, 'Export my data')!.trigger('click');

        expect(inertia.post).toHaveBeenCalledWith('/account/export', {}, expect.anything());
    });

    it('waits for an export in progress, checking back until it is ready', () => {
        const wrapper = mountAccount({}, { status: 'pending', expiresAt: null, downloadUrl: null });

        expect(wrapper.text()).toContain('Your export is being made.');
        expect(buttonNamed(wrapper, 'Export my data')!.attributes('disabled')).toBeDefined();
        expect(inertia.pollStart).toHaveBeenCalled();
    });

    it('offers the download once the export is ready', () => {
        const wrapper = mountAccount(
            {},
            {
                status: 'ready',
                expiresAt: '2026-11-08T12:00:00+00:00',
                downloadUrl: '/account/export/abc?signature=xyz',
            },
        );

        expect(buttonNamed(wrapper, 'Download your export')!.attributes('href')).toBe(
            '/account/export/abc?signature=xyz',
        );
        expect(buttonNamed(wrapper, 'Make a new export')).toBeDefined();
    });

    it('offers account deletion', () => {
        expect(buttonNamed(mountAccount(), 'Delete my account')).toBeDefined();
    });

    it('offers neither export nor deletion to the demo account', () => {
        const wrapper = mountAccount({ isDemo: true });

        expect(wrapper.text()).not.toContain('Your data');
        expect(buttonNamed(wrapper, 'Export my data')).toBeUndefined();
        expect(buttonNamed(wrapper, 'Delete my account')).toBeUndefined();
    });
});
