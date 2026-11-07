@php
    $savedTheme = auth()->user()?->theme->value;
@endphp
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    @if (in_array($savedTheme, ['light', 'dark'], true)) data-theme="{{ $savedTheme }}" @endif
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        @guest
            {{-- Signed-out visitors keep their theme in the browser. Applying it here, before the first paint, avoids a flash of the device theme. Keep the key in step with useAppTheme.ts. --}}
            <script>
                try {
                    const theme = localStorage.getItem('memento.theme');

                    if (theme === 'light' || theme === 'dark') {
                        document.documentElement.dataset.theme = theme;
                    }
                } catch {}
            </script>
        @endguest

        <title inertia>{{ config('app.name', 'Memento') }}</title>

        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
