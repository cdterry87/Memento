import { ref, watch, type Ref } from 'vue';

const prefix = 'memento.draft.';

function read(key: string): string | null {
    try {
        return window.localStorage.getItem(prefix + key);
    } catch {
        return null;
    }
}

/**
 * Text that survives an accidental refresh. It is kept in this browser
 * only, and cleared once the entry is saved.
 */
export function useDraft(key: string, initial: string): { text: Ref<string>; clear: () => void } {
    const text = ref(read(key) ?? initial);

    watch(text, (value) => {
        try {
            if (value === initial) {
                window.localStorage.removeItem(prefix + key);
            } else {
                window.localStorage.setItem(prefix + key, value);
            }
        } catch {
            // Without storage the draft simply isn't kept.
        }
    });

    function clear(): void {
        try {
            window.localStorage.removeItem(prefix + key);
        } catch {
            // Nothing to clear.
        }
    }

    return { text, clear };
}
