<aside class="app-sidebar bg-body-secondary shadow">
    <!--begin::Sidebar Brand-->
    <div class="sidebar-brand">
        <a href="{{ route('admin.dashboard') }}" class="brand-link">
            {{-- <img src="{{ asset('dist/assets/img/AdminLTELogo.png') }}" alt="Logo"
                class="brand-image opacity-75 shadow" /> --}}
            <span class="brand-text fw-semibold">Hapkido 2026</span>
        </a>
    </div>
    <!--end::Sidebar Brand-->

    <!--begin::Sidebar Wrapper-->
    <div class="sidebar-wrapper">
        <nav class="mt-2" aria-label="Navigasi utama">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" data-accordion="false" id="navigation">

                @can('dashboard.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}"
                            class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-speedometer2"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                @endcan

                @can('athletes.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.athletes.index') }}"
                            class="nav-link {{ request()->routeIs('admin.athletes.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-people-fill"></i>
                            <p>Data Atlet</p>
                        </a>
                    </li>
                @endcan

                {{-- Operasional hari-H --}}
                @if (auth()->user()->can('schedules.view') ||
                        auth()->user()->can('callings.view') ||
                        auth()->user()->can('verifications.view') ||
                        auth()->user()->can('readiness.view') ||
                        auth()->user()->can('equipment-loans.view'))
                    @php
                        $openOps = request()->routeIs(
                            'admin.schedules.*',
                            'admin.callings.*',
                            'admin.verifications.*',
                            'admin.readiness.*',
                            'admin.equipment-loans.*',
                        );
                    @endphp
                    <li class="nav-item has-treeview {{ $openOps ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $openOps ? 'active' : '' }}">
                            <i class="nav-icon bi bi-clipboard2-check-fill"></i>
                            <p>
                                Operasional Hari-H
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('schedules.view')
                                <li class="nav-item">
                                    <a href="{{ route('admin.schedules.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.schedules.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-calendar3"></i>
                                        <p>Jadwal</p>
                                    </a>
                                </li>
                            @endcan
                            @can('callings.view')
                                <li class="nav-item">
                                    <a href="{{ route('admin.callings.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.callings.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-bell-fill"></i>
                                        <p>Calling</p>
                                    </a>
                                </li>
                            @endcan
                            @can('verifications.view')
                                <li class="nav-item">
                                    <a href="{{ route('admin.verifications.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.verifications.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-qr-code-scan"></i>
                                        <p>Scan / Verifikasi</p>
                                    </a>
                                </li>
                            @endcan
                            @can('readiness.view')
                                <li class="nav-item">
                                    <a href="{{ route('admin.readiness.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.readiness.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-clipboard-check-fill"></i>
                                        <p>Pemeriksaan Kesiapan</p>
                                    </a>
                                </li>
                            @endcan
                            @can('equipment-loans.view')
                                <li class="nav-item">
                                    <a href="{{ route('admin.equipment-loans.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.equipment-loans.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-box-seam-fill"></i>
                                        <p>Peminjaman Perlengkapan</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif

                {{-- Pertandingan --}}
                @if (auth()->user()->can('matches.view') ||
                        auth()->user()->can('performances.view') ||
                        auth()->user()->can('brackets.view') ||
                        auth()->user()->can('results.view'))
                    @php
                        $openMatch = request()->routeIs(
                            'admin.matches.*',
                            'admin.performances.*',
                            'admin.scores.*',
                            'admin.brackets.*',
                            'admin.results.*',
                        );
                    @endphp
                    <li class="nav-item has-treeview {{ $openMatch ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $openMatch ? 'active' : '' }}">
                            <i class="nav-icon bi bi-trophy-fill"></i>
                            <p>
                                Pertandingan
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('matches.view')
                                <li class="nav-item">
                                    <a href="{{ route('admin.matches.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.matches.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-broadcast-pin"></i>
                                        <p>Daeryun</p>
                                    </a>
                                </li>
                            @endcan
                            @can('performances.view')
                                <li class="nav-item">
                                    <a href="{{ route('admin.performances.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.performances.*', 'admin.scores.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-star-fill"></i>
                                        <p>Seni</p>
                                    </a>
                                </li>
                            @endcan
                            @can('brackets.view')
                                <li class="nav-item">
                                    <a href="{{ route('admin.brackets.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.brackets.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-diagram-3-fill"></i>
                                        <p>Bracket</p>
                                    </a>
                                </li>
                            @endcan
                            @can('results.view')
                                <li class="nav-item">
                                    <a href="{{ route('admin.results.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.results.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-bar-chart-fill"></i>
                                        <p>Hasil &amp; Ranking</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif

                {{-- Data & Pengaturan arena --}}
                @if (auth()->user()->can('arenas.view') ||
                        auth()->user()->can('history.view') ||
                        auth()->user()->can('contingents.view') ||
                        auth()->user()->can('categories.view'))
                    @php
                        $openData = request()->routeIs(
                            'admin.arenas.*',
                            'admin.history.*',
                            'admin.contingents.*',
                            'admin.categories.*',
                        );
                    @endphp
                    <li class="nav-item has-treeview {{ $openData ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $openData ? 'active' : '' }}">
                            <i class="nav-icon bi bi-grid-3x3-gap-fill"></i>
                            <p>
                                Data &amp; Arena
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('arenas.view')
                                <li class="nav-item">
                                    <a href="{{ route('admin.arenas.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.arenas.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-columns-gap"></i>
                                        <p>Pengaturan Arena</p>
                                    </a>
                                </li>
                            @endcan
                            @can('contingents.view')
                                <li class="nav-item">
                                    <a href="{{ route('admin.contingents.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.contingents.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-flag-fill"></i>
                                        <p>Kontingen</p>
                                    </a>
                                </li>
                            @endcan
                            @can('categories.view')
                                <li class="nav-item">
                                    <a href="{{ route('admin.categories.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-bookmark-fill"></i>
                                        <p>Kategori</p>
                                    </a>
                                </li>
                            @endcan
                            @can('history.view')
                                <li class="nav-item">
                                    <a href="{{ route('admin.history.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.history.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-clock-history"></i>
                                        <p>Riwayat</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif

                {{-- Pengaturan sistem --}}
                @if (auth()->user()->can('users.view') || auth()->user()->can('roles.view') || auth()->user()->can('equipments.view'))
                    @php
                        $openSystem = request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.equipments.*');
                    @endphp
                    <li class="nav-item has-treeview {{ $openSystem ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $openSystem ? 'active' : '' }}">
                            <i class="nav-icon bi bi-gear-fill"></i>
                            <p>
                                Pengaturan
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('users.view')
                                <li class="nav-item">
                                    <a href="{{ route('admin.users.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-person-gear"></i>
                                        <p>User</p>
                                    </a>
                                </li>
                            @endcan
                            @can('roles.view')
                                <li class="nav-item">
                                    <a href="{{ route('admin.roles.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-shield-check"></i>
                                        <p>Role &amp; Permission</p>
                                    </a>
                                </li>
                            @endcan
                            @can('equipments.view')
                                <li class="nav-item">
                                    <a href="{{ route('admin.equipments.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.equipments.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-tools"></i>
                                        <p>Master Perlengkapan</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
    <!--end::Sidebar Wrapper-->
</aside>
