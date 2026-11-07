/**
 * Calendar dates (YYYY-MM-DD) have no time zone, so every formatter here
 * works in UTC to stop the browser's own zone shifting a date to the day
 * before or after.
 */
function formatCalendarDate(date: string, options: Intl.DateTimeFormatOptions): string {
    return new Intl.DateTimeFormat(undefined, { ...options, timeZone: 'UTC' }).format(
        new Date(`${date}T00:00:00Z`),
    );
}

/** "Friday, November 6", or "Friday, November 6, 2025" with the year. */
export function formatLongDate(date: string, { withYear = false } = {}): string {
    return formatCalendarDate(date, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: withYear ? 'numeric' : undefined,
    });
}

/** "November 2026", for month headings. */
export function formatMonthYear(date: string): string {
    return formatCalendarDate(date, { month: 'long', year: 'numeric' });
}

/** "Fri", for a compact date badge. */
export function formatShortWeekday(date: string): string {
    return formatCalendarDate(date, { weekday: 'short' });
}

/** The day of the month, such as 6. */
export function dayOfMonth(date: string): number {
    return Number(date.slice(8, 10));
}

/** "2026-11", for grouping dates by month. */
export function monthKey(date: string): string {
    return date.slice(0, 7);
}

/** A greeting for the current hour in the given time zone. */
export function greetingFor(timezone: string, now: Date = new Date()): string {
    const hour = Number(
        new Intl.DateTimeFormat('en-GB', {
            hour: 'numeric',
            hourCycle: 'h23',
            timeZone: timezone,
        }).format(now),
    );

    if (hour >= 5 && hour < 12) {
        return 'Good morning';
    }

    if (hour >= 12 && hour < 18) {
        return 'Good afternoon';
    }

    return 'Good evening';
}

/**
 * Counts characters the way the server does, by Unicode code point. Most
 * emoji are two UTF-16 units, so `text.length` would count them twice.
 */
export function characterCount(text: string): number {
    return [...text].length;
}
