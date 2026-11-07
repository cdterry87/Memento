<script setup lang="ts">
import AuthCard from '@/components/AuthCard.vue';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <Head title="Forgot password" />

    <AuthCard
        title="Forgot your password?"
        subtitle="Enter your email and we'll send you a link to choose a new one."
    >
        <v-alert v-if="page.props.status" type="success" class="mb-6">
            {{ page.props.status }}
        </v-alert>

        <Form v-slot="{ errors, processing }" action="/forgot-password" method="post">
            <v-text-field
                name="email"
                type="email"
                label="Email"
                autocomplete="email"
                autofocus
                required
                :error-messages="errors.email"
            />

            <v-btn type="submit" color="primary" size="large" block :loading="processing">
                Email reset link
            </v-btn>
        </Form>

        <template #footer>
            <Link href="/login" class="text-primary">Back to sign in</Link>
        </template>
    </AuthCard>
</template>
