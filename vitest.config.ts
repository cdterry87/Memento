import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';
import vuetify from 'vite-plugin-vuetify';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    plugins: [vue(), vuetify({ autoImport: true })],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        environment: 'jsdom',
        include: ['resources/js/**/*.test.ts'],
        setupFiles: ['resources/js/tests/setup.ts'],
        server: {
            deps: {
                inline: ['vuetify'],
            },
        },
    },
});
