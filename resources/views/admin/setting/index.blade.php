@extends('layouts.admin.app')

@section('title', 'Identitas & Tampilan')

@section('content')
    <div class="container-fluid">
        <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row">
                <div class="col-lg-8">
                    {{-- Judul & Deskripsi --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Judul &amp; Deskripsi</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="setting-app_name" class="form-label">
                                        Judul Aplikasi <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="app_name" id="setting-app_name"
                                        class="form-control @error('app_name') is-invalid @enderror"
                                        value="{{ old('app_name', $settings['app_name']) }}" maxlength="255"
                                        placeholder="Hapkido Championship 2026" required />
                                    <div class="form-text">Dipakai pada judul tab browser, display, &amp; label atlet.</div>
                                    @error('app_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="setting-app_short_name" class="form-label">
                                        Nama Singkat <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="app_short_name" id="setting-app_short_name"
                                        class="form-control @error('app_short_name') is-invalid @enderror"
                                        value="{{ old('app_short_name', $settings['app_short_name']) }}" maxlength="60"
                                        placeholder="Hapkido 2026" required />
                                    <div class="form-text">Untuk sidebar &amp; bar atas display.</div>
                                    @error('app_short_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="setting-app_tagline" class="form-label">Tagline</label>
                                <input type="text" name="app_tagline" id="setting-app_tagline"
                                    class="form-control @error('app_tagline') is-invalid @enderror"
                                    value="{{ old('app_tagline', $settings['app_tagline']) }}" maxlength="255"
                                    placeholder="Kejuaraan Hapkido Indonesia" />
                                <div class="form-text">Teks pendek di bawah logo pada halaman publik.</div>
                                @error('app_tagline')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-0">
                                <label for="setting-app_description" class="form-label">Deskripsi</label>
                                <textarea name="app_description" id="setting-app_description" rows="3"
                                    class="form-control @error('app_description') is-invalid @enderror"
                                    maxlength="1000"
                                    placeholder="Portal resmi jadwal, arena, kontingen, dan hasil pertandingan.">{{ old('app_description', $settings['app_description']) }}</textarea>
                                <div class="form-text">Dipakai sebagai meta description &amp; kalimat pembuka halaman publik.</div>
                                @error('app_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Logo & Favicon --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Logo &amp; Favicon</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="setting-app_logo" class="form-label">Logo</label>
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <div class="border rounded d-flex align-items-center justify-content-center bg-body-secondary"
                                            style="width: 72px; height: 72px;">
                                            @if ($settings['app_logo'])
                                                <img src="{{ $settings['app_logo'] }}" alt="Logo"
                                                    style="max-width: 100%; max-height: 100%; object-fit: contain;" />
                                            @else
                                                <i class="bi bi-image text-body-secondary fs-4"></i>
                                            @endif
                                        </div>
                                        <div class="small text-body-secondary">
                                            Format: PNG / JPG / WEBP / SVG, maks 2 MB.<br />
                                            Disarankan persegi (transparan) agar rapi di sidebar &amp; display.
                                        </div>
                                    </div>
                                    <input type="file" name="app_logo" id="setting-app_logo"
                                        class="form-control @error('app_logo') is-invalid @enderror"
                                        accept=".png,.jpg,.jpeg,.webp,.svg" />
                                    @error('app_logo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if ($settings['app_logo'])
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="remove_logo"
                                                value="1" id="remove-logo" @checked(old('remove_logo')) />
                                            <label class="form-check-label" for="remove-logo">
                                                Hapus logo saat disimpan
                                            </label>
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="setting-app_favicon" class="form-label">Favicon</label>
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <div class="border rounded d-flex align-items-center justify-content-center bg-body-secondary"
                                            style="width: 32px; height: 32px;">
                                            @if ($settings['app_favicon'])
                                                <img src="{{ $settings['app_favicon'] }}" alt="Favicon"
                                                    style="max-width: 100%; max-height: 100%;" />
                                            @else
                                                <i class="bi bi-image text-body-secondary"></i>
                                            @endif
                                        </div>
                                        <div class="small text-body-secondary">
                                            Format: PNG / JPG / ICO / SVG, maks 1 MB.<br />
                                            Ikon kecil yang tampil di tab browser.
                                        </div>
                                    </div>
                                    <input type="file" name="app_favicon" id="setting-app_favicon"
                                        class="form-control @error('app_favicon') is-invalid @enderror"
                                        accept=".png,.jpg,.jpeg,.ico,.svg" />
                                    @error('app_favicon')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if ($settings['app_favicon'])
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="remove_favicon"
                                                value="1" id="remove-favicon" @checked(old('remove_favicon')) />
                                            <label class="form-check-label" for="remove-favicon">
                                                Hapus favicon saat disimpan
                                            </label>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    {{-- Preview --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Preview</h3>
                        </div>
                        <div class="card-body">
                            <div class="border rounded overflow-hidden mb-3">
                                <div class="bg-body-secondary px-3 py-2 d-flex align-items-center gap-2">
                                    <span class="rounded-circle d-inline-block bg-danger" style="width: 9px; height: 9px;"></span>
                                    <span class="rounded-circle d-inline-block bg-warning" style="width: 9px; height: 9px;"></span>
                                    <span class="rounded-circle d-inline-block bg-success" style="width: 9px; height: 9px;"></span>
                                    <span class="small text-body-secondary ms-1">Tab browser</span>
                                </div>
                                <div class="p-3 bg-body">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        @if ($settings['app_logo'])
                                            <img src="{{ $settings['app_logo'] }}" alt=""
                                                style="height: 24px; width: auto;" />
                                        @endif
                                        <strong data-preview="title" class="text-truncate">
                                            {{ old('app_name', $settings['app_name']) }}
                                        </strong>
                                    </div>
                                    <p class="small text-body-secondary mb-0" data-preview="description">
                                        {{ old('app_description', $settings['app_description']) ?: 'Deskripsi aplikasi akan tampil di sini.' }}
                                    </p>
                                </div>
                            </div>

                            <div class="small text-body-secondary">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Tagline</span>
                                    <span class="text-body" data-preview="tagline">{{ old('app_tagline', $settings['app_tagline']) ?: '—' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Nama singkat</span>
                                    <span class="text-body" data-preview="short_name">{{ old('app_short_name', $settings['app_short_name']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Footer</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="setting-app_footer" class="form-label">Teks Footer</label>
                                <textarea name="app_footer" id="setting-app_footer" rows="2"
                                    class="form-control @error('app_footer') is-invalid @enderror"
                                    maxlength="500">{{ old('app_footer', $settings['app_footer']) }}</textarea>
                                @error('app_footer')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-0">
                                <label for="setting-app_copyright" class="form-label">Copyright</label>
                                <input type="text" name="app_copyright" id="setting-app_copyright"
                                    class="form-control @error('app_copyright') is-invalid @enderror"
                                    value="{{ old('app_copyright', $settings['app_copyright']) }}" maxlength="255" />
                                @error('app_copyright')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Simpan Perubahan
                            </button>
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
                                Kembali
                            </a>
                            <p class="small text-body-secondary mb-0">
                                Perubahan langsung terpakai pada tab browser, sidebar, display, dan halaman publik.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        {{-- Kembalikan ke default (form terpisah, tidak boleh bersarang) --}}
        @can('settings.update')
            <div class="card border-danger">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="mb-1">Kembalikan ke Pengaturan Default</h5>
                        <p class="small text-body-secondary mb-0">
                            Menghapus semua perubahan identitas serta logo/favicon yang diunggah,
                            lalu kembali memakai nilai dari <code>.env</code>.
                        </p>
                    </div>
                    <form action="{{ route('admin.settings.reset') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger"
                            onclick="return confirm('Yakin mengembalikan identitas aplikasi ke default?')">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Default
                        </button>
                    </form>
                </div>
            </div>
        @endcan
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bind = (name, target, fallback) => {
                const input = document.querySelector('[name="' + name + '"]');
                const output = document.querySelector('[data-preview="' + target + '"]');
                if (!input || !output) return;

                const update = () => {
                    output.textContent = input.value.trim() || fallback || '';
                };

                input.addEventListener('input', update);
                update();
            };

            bind('app_name', 'title', '');
            bind('app_tagline', 'tagline', '—');
            bind('app_short_name', 'short_name', '');
            bind('app_description', 'description', 'Deskripsi aplikasi akan tampil di sini.');
        });
    </script>
@endpush
