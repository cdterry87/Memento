<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        {{-- Applies a saved light or dark choice before the first paint, so the page doesn't flash the device theme. Keep the key in step with useAppTheme.ts. --}}
        <script>
            try {
                const theme = localStorage.getItem('memento.theme');

                if (theme === 'light' || theme === 'dark') {
                    document.documentElement.dataset.theme = theme;
                }
            } catch {}
        </script>

        <title inertia>{{ config('app.name', 'Memento') }}</title>

        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
