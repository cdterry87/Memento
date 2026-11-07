<script setup lang="ts">
import AuthCard from '@/components/AuthCard.vue';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
    token: string;
    email: string;
}>();
</script>

<template>
    <Head title="Choose a new password" />

    <AuthCard title="Choose a new password">
        <Form
            v-slot="{ errors, processing }"
            action="/reset-password"
            method="post"
            :reset-on-error="['password', 'password_confirmation']"
        >
            <input type="hidden" name="token" :value="token" />

            <v-text-field
                name="email"
                type="email"
                label="Email"
                autocomplete="email"
                :model-value="email"
                readonly
                :error-messages="errors.email"
            />

            <v-text-field
                name="password"
                type="password"
                label="New password"
                autocomplete="new-password"
                autofocus
                required
                :error-messages="errors.password"
            />

            <v-text-field
                name="password_confirmation"
                type="password"
                label="Confirm new password"
                autocomplete="new-password"
                required
            />

            <v-btn
                type="submit"
                color="primary"
                size="large"
                block
                class="mt-2"
                :loading="processing"
            >
                Save password
            </v-btn>
        </Form>
    </AuthCard>
</template>
