<script setup lang="ts">
/**
 * Confirms turning off one kind of email, from the link in that email.
 */
import AuthCard from '@/components/AuthCard.vue';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
    emailLabel: string;
    isSubscribed: boolean;
    confirmUrl: string;
}>();
</script>

<template>
    <Head title="Email preferences" />

    <AuthCard
        v-if="isSubscribed"
        title="Turn off these emails?"
        :subtitle="`You'll stop getting ${emailLabel}. You can turn them back on in your account at any time.`"
    >
        <Form v-slot="{ processing }" :action="confirmUrl" method="post">
            <v-btn type="submit" color="primary" size="large" block :loading="processing">
                Turn them off
            </v-btn>
        </Form>
    </AuthCard>

    <AuthCard
        v-else
        title="You're unsubscribed"
        :subtitle="`You won't get ${emailLabel} any more. You can turn them back on in your account at any time.`"
    >
        <v-btn color="primary" variant="tonal" size="large" block href="/today">
            Open Memento
        </v-btn>
    </AuthCard>
</template>
