<script setup lang="ts">
import AuthCard from '@/components/AuthCard.vue';
import { Form, Head, router, usePage } from '@inertiajs/vue3';

const page = usePage();

function signOut(): void {
    router.post('/logout');
}
</script>

<template>
    <Head title="Verify your email" />

    <AuthCard
        title="Check your inbox"
        subtitle="We've sent a link to confirm your email address. Open it to start your journal."
    >
        <v-alert v-if="page.props.status === 'verification-link-sent'" type="success" class="mb-6">
            A new link is on its way.
        </v-alert>

        <Form v-slot="{ processing }" action="/email/verification-notification" method="post">
            <v-btn type="submit" color="primary" size="large" block :loading="processing">
                Send the link again
            </v-btn>
        </Form>

        <template #footer>
            <v-btn variant="text" color="primary" @click="signOut">Sign out</v-btn>
        </template>
    </AuthCard>
</template>
