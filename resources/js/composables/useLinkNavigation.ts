import { router } from '@inertiajs/vue3';

type LinkEvent = MouseEvent | KeyboardEvent;

/**
 * Turns a click on a Vuetify button or list item with an `href` into an
 * Inertia visit. The element still renders a real link, so opening it in a
 * new tab with a modifier key or middle click keeps working.
 */
export function useLinkNavigation(): { navigate: (event: LinkEvent, href: string) => void } {
    function navigate(event: LinkEvent, href: string): void {
        const opensElsewhere =
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey ||
            (event instanceof MouseEvent && event.button !== 0);

        if (opensElsewhere) {
            return;
        }

        event.preventDefault();
        router.visit(href);
    }

    return { navigate };
}
