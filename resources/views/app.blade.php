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
        <meta name="theme-color" content="#fbfaff" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#17162b" media="(prefers-color-scheme: dark)">
        <meta name="apple-mobile-web-app-title" content="Memento">

        <link rel="icon" href="/favicon.ico" sizes="48x48">
        <link rel="icon" href="/icons/favicon-32.png" type="image/png" sizes="32x32">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">

        @guest
            {{-- Signed-out visitors keep their theme in the browser. Applying it here, before the first paint, avoids a flash of the device theme. Keep the key in step with useAppTheme.ts. --}}
            <script @if (Vite::cspNonce()) nonce="{{ Vite::cspNonce() }}" @endif>
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
