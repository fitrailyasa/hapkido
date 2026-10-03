@extends('layouts.admin.app')

@section('title', 'Kategori')

@section('content')
    <div class="container-fluid">
        <div class="card mb-4">
            <div class="card-header">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-4">
                        <h3 class="card-title mb-0">Daftar Kategori</h3>
                    </div>
                    <div class="col-12 col-md-8">
                        <form action="{{ route('admin.categories.index') }}" method="GET"
                            class="d-flex flex-wrap justify-content-md-end gap-2">
                            <div class="input-group input-group-sm w-auto">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="search" name="q" value="{{ $search }}" class="form-control"
                                    placeholder="Cari nama / kelas usia" style="width: 190px" />
                            </div>
                            <select name="type" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                <option value="">Semua jenis</option>
                                <option value="daeryun" @selected($typeFilter === 'daeryun')>Daeryun</option>
                                <option value="art" @selected($typeFilter === 'art')>Seni</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            @can('categories.create')
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#modal-add-category">
                                    <i class="bi bi-plus-lg me-1"></i>Tambah Kategori
                                </button>
                            @endcan
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 60px">#</th>
                                <th>Nama Kategori</th>
                                <th>Jenis</th>
                                <th>Gender</th>
                                <th>Kelas Usia</th>
                                <th>Atlet</th>
                                <th>Jadwal</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $category)
                                <tr>
                                    <td>{{ $category->id }}</td>
                                    <td class="fw-medium">{{ $category->name }}</td>
                                    <td>
                                        <span class="badge {{ $category->isArt() ? 'bg-warning text-dark' : 'bg-primary' }}">
                                            {{ $category->typeLabel() }}
                                        </span>
                                    </td>
                                    <td>{{ $category->genderLabel() }}</td>
                                    <td>{{ $category->age_class ?? '—' }}</td>
                                    <td><span class="badge bg-info-subtle text-info-emphasis">{{ $category->athletes_count }}</span></td>
                                    <td><span class="badge bg-secondary">{{ $category->schedules_count }}</span></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            @can('categories.update')
                                                <button type="button" class="btn btn-outline-secondary" title="Ubah"
                                                    aria-label="Ubah kategori"
                                                    data-bs-toggle="modal" data-bs-target="#modal-edit-category"
                                                    data-url="{{ route('admin.categories.update', $category) }}"
                                                    data-name="{{ $category->name }}"
                                                    data-type="{{ $category->type }}"
                                                    data-gender="{{ $category->gender }}"
                                                    data-age_class="{{ $category->age_class }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('categories.delete')
                                                <button type="button" class="btn btn-outline-danger" title="Hapus"
                                                    aria-label="Hapus kategori"
                                                    data-bs-toggle="modal" data-bs-target="#modal-delete-category"
                                                    data-url="{{ route('admin.categories.delete', $category) }}"
                                                    data-name="{{ $category->name }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-body-secondary py-4">
                                        Tidak ada kategori ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                {{ $categories->links('pagination::bootstrap-5') }}
            </div>
        </div>

        @include('admin.category.create')
        @include('admin.category.edit')
        @include('admin.category.delete')
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModalEl = document.getElementById('modal-edit-category');
            const deleteModalEl = document.getElementById('modal-delete-category');

            editModalEl.addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                const form = editModalEl.querySelector('form');
                form.setAttribute('action', trigger.dataset.url);
                form.querySelector('[name="name"]').value = trigger.dataset.name || '';
                form.querySelector('[name="type"]').value = trigger.dataset.type || 'daeryun';
                form.querySelector('[name="gender"]').value = trigger.dataset.gender || 'open';
                form.querySelector('[name="age_class"]').value = trigger.dataset.age_class || '';
            });

            deleteModalEl.addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                const form = deleteModalEl.querySelector('form');
                form.setAttribute('action', trigger.dataset.url);
                deleteModalEl.querySelector('[data-delete-name]').textContent = trigger.dataset.name || '';
            });

            @if ($errors->any())
                const errorModal = document.getElementById(
                    @json(old('category_id') ? 'modal-edit-category' : 'modal-add-category')
                );
                new bootstrap.Modal(errorModal).show();
            @endif
        });
    </script>
@endpush
