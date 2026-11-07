import skipFormatting from '@vue/eslint-config-prettier/skip-formatting';
import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript';
import pluginVue from 'eslint-plugin-vue';

export default defineConfigWithVueTs(
    {
        name: 'app/files-to-lint',
        files: ['resources/js/**/*.{ts,vue}', '*.config.ts'],
    },
    {
        name: 'app/files-to-ignore',
        ignores: ['vendor/**', 'node_modules/**', 'public/**', 'bootstrap/ssr/**', 'storage/**'],
    },
    pluginVue.configs['flat/recommended'],
    vueTsConfigs.recommended,
    {
        name: 'app/pages',
        files: ['resources/js/pages/**/*.vue'],
        rules: {
            // Inertia page names map to routes, so single words like "Today" are expected.
            'vue/multi-word-component-names': 'off',
        },
    },
    {
        name: 'app/tests',
        files: ['resources/js/**/*.test.ts'],
        rules: {
            'vue/one-component-per-file': 'off',
        },
    },
    skipFormatting,
);
