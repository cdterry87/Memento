import type { ThemePreference } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { computed, type WritableComputedRef } from 'vue';
import { useTheme } from 'vuetify';

/**
 * Where a signed-out visitor's theme choice is kept. Signed-in users have
 * theirs saved in `users.theme`. The inline script in app.blade.php reads
 * the same key before the first paint.
 */
export const THEME_STORAGE_KEY = 'memento.theme';

/**
 * The theme to start with, read from the page before the app mounts. The
 * server sets `data-theme` for a signed-in user's saved choice, and the
 * inline script sets it from local storage for visitors.
 */
export function readInitialTheme(): ThemePreference {
    const theme = document.documentElement.dataset.theme;

    return theme === 'light' || theme === 'dark' ? theme : 'system';
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
    const page = usePage();

    const preference = computed<ThemePreference>({
        get: () => (theme.isSystem.value ? 'system' : (theme.global.name.value as ThemePreference)),
        set: (value) => {
            theme.change(value);
            applyDocumentTheme(value);
            storeTheme(value);

            if (page.props.auth.user) {
                router.patch(
                    '/account/preferences',
                    { theme: value },
                    { preserveScroll: true, preserveState: true },
                );
            }
        },
    });

    return { preference };
}
