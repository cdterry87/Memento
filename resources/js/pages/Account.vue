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

/** Saves one or more preferences, then confirms with a short message. */
function savePreferences(preferences: Record<string, string | boolean>, message: string): void {
    router.patch('/account/preferences', preferences, {
        preserveScroll: true,
        onSuccess: () => {
            snackbarText.value = message;
            snackbar.value = true;
        },
    });
}

function saveShowQuotes(showQuotes: boolean | null): void {
    savePreferences(
        { show_quotes: !!showQuotes },
        showQuotes ? 'Quotes are back on Today.' : 'Quotes are hidden.',
    );
}

function saveTimezone(timezone: string | null): void {
    if (!timezone || timezone === user.value.timezone) {
        return;
    }

    savePreferences({ timezone }, 'Your time zone is saved.');
}

function saveReminderEmails(on: boolean | null): void {
    savePreferences({ reminder_emails: !!on }, on ? 'Reminders are on.' : 'Reminders are off.');
}

function saveReminderTime(time: string | null): void {
    if (!time || time === user.value.reminderTime) {
        return;
    }

    savePreferences({ reminder_time: time }, 'Your reminder time is saved.');
}

function saveOnThisDayEmails(on: boolean | null): void {
    savePreferences(
        { on_this_day_emails: !!on },
        on ? 'On this day emails are on.' : 'On this day emails are off.',
    );
}

/** Every whole hour, labelled the way the browser shows times, such as "8 PM". */
const reminderHours = Array.from({ length: 24 }, (_, hour) => ({
    value: `${String(hour).padStart(2, '0')}:00`,
    title: new Intl.DateTimeFormat(undefined, { hour: 'numeric', timeZone: 'UTC' }).format(
        new Date(Date.UTC(2000, 0, 1, hour)),
    ),
}));
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

        <v-card class="pa-6">
            <h2 class="text-subtitle-1 font-weight-bold mb-1">Emails</h2>
            <p class="text-body-2 text-medium-emphasis mb-2">
                Both are off unless you turn them on. Emails never include your entries, only a link
                back here.
            </p>

            <v-alert v-if="!user.emailVerified" type="info" variant="tonal" class="my-4">
                Confirm your email address to get emails.
            </v-alert>

            <v-switch
                label="Daily check-in reminder"
                color="primary"
                inset
                hide-details
                :disabled="!user.emailVerified"
                :model-value="user.reminderEmails"
                @update:model-value="saveReminderEmails"
            />
            <p class="text-body-2 text-medium-emphasis mb-4">
                Only on days you haven't checked in yet.
            </p>

            <v-select
                v-if="user.reminderEmails"
                label="Reminder time"
                :items="reminderHours"
                :model-value="user.reminderTime"
                :error-messages="page.props.errors.reminder_time"
                :disabled="!user.emailVerified"
                class="mb-2"
                @update:model-value="saveReminderTime"
            />

            <v-switch
                label="On this day"
                color="primary"
                inset
                hide-details
                :disabled="!user.emailVerified"
                :model-value="user.onThisDayEmails"
                @update:model-value="saveOnThisDayEmails"
            />
            <p class="text-body-2 text-medium-emphasis">
                A note at 8 AM when you have memories from this date in earlier years.
            </p>
        </v-card>

        <v-snackbar v-model="snackbar" :timeout="3000">{{ snackbarText }}</v-snackbar>
    </section>
</template>
