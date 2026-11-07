import '@inertiajs/core';

export type ThemePreference = 'system' | 'light' | 'dark';

/** The signed-in user, shaped by CurrentUserResource. */
export interface CurrentUser {
    name: string;
    email: string;
    emailVerified: boolean;
    timezone: string;
    theme: ThemePreference;
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            auth: { user: CurrentUser | null };
            status: string | null;
        };
    }
}
