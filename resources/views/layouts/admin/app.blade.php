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

    {{-- Apply the theme from localStorage before paint (default: light) --}}
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

        // Plain POST with a CSRF token (for forms inside a modal)
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

        // Toggle theme (stored in localStorage, default light)
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

        // Realtime polling (dashboard / public display / calling)
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

        // Bulk delete: select all, row counter and a confirmed POST of the
        // selected ids. Delegated so re-rendered tables keep working.
        document.addEventListener('change', function(event) {
            const target = event.target;

            if (!target || !target.matches) {
                return;
            }

            if (target.matches('[data-bulk-all]')) {
                const table = target.closest('table');
                const scoped = (table || document).querySelectorAll('[data-bulk-row]');

                scoped.forEach(function(row) { row.checked = target.checked; });
            }

            if (target.matches('[data-bulk-all], [data-bulk-row]')) {
                window.refreshBulkToolbar();
            }
        });

        document.addEventListener('submit', function(event) {
            const form = event.target;

            if (!form || !form.matches || !form.matches('[data-bulk-form]')) {
                return;
            }

            const picked = Array.from(form.ownerDocument.querySelectorAll('[data-bulk-row]:checked'));

            if (!picked.length) {
                event.preventDefault();
                window.swalInfo('Pilih data', 'Centang minimal satu baris untuk dihapus.');
                return;
            }

            event.preventDefault();

            form.querySelectorAll('input[name="ids[]"]').forEach(function(input) { input.remove(); });

            picked.forEach(function(input) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'ids[]';
                hidden.value = input.value;
                form.appendChild(hidden);
            });

            // The wording of the confirmation follows the action of the form.
            const count = String(picked.length);
            const dialog = {
                icon: form.dataset.bulkIcon || 'warning',
                title: (form.dataset.bulkTitle || 'Hapus {n} data?').replace('{n}', count),
                text: form.dataset.bulkText || 'Data yang dipilih akan dihapus dari sistem.',
                confirmButtonColor: form.dataset.bulkColor || '#dc3545',
                confirmButtonText: form.dataset.bulkConfirm || 'Ya, hapus'
            };

            window.swalConfirm(dialog).then(function(result) {
                if (result.isConfirmed) HTMLFormElement.prototype.submit.call(form);
            });
        });

        window.refreshBulkToolbar = function() {
            const rows = Array.from(document.querySelectorAll('[data-bulk-row]'));
            const picked = rows.filter(function(row) { return row.checked; });

            document.querySelectorAll('[data-bulk-count]').forEach(function(el) {
                el.textContent = String(picked.length);
            });

            document.querySelectorAll('[data-bulk-submit]').forEach(function(button) {
                button.disabled = picked.length === 0;
            });

            // The toolbar is hidden while nothing is selected.
            document.querySelectorAll('[data-bulk-bar]').forEach(function(bar) {
                bar.classList.toggle('d-none', picked.length === 0);
            });

            document.querySelectorAll('[data-bulk-all]').forEach(function(master) {
                const table = master.closest('table');
                const scoped = Array.from((table || document).querySelectorAll('[data-bulk-row]'));
                const scopedPicked = scoped.filter(function(row) { return row.checked; });

                master.checked = scoped.length > 0 && scopedPicked.length === scoped.length;
                master.indeterminate = scopedPicked.length > 0 && scopedPicked.length < scoped.length;
            });
        };

        document.addEventListener('DOMContentLoaded', window.refreshBulkToolbar);
    </script>
    @stack('scripts')

    {{-- Show/hide toggle for every password field --}}
    <script src="{{ asset('dist/js/password-toggle.js') }}" defer></script>
</body>

</html>
