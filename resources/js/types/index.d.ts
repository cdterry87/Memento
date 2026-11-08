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

/** A month's average mood score, or null for a month with no entries. */
export interface MonthAverage {
    month: number;
    average: number | null;
    count: number;
}

/** One day in the year-in-pixels grid. */
export interface MoodPixel {
    date: string;
    score: number;
    emoji: string;
    label: string;
}

/** A year's recap, shaped by RecapBuilder. */
export interface Recap {
    year: number;
    isYearToDate: boolean;
    daysInPeriod: number;
    checkIns: number;
    longestStreak: number;
    photos: number;
    averageScore: number | null;
    pixels: MoodPixel[];
    months: MonthAverage[];
    moodMix: (Mood & { count: number; share: number })[];
    topReasons: (Reason & { count: number })[];
    lifted: (Reason & { average: number })[];
    bestMonth: MonthAverage | null;
    goodMemories: Memory[];
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            auth: { user: CurrentUser | null };
            status: string | null;
        };
    }
}
