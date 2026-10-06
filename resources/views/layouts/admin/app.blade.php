<!doctype html>
<html lang="id" data-bs-theme="light">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ config('app.name', 'Hapkido Championship 2026') }} | @yield('title', 'Dashboard')</title>

    <meta name="description" content="{{ config('app.description') }}" />
    @if (config('app.favicon'))
        <link rel="icon" href="{{ config('app.favicon') }}" />
    @endif

    <meta name="csrf-token" content="{{ csrf_token() }}" />

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
    </script>


    <!--begin::Assets (offline)-->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.css') }}" />
    <link rel="stylesheet" href="{{ asset('dist/css/app.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}" />
    <!--end::Assets-->
    @stack('styles')
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        @include('layouts.admin.navbar')
        @include('layouts.admin.sidebar')
        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <h1 class="mb-0 fs-3">@yield('title', 'Dashboard')</h1>
                        </div>
                        <div class="col-sm-6">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb float-sm-end">
                                    <li class="breadcrumb-item">
                                        <a href="{{ route('admin.dashboard') }}">Beranda</a>
                                    </li>
                                    <li class="breadcrumb-item active" aria-current="page">
                                        @yield('title', 'Dashboard')
                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <div class="app-content">
                @include('layouts.admin.alert')
                @yield('content')
            </div>
        </main>
        <footer class="app-footer">
            <div class="float-end d-none d-sm-inline">{{ config('app.footer') }}</div>
            <strong>
                {{ config('app.copyright') }}
            </strong>
            All rights reserved.
        </footer>
    </div>

    <!--begin::Scripts (offline)-->
    <script src="{{ asset('vendor/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('dist/js/adminlte.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script>
        window.AppConfig = {
            csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
            pollInterval: 3000
        };

        window.swalSuccess = function(title, text) {
            return Swal.fire({
                icon: 'success',
                title: title || 'Berhasil',
                text: text || '',
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'OK'
            });
        };

        window.swalError = function(title, text) {
            return Swal.fire({
                icon: 'error',
                title: title || 'Gagal',
                text: text || '',
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'OK'
            });
        };

        window.swalInfo = function(title, text) {
            return Swal.fire({
                icon: 'info',
                title: title || 'Informasi',
                text: text || '',
                confirmButtonColor: '#0d6efd'
            });
        };

        window.swalConfirm = function(options) {
            return Swal.fire(Object.assign({
                icon: 'question',
                title: 'Konfirmasi',
                text: 'Apakah Anda yakin?',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, lanjutkan',
                cancelButtonText: 'Batal'
            }, options || {}));
        };

        // POST biasa dengan CSRF token (untuk form dalam modal)
        window.postForm = function(action, data) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = action;

            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_token';
            token.value = AppConfig.csrf;
            form.appendChild(token);

            Object.entries(data || {}).forEach(function(entry) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = entry[0];
                input.value = entry[1];
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        };

        // Ganti tema (tersimpan di localStorage, default light)
        window.applyTheme = function(theme) {
            const root = document.documentElement;
            root.setAttribute('data-bs-theme', theme);
            root.style.colorScheme = theme;
            try {
                localStorage.setItem('theme', theme);
            } catch (e) {}
            const icon = document.getElementById('theme-icon');
            if (icon) {
                icon.className = theme === 'dark' ? 'bi bi-moon-stars' : 'bi bi-sun';
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
            const icon = document.getElementById('theme-icon');
            if (icon) {
                icon.className = current === 'dark' ? 'bi bi-moon-stars' : 'bi bi-sun';
            }
        });

        // Polling realtime (dashboard / public display / calling)
        window.startPolling = function(url, interval, callback) {
            const run = function() {
                fetch(url, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
                    .then(function(response) { return response.ok ? response.json() : null; })
                    .then(function(data) { if (data) callback(data); })
                    .catch(function() {});
            };

            run();
            return setInterval(run, interval || AppConfig.pollInterval);
        };
    </script>
    @stack('scripts')
</body>

</html>
