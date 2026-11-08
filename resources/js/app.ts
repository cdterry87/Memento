import '../css/app.css';
import AppLayout from '@/layouts/AppLayout.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import vuetify from '@/plugins/vuetify';
import { createInertiaApp } from '@inertiajs/vue3';

const appName = import.meta.env.VITE_APP_NAME || 'Memento';

/** Pages shown to signed-out visitors. Every other page uses the app layout. */
const isGuestPage = (name: string): boolean =>
    ['Welcome', 'Unsubscribe', 'Privacy'].includes(name) || name.startsWith('auth/');

createInertiaApp({
    pages: './pages',
    layout: (name) => (isGuestPage(name) ? GuestLayout : AppLayout),
    title: (title) => (title ? `${title} · ${appName}` : appName),
    progress: {
        color: '#6252e0',
    },
    withApp: (app) => {
        app.use(vuetify);
    },
});
