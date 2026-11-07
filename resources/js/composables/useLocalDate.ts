/**
 * Formats a calendar date (YYYY-MM-DD) for display. Dates are calendar
 * days with no time zone, so they are formatted in UTC to stop the
 * browser's own zone shifting them to the day before or after.
 */
export function formatLongDate(date: string): string {
    return new Intl.DateTimeFormat(undefined, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(`${date}T00:00:00Z`));
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
