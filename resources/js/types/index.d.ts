import '@inertiajs/core';

export type ThemePreference = 'system' | 'light' | 'dark';

/** The signed-in user, shaped by CurrentUserResource. */
export interface CurrentUser {
    name: string;
    email: string;
    emailVerified: boolean;
    timezone: string;
    theme: ThemePreference;
    showQuotes: boolean;
}

/** A pickable option in the check-in, shaped by MoodResource and ReasonResource. */
export interface EmojiOption {
    slug: string;
    label: string;
    emoji: string;
}

export interface Mood extends EmojiOption {
    score: number;
}

export type Reason = EmojiOption;

/** A photo on a check-in, shaped by PhotoResource. URLs go through the authorizing media route. */
export interface Photo {
    id: string;
    url: string;
    thumbnailUrl: string;
    width: number;
    height: number;
}

/** One day's check-in, shaped by MemoryResource. */
export interface Memory {
    date: string;
    mood: Mood;
    reasons: Reason[];
    body: string | null;
    photos: Photo[];
}

/** The quote of the day, shaped by QuoteResource. */
export interface Quote {
    text: string;
    author: string;
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            auth: { user: CurrentUser | null };
            status: string | null;
        };
    }
}
