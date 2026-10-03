@extends('layouts.admin.app')

@section('title', 'Pengaturan Arena')

@section('content')
    <div class="container-fluid">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Daftar Arena</h3>
                <div>
                    @can('arenas.create')
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-arena"
                            title="Tambah arena baru">
                            <i class="bi bi-plus-lg me-1"></i>Tambah Arena
                        </button>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @forelse ($arenas as $arena)
                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="mb-0">{{ $arena->name }}</h5>
                                            <span class="text-body-secondary small">{{ $arena->label }}</span>
                                        </div>
                                        <span
                                            class="badge {{ ['running' => 'bg-danger', 'preparation' => 'bg-warning text-dark'][$arena->status] ?? 'bg-secondary' }}">
                                            {{ $arena->statusLabel() }}
                                        </span>
                                    </div>
                                    <table class="table table-borderless table-sm mb-0">
                                        <tbody>
                                            <tr>
                                                <th class="text-body-secondary fw-normal p-0" style="width: 45%">
                                                    Kategori
                                                </th>
                                                <td class="p-0">{{ $arena->category?->name ?? 'Umum / belum ditentukan' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="text-body-secondary fw-normal p-0">Jenis</th>
                                                <td class="p-0">
                                                    <span
                                                        class="badge {{ $arena->match_type === 'art' ? 'bg-warning text-dark' : 'bg-primary' }}">
                                                        {{ $arena->match_type === 'art' ? 'Seni' : 'Daeryun' }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="text-body-secondary fw-normal p-0">Jadwal aktif</th>
                                                <td class="p-0">
                                                    @if ($arena->currentSchedule)
                                                        {{ $arena->currentSchedule->match_no }}
                                                        <span class="text-body-secondary small">
                                                            ({{ $arena->currentSchedule->category?->name ?? '—' }})
                                                        </span>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                @canany(['arenas.update', 'arenas.delete'])
                                    <div class="card-footer d-flex justify-content-end">
                                        <div class="btn-group btn-group-sm">
                                            @can('arenas.update')
                                                <button type="button" class="btn btn-outline-secondary" title="Ubah"
                                                    aria-label="Ubah arena"
                                                    data-bs-toggle="modal" data-bs-target="#modal-edit-arena"
                                                    data-url="{{ route('admin.arenas.update', $arena) }}"
                                                    data-id="{{ $arena->id }}"
                                                    data-name="{{ $arena->name }}"
                                                    data-label="{{ $arena->label }}"
                                                    data-category="{{ $arena->category_id }}"
                                                    data-type="{{ $arena->match_type }}"
                                                    data-status="{{ $arena->status }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('arenas.delete')
                                                <button type="button" class="btn btn-outline-danger" title="Hapus"
                                                    aria-label="Hapus arena"
                                                    data-bs-toggle="modal" data-bs-target="#modal-delete-arena"
                                                    data-url="{{ route('admin.arenas.delete', $arena) }}"
                                                    data-name="{{ $arena->name }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                @endcanany
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <p class="text-center text-body-secondary py-4 mb-0">Belum ada arena terdaftar.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!--begin::Edit Arena Modal-->
    <div class="modal fade" id="modal-edit-arena" tabindex="-1" aria-labelledby="modal-edit-arena-label"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ old('arena_id') ? route('admin.arenas.update', old('arena_id')) : '#' }}"
                    method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="arena_id" value="{{ old('arena_id') }}" />
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-edit-arena-label">Ubah Arena</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit-arena-name" class="form-label">Nama Arena</label>
                            <input type="text" name="name" id="edit-arena-name"
                                class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                                required />
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="edit-arena-label" class="form-label">Label / Keterangan</label>
                            <input type="text" name="label" id="edit-arena-label"
                                class="form-control @error('label') is-invalid @enderror"
                                value="{{ old('label') }}" placeholder="Contoh: Lapangan Utama" required />
                            @error('label')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="edit-arena-category_id" class="form-label">Kategori yang Ditangani</label>
                            <select id="edit-arena-category_id" name="category_id"
                                class="form-select @error('category_id') is-invalid @enderror">
                                <option value="">— Umum / semua kategori —</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit-arena-match_type" class="form-label">Jenis Pertandingan</label>
                                <select id="edit-arena-match_type" name="match_type"
                                    class="form-select @error('match_type') is-invalid @enderror" required>
                                    <option value="daeryun" @selected(old('match_type') === 'daeryun')>Daeryun</option>
                                    <option value="art" @selected(old('match_type') === 'art')>Seni</option>
                                </select>
                                @error('match_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit-arena-status" class="form-label">Status</label>
                                <select id="edit-arena-status" name="status"
                                    class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="idle" @selected(old('status') === 'idle')>Menunggu</option>
                                    <option value="preparation" @selected(old('status') === 'preparation')>Persiapan</option>
                                    <option value="running" @selected(old('status') === 'running')>Tampil</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!--end::Edit Arena Modal-->

    <!--begin::Add Arena Modal-->
    <div class="modal fade" id="modal-add-arena" tabindex="-1" aria-labelledby="modal-add-arena-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.arenas.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-add-arena-label">Tambah Arena</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add-arena-name" class="form-label">Nama Arena</label>
                            <input type="text" name="name" id="add-arena-name"
                                class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                                placeholder="Contoh: Arena D" required />
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="add-arena-label" class="form-label">Label / Keterangan</label>
                            <input type="text" name="label" id="add-arena-label"
                                class="form-control @error('label') is-invalid @enderror"
                                value="{{ old('label') }}" placeholder="Contoh: Lapangan Cadangan" required />
                            @error('label')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="add-arena-category_id" class="form-label">Kategori yang Ditangani</label>
                            <select id="add-arena-category_id" name="category_id"
                                class="form-select @error('category_id') is-invalid @enderror">
                                <option value="">— Umum / semua kategori —</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add-arena-match_type" class="form-label">Jenis Pertandingan</label>
                                <select id="add-arena-match_type" name="match_type"
                                    class="form-select @error('match_type') is-invalid @enderror" required>
                                    <option value="daeryun" @selected(old('match_type') === 'daeryun')>Daeryun</option>
                                    <option value="art" @selected(old('match_type') === 'art')>Seni</option>
                                </select>
                                @error('match_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="add-arena-status" class="form-label">Status</label>
                                <select id="add-arena-status" name="status"
                                    class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="idle" @selected(old('status') === 'idle')>Menunggu</option>
                                    <option value="preparation" @selected(old('status') === 'preparation')>Persiapan</option>
                                    <option value="running" @selected(old('status') === 'running')>Tampil</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!--end::Add Arena Modal-->

    <!--begin::Delete Arena Modal-->
    <div class="modal fade" id="modal-delete-arena" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="#" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title">Hapus Arena</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        Yakin menghapus arena <strong data-delete-name></strong>?
                        Arena yang masih dipakai jadwal tidak dapat dihapus.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!--end::Delete Arena Modal-->
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModalEl = document.getElementById('modal-edit-arena');

            editModalEl.addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                const form = editModalEl.querySelector('form');
                form.setAttribute('action', trigger.dataset.url);
                form.querySelector('[name="arena_id"]').value = trigger.dataset.id || '';
                form.querySelector('[name="name"]').value = trigger.dataset.name || '';
                form.querySelector('[name="label"]').value = trigger.dataset.label || '';
                form.querySelector('[name="category_id"]').value = trigger.dataset.category || '';
                form.querySelector('[name="match_type"]').value = trigger.dataset.type || 'daeryun';
                form.querySelector('[name="status"]').value = trigger.dataset.status || 'idle';
            });

            const deleteModalEl = document.getElementById('modal-delete-arena');

            deleteModalEl.addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;
                const form = deleteModalEl.querySelector('form');
                form.setAttribute('action', trigger.dataset.url);
                deleteModalEl.querySelector('[data-delete-name]').textContent = trigger.dataset.name || '';
            });

            @if ($errors->any())
                const target = document.getElementById(
                    @json(old('arena_id') ? 'modal-edit-arena' : 'modal-add-arena')
                );
                if (target.id === 'modal-edit-arena') {
                    target.querySelector('form').setAttribute('action',
                        '/admin/arenas/' + @json(old('arena_id')) + '?_method=PUT');
                    target.querySelector('[name="name"]').value = @json(old('name', ''));
                    target.querySelector('[name="label"]').value = @json(old('label', ''));
                    target.querySelector('[name="category_id"]').value = @json(old('category_id', ''));
                    target.querySelector('[name="match_type"]').value = @json(old('match_type', 'daeryun'));
                    target.querySelector('[name="status"]').value = @json(old('status', 'idle'));
                }
                new bootstrap.Modal(target).show();
            @endif
        });
    </script>
@endpush
