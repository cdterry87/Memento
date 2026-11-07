import { readInitialTheme } from '@/composables/useAppTheme';
import { createVuetify, type ThemeDefinition } from 'vuetify';
import { aliases, mdi } from 'vuetify/iconsets/mdi-svg';
import 'vuetify/styles';

/**
 * Palette from docs/spec.md, section 7.2, checked against WCAG 2.2 AA.
 *
 * - Body text, primary, and error reach at least 4.5:1 on the background,
 *   surface, and gradient tint, and so does the text on each filled colour.
 * - Primary is a little deeper than the spec's first #6c5ce7 so that it
 *   passes on the tint.
 * - The light secondary is too pale for white text or for text on its own,
 *   so it is only used as a fill, with dark text on top.
 * - Medium-emphasis text uses 70% opacity, which still reaches 4.5:1.
 * - `background-tint` is the far end of the page gradient.
 */
const light: ThemeDefinition = {
    dark: false,
    colors: {
        background: '#fbfaff',
        'background-tint': '#efeaff',
        surface: '#ffffff',
        primary: '#6252e0',
        secondary: '#48bebe',
        error: '#c03535',
        'on-background': '#2b2a3d',
        'on-surface': '#2b2a3d',
        'on-primary': '#ffffff',
        'on-secondary': '#2b2a3d',
        'on-error': '#ffffff',
        'mood-1': '#8fa8d8',
        'mood-2': '#a9b8e8',
        'mood-3': '#d9d4ee',
        'mood-4': '#ffd27d',
        'mood-5': '#ffa96b',
        'on-mood-1': '#2b2a3d',
        'on-mood-2': '#2b2a3d',
        'on-mood-3': '#2b2a3d',
        'on-mood-4': '#2b2a3d',
        'on-mood-5': '#2b2a3d',
    },
    variables: {
        'medium-emphasis-opacity': 0.7,
    },
};

const dark: ThemeDefinition = {
    dark: true,
    colors: {
        background: '#17162b',
        'background-tint': '#221c42',
        surface: '#232240',
        primary: '#a99ffa',
        secondary: '#6fd6d0',
        error: '#ff8a8a',
        'on-background': '#f3f1ff',
        'on-surface': '#f3f1ff',
        'on-primary': '#17162b',
        'on-secondary': '#17162b',
        'on-error': '#17162b',
        'mood-1': '#6d86ba',
        'mood-2': '#8695c9',
        'mood-3': '#a9a4c6',
        'mood-4': '#e6b95f',
        'mood-5': '#f09252',
        'on-mood-1': '#17162b',
        'on-mood-2': '#17162b',
        'on-mood-3': '#17162b',
        'on-mood-4': '#17162b',
        'on-mood-5': '#17162b',
    },
    variables: {
        'medium-emphasis-opacity': 0.7,
    },
};

const fieldDefaults = { variant: 'solo-filled', flat: true, rounded: 'lg' } as const;

export default createVuetify({
    theme: {
        defaultTheme: readInitialTheme(),
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
        VChip: { rounded: 'pill', variant: 'tonal' },
        VAlert: { rounded: 'xl', variant: 'tonal' },
        VTextField: fieldDefaults,
        VTextarea: fieldDefaults,
        VSelect: fieldDefaults,
        VAutocomplete: fieldDefaults,
        VSwitch: { color: 'primary', inset: true, hideDetails: 'auto' },
        VBtnToggle: { rounded: 'pill', color: 'primary' },
        VBottomNavigation: { elevation: 0 },
        VDialog: { maxWidth: 560 },
        VSnackbar: { rounded: 'pill' },
        VProgressLinear: { rounded: true, color: 'primary' },
    },
});
