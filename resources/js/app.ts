import { createInertiaApp } from '@inertiajs/vue3';

const appName = import.meta.env.VITE_APP_NAME || 'Memento';

createInertiaApp({
    pages: './pages',
    title: (title) => (title ? `${title} · ${appName}` : appName),
    progress: {
        color: '#6c5ce7',
    },
});
