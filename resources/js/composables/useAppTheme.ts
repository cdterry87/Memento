import { computed, type WritableComputedRef } from 'vue';
import { useTheme } from 'vuetify';

export type ThemePreference = 'system' | 'light' | 'dark';

/**
 * Where the theme choice is kept until accounts exist. Milestone 2 moves it
 * to `users.theme` so that it follows the user across devices. The inline
 * script in app.blade.php reads the same key before the first paint.
 */
export const THEME_STORAGE_KEY = 'memento.theme';

const preferences: ThemePreference[] = ['system', 'light', 'dark'];

function isThemePreference(value: unknown): value is ThemePreference {
    return preferences.includes(value as ThemePreference);
}

export function readStoredTheme(): ThemePreference {
    try {
        const stored = window.localStorage.getItem(THEME_STORAGE_KEY);

        return isThemePreference(stored) ? stored : 'system';
    } catch {
        return 'system';
    }
}

function storeTheme(preference: ThemePreference): void {
    try {
        if (preference === 'system') {
            window.localStorage.removeItem(THEME_STORAGE_KEY);
        } else {
            window.localStorage.setItem(THEME_STORAGE_KEY, preference);
        }
    } catch {
        // Storage can be unavailable, for example in a private window. The
        // choice still applies for this visit.
    }
}

/**
 * Keeps the page background, set in app.css, in step with the Vuetify theme.
 */
function applyDocumentTheme(preference: ThemePreference): void {
    if (preference === 'system') {
        delete document.documentElement.dataset.theme;
    } else {
        document.documentElement.dataset.theme = preference;
    }
}

export function useAppTheme(): { preference: WritableComputedRef<ThemePreference> } {
    const theme = useTheme();

    const preference = computed<ThemePreference>({
        get: () => (theme.isSystem.value ? 'system' : (theme.global.name.value as ThemePreference)),
        set: (value) => {
            theme.change(value);
            storeTheme(value);
            applyDocumentTheme(value);
        },
    });

    return { preference };
}
