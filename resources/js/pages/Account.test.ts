import vuetify from '@/plugins/vuetify';
import type { CurrentUser } from '@/types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { VApp } from 'vuetify/components';
import Account from './Account.vue';

const inertia = vi.hoisted(() => ({
    patch: vi.fn(),
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
        router: { patch: inertia.patch },
    };
});

function mountAccount(user: Partial<CurrentUser> = {}) {
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

    const Wrapper = defineComponent({
        render: () => h(VApp, () => h(Account, { timezones: ['UTC'] })),
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
