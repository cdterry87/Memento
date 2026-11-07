import { describe, expect, it } from 'vitest';
import { characterCount, formatLongDate, greetingFor } from './useLocalDate';

describe('formatLongDate', () => {
    it('formats the calendar date without shifting it to another day', () => {
        expect(formatLongDate('2026-11-06')).toContain('November 6');
        expect(formatLongDate('2026-11-06')).toContain('Friday');
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
