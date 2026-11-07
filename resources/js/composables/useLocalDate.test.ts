import { describe, expect, it } from 'vitest';
import {
    characterCount,
    dayOfMonth,
    formatLongDate,
    formatMonthYear,
    formatShortWeekday,
    greetingFor,
    monthKey,
    yearsAgoLabel,
    yearsBetween,
} from './useLocalDate';

describe('formatLongDate', () => {
    it('formats the calendar date without shifting it to another day', () => {
        expect(formatLongDate('2026-11-06')).toContain('November 6');
        expect(formatLongDate('2026-11-06')).toContain('Friday');
        expect(formatLongDate('2026-11-06')).not.toContain('2026');
    });

    it('adds the year when asked', () => {
        expect(formatLongDate('2025-01-01', { withYear: true })).toContain('2025');
    });
});

describe('timeline date helpers', () => {
    it('formats the first and last day of a month as that month', () => {
        expect(formatMonthYear('2026-11-01')).toBe('November 2026');
        expect(formatMonthYear('2026-10-31')).toBe('October 2026');
    });

    it('formats the weekday and day of the month', () => {
        expect(formatShortWeekday('2026-11-06')).toBe('Fri');
        expect(dayOfMonth('2026-11-06')).toBe(6);
    });

    it('groups dates by calendar month', () => {
        expect(monthKey('2026-11-06')).toBe('2026-11');
    });
});

describe('greetingFor', () => {
    const noonUtc = new Date('2026-11-07T12:00:00Z');

    it("greets by the hour in the user's time zone", () => {
        expect(greetingFor('UTC', noonUtc)).toBe('Good afternoon');
        expect(greetingFor('America/Chicago', noonUtc)).toBe('Good morning');
        expect(greetingFor('Asia/Tokyo', noonUtc)).toBe('Good evening');
    });
});

describe('characterCount', () => {
    it('counts each emoji as one character, like the server', () => {
        expect('🎉🎉'.length).toBe(4);
        expect(characterCount('🎉🎉')).toBe(2);
        expect(characterCount('Coffee with Sam 🎉')).toBe(17);
    });
});

describe('yearsBetween and yearsAgoLabel', () => {
    it('counts calendar years, including a leap day shown on February 28', () => {
        expect(yearsBetween('2025-11-07', '2026-11-07')).toBe(1);
        expect(yearsBetween('2024-02-29', '2027-02-28')).toBe(3);
    });

    it('says "1 year ago" or "N years ago"', () => {
        expect(yearsAgoLabel(1)).toBe('1 year ago');
        expect(yearsAgoLabel(4)).toBe('4 years ago');
    });
});
