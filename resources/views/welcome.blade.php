<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Hapkido Championship 2026') }}</title>
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
    <body class="font-sans antialiased">
        <div class="wl-page min-h-screen flex flex-col">
            <!-- Header -->
            <header class="wl-topbar">
                <a href="{{ url('/welcome') }}" class="wl-brand">
                    <x-application-logo class="wl-logo h-11 w-11 fill-current" />
                    <span class="wl-brand-text">
                        <strong>{{ config('app.name', 'Hapkido Championship 2026') }}</strong>
                        <small>{{ config('app.tagline', 'Kejuaraan Hapkido Indonesia') }}</small>
                    </span>
                </a>

                <div class="wl-actions">
                    <button type="button"
                            class="theme-toggle"
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

                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="wl-btn wl-btn--ghost">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="wl-btn wl-btn--ghost">
                                Masuk
                            </a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="wl-btn wl-btn--primary">
                                    Daftar
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </header>

            <!-- Hero -->
            <main class="wl-hero">
                <div class="wl-hero-inner">
                    <span class="wl-eyebrow">Musim 2026</span>

                    <h1 class="wl-title">
                        Selamat datang di <span class="wl-accent">{{ config('app.name', 'Hapkido Championship') }}</span>
                    </h1>

                    <p class="wl-lead">
                        {{ config('app.description') }}
                        Informasi tampil langsung di layar venue dan dapat dibuka dari
                        perangkat apa pun, baik secara daring maupun luring.
                    </p>

                    <div class="wl-cta">
                        <a href="{{ route('display.index') }}" class="wl-btn wl-btn--primary">
                            Lihat Layar Pertandingan
                        </a>

                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="wl-btn wl-btn--ghost">
                                    Buka Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="wl-btn wl-btn--ghost">
                                    Masuk ke Akun
                                </a>
                            @endauth
                        @endif
                    </div>
                </div>
            </main>

            <!-- Fitur -->
            <section class="wl-section">
                <div class="wl-grid">
                    <div class="wl-card">
                        <span class="wl-card-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                <path d="M16 2v4M8 2v4M3 10h18"></path>
                            </svg>
                        </span>
                        <h2>Jadwal &amp; Arena</h2>
                        <p>
                            Jadwal pertandingan per arena, lengkap dengan status
                            pertandingan yang diperbarui secara berkala.
                        </p>
                    </div>

                    <div class="wl-card">
                        <span class="wl-card-icon wl-card-icon--gold">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8 21h8M12 17v4M17 4h3a1 1 0 0 1 1 1v2a4 4 0 0 1-4 4h-1zM7 4H4a1 1 0 0 0-1 1v2a4 4 0 0 0 4 4h1z"></path>
                                <rect x="7" y="4" width="10" height="7" rx="2"></rect>
                            </svg>
                        </span>
                        <h2>Kontingen &amp; Atlet</h2>
                        <p>
                            Data kontingen, atlet, dan kategori pertandingan yang
                            terdaftar resmi pada kejuaraan tahun ini.
                        </p>
                    </div>

                    <div class="wl-card">
                        <span class="wl-card-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 20V10M10 20V4M16 20v-7M22 20H2"></path>
                            </svg>
                        </span>
                        <h2>Hasil &amp; Ranking</h2>
                        <p>
                            Hasil akhir, bracket, dan perolehan medali setiap kategori
                            ditampilkan pada layar publik.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <footer class="wl-footer">
                <span>{{ config('app.copyright', '© 2026 ' . config('app.name')) }}</span>
                <span>
                    Laravel v{{ Illuminate\Foundation\Application::VERSION }} &middot; PHP v{{ PHP_VERSION }}
                </span>
            </footer>
        </div>
    </body>
</html>
