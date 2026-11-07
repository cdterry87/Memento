<script setup lang="ts">
import AuthCard from '@/components/AuthCard.vue';
import { Form, Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Confirm your password" />

    <AuthCard
        title="Confirm your password"
        subtitle="This part of your account needs your password before you continue."
    >
        <Form
            v-slot="{ errors, processing }"
            action="/user/confirm-password"
            method="post"
            :reset-on-error="['password']"
        >
            <v-text-field
                name="password"
                type="password"
                label="Password"
                autocomplete="current-password"
                autofocus
                required
                :error-messages="errors.password"
            />

            <v-btn type="submit" color="primary" size="large" block :loading="processing">
                Confirm
            </v-btn>
        </Form>
    </AuthCard>
</template>
