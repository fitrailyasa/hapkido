<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

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
    <body class="font-sans antialiased">
        <div class="app-shell min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="app-header-bar bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
