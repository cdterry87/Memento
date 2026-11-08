import vuetify from '@/plugins/vuetify';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { VApp } from 'vuetify/components';
import TurnstileWidget from '@/components/TurnstileWidget.vue';
import Login from './Login.vue';

const inertia = vi.hoisted(() => ({ demoMode: false, post: vi.fn() }));

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    // Imported here because the mock can load before this file's own imports.
    const { defineComponent, h, reactive } = await import('vue');

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
        Form: defineComponent({
            setup:
                (_, { slots }) =>
                () =>
                    h('form', slots.default?.({ errors: {}, processing: false })),
        }),
        useForm: (data: object) =>
            reactive({ ...data, errors: {}, processing: false, post: inertia.post }),
        usePage: () => ({ props: { status: null, demoMode: inertia.demoMode } }),
    };
});

function mountLogin(demoMode: boolean) {
    inertia.demoMode = demoMode;
    inertia.post.mockClear();

    const Wrapper = defineComponent({
        render: () => h(VApp, () => h(Login, { canResetPassword: true, turnstileSiteKey: null })),
    });

    return mount(Wrapper, { global: { plugins: [vuetify] } });
}

describe('Login', () => {
    it('offers registration, and no demo, normally', () => {
        const wrapper = mountLogin(false);

        expect(wrapper.text()).not.toContain('Try the demo');
        expect(wrapper.find('a[href="/register"]').exists()).toBe(true);
    });

    it('offers the demo, and no registration, in demo mode', async () => {
        const wrapper = mountLogin(true);

        expect(wrapper.text()).toContain('Try the demo');
        expect(wrapper.find('a[href="/register"]').exists()).toBe(false);

        const button = () =>
            wrapper
                .findAll('button')
                .find(
                    (item) => item.text().includes('demo user') || item.text().includes('Checking'),
                )!;

        expect(button().text()).toBe('Checking your browser…');
        expect(button().attributes('disabled')).toBeDefined();

        wrapper.findComponent(TurnstileWidget).vm.$emit('update:modelValue', 'a-token');
        await wrapper.vm.$nextTick();

        expect(button().text()).toBe('Sign in as the demo user');

        await button().trigger('click');

        expect(inertia.post).toHaveBeenCalledWith('/demo', expect.anything());
    });
});
