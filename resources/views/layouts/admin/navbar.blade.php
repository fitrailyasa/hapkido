<nav class="app-header navbar navbar-expand bg-body">
    <!--begin::Container-->
    <div class="container-fluid">
        <!--begin::Start Navbar Links-->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Buka/tutup menu">
                    <i class="bi bi-list"></i>
                </a>
            </li>
            <li class="nav-item d-none d-md-flex align-items-center">
                <span class="nav-link text-body-secondary small">
                    <i class="bi bi-calendar-event me-1"></i>{{ now()->translatedFormat('l, d M Y') }}
                </span>
            </li>
        </ul>
        <!--end::Start Navbar Links-->

        <!--begin::End Navbar Links-->
        <ul class="navbar-nav ms-auto">
            <li class="nav-item">
                <a href="{{ route('display.index') }}" target="_blank" class="nav-link"
                    title="Buka layar publik (display)">
                    <i class="bi bi-tv"></i>
                    <span class="d-none d-md-inline">Display</span>
                </a>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link" id="theme-toggle"
                    onclick="applyTheme((document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark'))"
                    title="Ganti tema terang/gelap">
                    <i class="bi bi-sun" id="theme-icon"></i>
                </button>
            </li>
            <!--begin::User Menu Dropdown-->
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                    {{-- <img src="{{ asset('dist/assets/img/user2-160x160.jpg') }}" class="user-image rounded-circle shadow"
                        alt="{{ auth()->user()->name ?? 'User' }}" /> --}}
                    <span class="d-none d-md-inline">{{ auth()->user()->name ?? 'Tamu' }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                    <li class="user-header text-bg-primary">
                        {{-- <img src="{{ asset('dist/assets/img/user2-160x160.jpg') }}" class="rounded-circle shadow"
                            alt="{{ auth()->user()->name ?? 'User' }}" /> --}}
                        <p>
                            {{ auth()->user()->name ?? 'Tamu' }}
                            <small>{{ auth()->user()->email ?? '' }}</small>
                        </p>
                    </li>
                    <li class="user-footer">
                        <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary">Profil</a>
                        <form action="{{ route('logout') }}" method="POST" class="d-inline float-end">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger">Keluar</button>
                        </form>
                    </li>
                </ul>
            </li>
            <!--end::User Menu Dropdown-->
        </ul>
        <!--end::End Navbar Links-->
    </div>
    <!--end::Container-->
</nav>
