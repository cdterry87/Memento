<script setup lang="ts">
/**
 * What Memento stores, why, and who else sees it (spec section 6.7).
 */
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Privacy Policy" />

    <article class="privacy py-8">
        <Link href="/" class="d-block font-logo text-h4 text-primary text-decoration-none mb-6">
            Memento
        </Link>

        <v-card class="pa-6 pa-sm-8">
            <h1 class="text-h4 mb-1">Privacy Policy</h1>
            <p class="text-body-2 text-medium-emphasis mb-6">Effective November 7, 2026</p>

            <p class="mb-6">
                Memento is a private journal. This policy explains what it stores, why, and who else
                sees it. By using Memento, you agree to it.
            </p>

            <section>
                <h2 class="text-h6 mb-2">1. Information we collect</h2>
                <ul>
                    <li>
                        <strong>Account details:</strong> your name, email address, password (stored
                        only as a secure hash), time zone, and settings such as your theme and email
                        choices.
                    </li>
                    <li>
                        <strong>Your journal:</strong> the moods, reasons, messages, and photos you
                        add. Photos are resized and stripped of their hidden details, including
                        location, before they are stored.
                    </li>
                    <li>
                        <strong>Technical information:</strong> your IP address and browser details,
                        used to limit repeated sign-in attempts, check forms for bots, and keep
                        server logs.
                    </li>
                </ul>
            </section>

            <section>
                <h2 class="text-h6 mb-2">2. How we use your information</h2>
                <p>
                    Only to run Memento: to show your entries, timeline, and yearly recap, to send
                    the emails you ask for, and to protect the app from abuse. There are no
                    analytics, advertising, or marketing emails.
                </p>
                <p>
                    Reminder and "on this day" emails are off unless you turn them on. Like every
                    email Memento sends, they never include your entries, moods, or photos, only a
                    link back to the app.
                </p>
            </section>

            <section>
                <h2 class="text-h6 mb-2">3. Information sharing</h2>
                <p>
                    Your information is never sold or traded. It is shared only with the services
                    that run Memento, or when the law requires it:
                </p>
                <ul>
                    <li>
                        <strong>Cloudflare Turnstile</strong> checks the registration form and the
                        demo sign-in for bots, and receives your IP address and browser signals to
                        do so.
                    </li>
                    <li><strong>Cloudflare R2</strong> stores your photos privately.</li>
                    <li>
                        <strong>An email delivery service</strong> sends account emails, such as
                        address confirmations, password resets, and export links, and the emails you
                        turn on. It receives your email address to do so.
                    </li>
                    <li>
                        <strong>The hosting provider</strong> stores the application and its
                        database.
                    </li>
                </ul>
            </section>

            <section>
                <h2 class="text-h6 mb-2">4. Cookies and local storage</h2>
                <p>
                    Memento uses a session cookie and a security token cookie to keep you signed in
                    and protect forms, and a "remember me" cookie only if you choose it. Your
                    browser's local storage keeps your theme choice and any unsaved message, so a
                    refresh doesn't lose it. Cloudflare Turnstile may set its own cookies on the
                    pages it protects. There are no tracking cookies.
                </p>
            </section>

            <section>
                <h2 class="text-h6 mb-2">5. Security and retention</h2>
                <p>
                    Passwords are hashed, connections are encrypted, photos are only reachable
                    through short-lived private links, and sign-in attempts are rate limited. No
                    method of storage or transfer is completely secure, however.
                </p>
                <p>
                    Your information is kept until you delete it. Deleting an entry removes it and
                    its photos.
                </p>
                <p>
                    From your Account page you can download a copy of everything, as a ZIP of your
                    entries and photos. It is emailed to you as a link that works for 24 hours,
                    after which the file is deleted. You can also delete your account there, which
                    removes it and all of its entries and photos straight away.
                </p>
                <p>The shared demo account is reset every night.</p>
            </section>

            <section>
                <h2 class="text-h6 mb-2">6. Third-party links</h2>
                <p>
                    Memento links to a few other websites. We are not responsible for their content
                    or privacy practices.
                </p>
            </section>

            <section>
                <h2 class="text-h6 mb-2">7. Children's privacy</h2>
                <p>
                    Memento is not intended for children under 13, and we do not knowingly collect
                    information from them.
                </p>
            </section>

            <section>
                <h2 class="text-h6 mb-2">8. Changes to this policy</h2>
                <p>This policy may change. The effective date above shows when it last did.</p>
            </section>

            <v-btn variant="tonal" color="primary" class="mt-4" href="/">Back to Memento</v-btn>
        </v-card>
    </article>
</template>

<style scoped>
.font-logo {
    font-size: 2rem;
}

.privacy {
    max-width: 720px;
    margin-inline: auto;
}

.privacy section + section {
    margin-top: 24px;
}

.privacy section p + p {
    margin-top: 8px;
}

.privacy ul {
    padding-inline-start: 20px;
}

.privacy li + li {
    margin-top: 6px;
}
</style>
