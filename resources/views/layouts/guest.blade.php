<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <meta name="description" content="{{ config('app.description') }}" />
        @if (config('app.favicon'))
            <link rel="icon" href="{{ config('app.favicon') }}" />
        @endif

        {{-- Terapkan tema dari localStorage sebelum paint (default: light) --}}
        <script>
            (function() {
                var theme = 'light';
                try {
                    var saved = localStorage.getItem('theme');
                    if (saved === 'dark' || saved === 'light') {
                        theme = saved;
                    }
                } catch (e) {}
                var root = document.documentElement;
                root.setAttribute('data-bs-theme', theme);
                root.style.colorScheme = theme;
                window.__APP_THEME__ = theme;
            })();

            // Ganti tema (tersimpan di localStorage, key 'theme') — sama dengan admin & display
            window.applyTheme = function(theme) {
                var root = document.documentElement;
                root.setAttribute('data-bs-theme', theme);
                root.style.colorScheme = theme;
                try {
                    localStorage.setItem('theme', theme);
                } catch (e) {}
            };
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="{{ asset('dist/css/client.css') }}">
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="guest-shell min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <button type="button"
                    class="theme-toggle theme-toggle--corner"
                    onclick="applyTheme(document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark')"
                    title="Ganti tema terang/gelap"
                    aria-label="Ganti tema terang/gelap">
                <span class="theme-icon" aria-hidden="true">
                    <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="12" cy="12" r="4"></circle>
                        <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path>
                    </svg>
                    <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"></path>
                    </svg>
                </span>
            </button>

            <div>
                <a href="/">
                    <x-application-logo class="guest-logo w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="guest-card w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
