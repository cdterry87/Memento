<script setup lang="ts">
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
import type { DataExportStatus } from '@/types';
import { mdiDownload } from '@mdi/js';
import { Form, Head, router, usePage, usePoll } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    timezones: string[];
    dataExport: DataExportStatus | null;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user!);

const statusMessages: Record<string, string> = {
    'profile-information-updated': 'Your profile is saved.',
    'password-updated': 'Your password is changed.',
    'verification-link-sent': 'A verification link is on its way.',
    'export-requested': "We're making your export. We'll email you when it's ready.",
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

const exporting = ref(false);

function requestExport(): void {
    router.post(
        '/account/export',
        {},
        {
            preserveScroll: true,
            onStart: () => (exporting.value = true),
            onFinish: () => (exporting.value = false),
        },
    );
}

/** Checks on an export in progress, so the download appears without a refresh. */
const exportPoll = usePoll(5000, { only: ['dataExport'] }, { autoStart: false });

watch(
    () => props.dataExport?.status,
    (status) => (status === 'pending' ? exportPoll.start() : exportPoll.stop()),
    { immediate: true },
);

const exportExpiresAt = computed(() =>
    props.dataExport?.expiresAt
        ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(
              new Date(props.dataExport.expiresAt),
          )
        : null,
);

const deleteDialog = ref(false);

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

        <v-alert v-if="user.isDemo" type="info" variant="tonal">
            This is the shared demo account, so its name, email, password, time zone, and emails
            can't be changed, and it can't be exported or deleted. Appearance and quotes are yours
            to try.
        </v-alert>

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
                    :disabled="user.isDemo"
                    name="name"
                    label="Name"
                    autocomplete="name"
                    :model-value="user.name"
                    :error-messages="errors.name"
                />

                <v-text-field
                    :disabled="user.isDemo"
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

                <v-btn type="submit" color="primary" :loading="processing" :disabled="user.isDemo"
                    >Save profile</v-btn
                >
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
                    :disabled="user.isDemo"
                    name="current_password"
                    type="password"
                    label="Current password"
                    autocomplete="current-password"
                    :error-messages="errors.current_password"
                />

                <v-text-field
                    :disabled="user.isDemo"
                    name="password"
                    type="password"
                    label="New password"
                    autocomplete="new-password"
                    :error-messages="errors.password"
                />

                <v-text-field
                    :disabled="user.isDemo"
                    name="password_confirmation"
                    type="password"
                    label="Confirm new password"
                    autocomplete="new-password"
                />

                <v-btn type="submit" color="primary" :loading="processing" :disabled="user.isDemo"
                    >Change password</v-btn
                >
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
                :disabled="user.isDemo"
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
                :disabled="!user.emailVerified || user.isDemo"
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
                :disabled="!user.emailVerified || user.isDemo"
                class="mb-2"
                @update:model-value="saveReminderTime"
            />

            <v-switch
                label="On this day"
                color="primary"
                inset
                hide-details
                :disabled="!user.emailVerified || user.isDemo"
                :model-value="user.onThisDayEmails"
                @update:model-value="saveOnThisDayEmails"
            />
            <p class="text-body-2 text-medium-emphasis">
                A note at 8 AM when you have memories from this date in earlier years.
            </p>
        </v-card>

        <v-card v-if="!user.isDemo" class="pa-6">
            <h2 class="text-subtitle-1 font-weight-bold mb-1">Your data</h2>
            <p class="text-body-2 text-medium-emphasis mb-4">
                Download a copy of everything you've kept: your entries as a JSON file, and your
                photos at full size, in one ZIP. We'll email you a link when it's ready. It works
                for 24 hours.
            </p>

            <v-alert
                v-if="dataExport?.status === 'pending'"
                type="info"
                variant="tonal"
                class="mb-4"
                role="status"
            >
                Your export is being made. This page updates when it's ready, and we'll email you
                too.
            </v-alert>

            <v-alert
                v-else-if="dataExport?.status === 'failed'"
                type="warning"
                variant="tonal"
                class="mb-4"
            >
                Your last export couldn't be made. Please try again.
            </v-alert>

            <div v-else-if="dataExport?.status === 'ready'" class="mb-4">
                <v-btn
                    color="primary"
                    :prepend-icon="mdiDownload"
                    :href="dataExport.downloadUrl ?? undefined"
                    download
                >
                    Download your export
                </v-btn>
                <p class="text-body-2 text-medium-emphasis mt-2">
                    The link works until {{ exportExpiresAt }}.
                </p>
            </div>

            <v-btn
                :color="dataExport?.status === 'ready' ? undefined : 'primary'"
                :variant="dataExport?.status === 'ready' ? 'tonal' : 'flat'"
                :loading="exporting"
                :disabled="dataExport?.status === 'pending'"
                @click="requestExport"
            >
                {{ dataExport?.status === 'ready' ? 'Make a new export' : 'Export my data' }}
            </v-btn>
            <p v-if="page.props.errors.export" class="text-body-2 text-error mt-2" role="alert">
                {{ page.props.errors.export }}
            </p>

            <v-divider class="my-6" />

            <h3 class="text-subtitle-1 font-weight-bold mb-1">Delete account</h3>
            <p class="text-body-2 text-medium-emphasis mb-4">
                Deletes your account and every entry and photo in it, straight away. This can't be
                undone, so you may want to export your data first.
            </p>

            <v-btn color="error" variant="tonal" @click="deleteDialog = true">
                Delete my account
            </v-btn>

            <v-dialog v-model="deleteDialog" max-width="480">
                <v-card>
                    <Form
                        v-slot="{ errors, processing }"
                        action="/account"
                        method="delete"
                        error-bag="deleteAccount"
                        :reset-on-error="['password']"
                    >
                        <v-card-title class="text-h6 pt-5 px-6">Delete your account?</v-card-title>
                        <v-card-text class="px-6">
                            <p class="mb-4">
                                Your account, entries, and photos will be deleted for good. Enter
                                your password to confirm.
                            </p>
                            <v-text-field
                                name="password"
                                type="password"
                                label="Password"
                                autocomplete="current-password"
                                :error-messages="errors.password"
                                autofocus
                            />
                        </v-card-text>
                        <v-card-actions class="px-6 pb-5">
                            <v-spacer />
                            <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                            <v-btn type="submit" color="error" variant="flat" :loading="processing">
                                Delete my account
                            </v-btn>
                        </v-card-actions>
                    </Form>
                </v-card>
            </v-dialog>
        </v-card>

        <v-snackbar v-model="snackbar" :timeout="3000">{{ snackbarText }}</v-snackbar>
    </section>
</template>
