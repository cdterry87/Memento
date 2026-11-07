import { createVuetify, type ThemeDefinition } from 'vuetify';
import { aliases, mdi } from 'vuetify/iconsets/mdi-svg';
import 'vuetify/styles';

/**
 * Starting palette from docs/spec.md, section 7.2. Contrast still needs
 * checking against WCAG 2.2 AA before these values are final.
 */
const light: ThemeDefinition = {
    dark: false,
    colors: {
        background: '#fbfaff',
        surface: '#ffffff',
        primary: '#6c5ce7',
        secondary: '#48bebe',
        error: '#d64545',
        'on-background': '#2b2a3d',
        'on-surface': '#2b2a3d',
        'mood-1': '#8fa8d8',
        'mood-2': '#a9b8e8',
        'mood-3': '#d9d4ee',
        'mood-4': '#ffd27d',
        'mood-5': '#ffa96b',
    },
};

const dark: ThemeDefinition = {
    dark: true,
    colors: {
        background: '#17162b',
        surface: '#232240',
        primary: '#a99ffa',
        secondary: '#6fd6d0',
        error: '#ff8a8a',
        'on-background': '#f3f1ff',
        'on-surface': '#f3f1ff',
        'mood-1': '#6d86ba',
        'mood-2': '#8695c9',
        'mood-3': '#a9a4c6',
        'mood-4': '#e6b95f',
        'mood-5': '#f09252',
    },
};

export default createVuetify({
    theme: {
        defaultTheme: 'system',
        themes: { light, dark },
    },
    icons: {
        defaultSet: 'mdi',
        aliases,
        sets: { mdi },
    },
    defaults: {
        VBtn: { rounded: 'pill', elevation: 0 },
        VCard: { rounded: 'xl', elevation: 0 },
        VTextField: { variant: 'solo-filled', flat: true, rounded: 'lg' },
        VTextarea: { variant: 'solo-filled', flat: true, rounded: 'lg' },
    },
});
