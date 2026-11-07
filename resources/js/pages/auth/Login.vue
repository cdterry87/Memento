<script setup lang="ts">
import AuthCard from '@/components/AuthCard.vue';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';

defineProps<{
    canResetPassword: boolean;
}>();

const page = usePage();
</script>

<template>
    <Head title="Sign in" />

    <AuthCard title="Welcome back" subtitle="Sign in to your journal.">
        <v-alert v-if="page.props.status" type="success" class="mb-6">
            {{ page.props.status }}
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

        <template #footer>
            New here?
            <Link href="/register" class="text-primary">Create an account</Link>
        </template>
    </AuthCard>
</template>
