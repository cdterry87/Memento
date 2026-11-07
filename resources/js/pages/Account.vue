<script setup lang="ts">
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

defineProps<{
    timezones: string[];
}>();

const page = usePage();
const user = computed(() => page.props.auth.user!);

const statusMessages: Record<string, string> = {
    'profile-information-updated': 'Your profile is saved.',
    'password-updated': 'Your password is changed.',
    'verification-link-sent': 'A verification link is on its way.',
};

const snackbar = ref(false);
const snackbarText = ref('');

watch(
    () => page.props.status,
    (status) => {
        if (status && statusMessages[status]) {
            snackbarText.value = statusMessages[status];
            snackbar.value = true;
        }
    },
    { immediate: true },
);

function saveShowQuotes(showQuotes: boolean | null): void {
    router.patch(
        '/account/preferences',
        { show_quotes: !!showQuotes },
        {
            preserveScroll: true,
            onSuccess: () => {
                snackbarText.value = showQuotes
                    ? 'Quotes are back on Today.'
                    : 'Quotes are hidden.';
                snackbar.value = true;
            },
        },
    );
}

function saveTimezone(timezone: string | null): void {
    if (!timezone || timezone === user.value.timezone) {
        return;
    }

    router.patch(
        '/account/preferences',
        { timezone },
        {
            preserveScroll: true,
            onSuccess: () => {
                snackbarText.value = 'Your time zone is saved.';
                snackbar.value = true;
            },
        },
    );
}
</script>

<template>
    <Head title="Account" />

    <section class="d-flex flex-column ga-6">
        <h1 class="text-h4">Account</h1>

        <v-card class="pa-6">
            <h2 class="text-subtitle-1 font-weight-bold mb-4">Profile</h2>

            <Form
                v-slot="{ errors, processing }"
                action="/user/profile-information"
                method="put"
                error-bag="updateProfileInformation"
                :options="{ preserveScroll: true }"
            >
                <v-text-field
                    name="name"
                    label="Name"
                    autocomplete="name"
                    :model-value="user.name"
                    :error-messages="errors.name"
                />

                <v-text-field
                    name="email"
                    type="email"
                    label="Email"
                    autocomplete="email"
                    :model-value="user.email"
                    :error-messages="errors.email"
                />

                <p class="text-body-2 text-medium-emphasis mb-4">
                    If you change your email, we'll ask you to confirm the new address before you
                    carry on.
                </p>

                <v-btn type="submit" color="primary" :loading="processing">Save profile</v-btn>
            </Form>
        </v-card>

        <v-card class="pa-6">
            <h2 class="text-subtitle-1 font-weight-bold mb-4">Password</h2>

            <Form
                v-slot="{ errors, processing }"
                action="/user/password"
                method="put"
                error-bag="updatePassword"
                :options="{ preserveScroll: true }"
                reset-on-success
                :reset-on-error="['current_password', 'password', 'password_confirmation']"
            >
                <v-text-field
                    name="current_password"
                    type="password"
                    label="Current password"
                    autocomplete="current-password"
                    :error-messages="errors.current_password"
                />

                <v-text-field
                    name="password"
                    type="password"
                    label="New password"
                    autocomplete="new-password"
                    :error-messages="errors.password"
                />

                <v-text-field
                    name="password_confirmation"
                    type="password"
                    label="Confirm new password"
                    autocomplete="new-password"
                />

                <v-btn type="submit" color="primary" :loading="processing">Change password</v-btn>
            </Form>
        </v-card>

        <v-card class="pa-6">
            <h2 class="text-subtitle-1 font-weight-bold mb-1">Appearance</h2>
            <p class="text-body-2 text-medium-emphasis mb-4">
                Follow your device, or always use light or dark.
            </p>

            <ThemeSwitcher />
        </v-card>

        <v-card class="pa-6">
            <h2 class="text-subtitle-1 font-weight-bold mb-1">Today screen</h2>
            <p class="text-body-2 text-medium-emphasis mb-2">
                A short quote to start the day, different every day.
            </p>

            <v-switch
                label="Show a daily quote"
                color="primary"
                inset
                hide-details
                :model-value="user.showQuotes"
                @update:model-value="saveShowQuotes"
            />
        </v-card>

        <v-card class="pa-6">
            <h2 class="text-subtitle-1 font-weight-bold mb-1">Time zone</h2>
            <p class="text-body-2 text-medium-emphasis mb-4">
                Decides when your day starts, and when reminder emails arrive.
            </p>

            <v-autocomplete
                label="Time zone"
                :items="timezones"
                :model-value="user.timezone"
                :error-messages="page.props.errors.timezone"
                auto-select-first
                @update:model-value="saveTimezone"
            />
        </v-card>

        <v-snackbar v-model="snackbar" :timeout="3000">{{ snackbarText }}</v-snackbar>
    </section>
</template>
