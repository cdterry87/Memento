<script setup lang="ts">
import AuthCard from '@/components/AuthCard.vue';
import TurnstileWidget from '@/components/TurnstileWidget.vue';
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    turnstileSiteKey: string | null;
}>();

const turnstileToken = ref('');
const turnstile = ref<InstanceType<typeof TurnstileWidget> | null>(null);

/** Sent with the form so reminders and "today" match the user's own clock. */
const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
</script>

<template>
    <Head title="Create an account" />

    <AuthCard title="Start your journal" subtitle="It's private, and only takes a minute.">
        <Form
            v-slot="{ errors, processing }"
            action="/register"
            method="post"
            :reset-on-error="['password', 'password_confirmation']"
            @finish="turnstile?.reset()"
        >
            <input type="hidden" name="timezone" :value="timezone" />
            <input type="hidden" name="turnstile" :value="turnstileToken" />

            <v-text-field
                name="name"
                label="Name"
                autocomplete="name"
                autofocus
                required
                :error-messages="errors.name"
            />

            <v-text-field
                name="email"
                type="email"
                label="Email"
                autocomplete="email"
                required
                :error-messages="errors.email"
            />

            <v-text-field
                name="password"
                type="password"
                label="Password"
                autocomplete="new-password"
                required
                :error-messages="errors.password"
            />

            <v-text-field
                name="password_confirmation"
                type="password"
                label="Confirm password"
                autocomplete="new-password"
                required
            />

            <TurnstileWidget
                ref="turnstile"
                v-model="turnstileToken"
                :site-key="turnstileSiteKey"
                action="register"
                class="mb-2"
            />
            <p v-if="errors.turnstile" class="text-body-2 text-error mb-2" role="alert">
                {{ errors.turnstile }}
            </p>

            <v-btn
                type="submit"
                color="primary"
                size="large"
                block
                class="mt-2"
                :loading="processing"
            >
                Create account
            </v-btn>
        </Form>

        <template #footer>
            Already have an account?
            <Link href="/login" class="text-primary">Sign in</Link>
        </template>
    </AuthCard>
</template>
