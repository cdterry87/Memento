<script setup lang="ts">
import AuthCard from '@/components/AuthCard.vue';
import TurnstileWidget from '@/components/TurnstileWidget.vue';
import { Form, Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    canResetPassword: boolean;
    turnstileSiteKey: string | null;
}>();

const page = usePage();

/** Signs in as the shared demo user, once Turnstile has checked this is a person. */
const demo = useForm({ turnstile: '' });
const demoCheck = ref<InstanceType<typeof TurnstileWidget> | null>(null);

function tryDemo(): void {
    demo.post('/demo', {
        // Each Turnstile token works once, so every attempt needs a new one.
        onFinish: () => demoCheck.value?.reset(),
    });
}
</script>

<template>
    <Head title="Sign in" />

    <AuthCard title="Welcome back" subtitle="Sign in to your journal.">
        <v-alert v-if="page.props.status" type="success" class="mb-6">
            {{ page.props.status }}
        </v-alert>

        <v-alert
            v-if="page.props.demoMode"
            type="info"
            variant="tonal"
            class="mb-6"
            title="Try the demo"
        >
            <p class="mb-3">
                Look around with two years of sample entries. The demo resets every night.
            </p>
            <TurnstileWidget
                ref="demoCheck"
                v-model="demo.turnstile"
                :site-key="turnstileSiteKey"
                action="demo-login"
                appearance="interaction-only"
            />
            <p v-if="demo.errors.turnstile" class="text-body-2 text-error mb-2" role="alert">
                {{ demo.errors.turnstile }}
            </p>
            <v-btn
                color="primary"
                block
                :loading="demo.processing"
                :disabled="!demo.turnstile"
                @click="tryDemo"
            >
                {{ demo.turnstile ? 'Sign in as the demo user' : 'Checking your browser…' }}
            </v-btn>
        </v-alert>

        <Form
            v-slot="{ errors, processing }"
            action="/login"
            method="post"
            :reset-on-error="['password']"
        >
            <v-text-field
                name="email"
                type="email"
                label="Email"
                autocomplete="email"
                autofocus
                required
                :error-messages="errors.email"
            />

            <v-text-field
                name="password"
                type="password"
                label="Password"
                autocomplete="current-password"
                required
                :error-messages="errors.password"
            />

            <div class="d-flex align-center justify-space-between flex-wrap mb-4">
                <v-checkbox name="remember" label="Remember me" hide-details density="compact" />

                <Link
                    v-if="canResetPassword"
                    href="/forgot-password"
                    class="text-body-2 text-primary"
                >
                    Forgot password?
                </Link>
            </div>

            <v-btn type="submit" color="primary" size="large" block :loading="processing">
                Sign in
            </v-btn>
        </Form>

        <template v-if="!page.props.demoMode" #footer>
            New here?
            <Link href="/register" class="text-primary">Create an account</Link>
        </template>
    </AuthCard>
</template>
