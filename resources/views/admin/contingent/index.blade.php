@extends('layouts.admin.app')

@section('title', 'Kontingen')

@section('content')
    <div class="container-fluid">
        <div class="card mb-4">
            <div class="card-header">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-4">
                        <h3 class="card-title mb-0">Daftar Kontingen</h3>
                    </div>
                    <div class="col-12 col-md-8">
                        <form action="{{ route('admin.contingents.index') }}" method="GET"
                            class="d-flex flex-wrap justify-content-md-end gap-2">
                            <div class="input-group input-group-sm w-auto">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="search" name="q" value="{{ $search }}" class="form-control"
                                    placeholder="Cari nama / kode / daerah" style="width: 220px" />
                            </div>
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            @can('contingents.create')
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#modal-add-contingent">
                                    <i class="bi bi-plus-lg me-1"></i>Tambah Kontingen
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
                                <th>Kode</th>
                                <th>Nama Kontingen</th>
                                <th>Daerah</th>
                                <th>Pelatih</th>
                                <th>Jumlah Atlet</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($contingents as $contingent)
                                <tr>
                                    <td>{{ $contingent->id }}</td>
                                    <td><span class="badge bg-dark">{{ $contingent->code }}</span></td>
                                    <td class="fw-medium">{{ $contingent->name }}</td>
                                    <td>{{ $contingent->region ?? '—' }}</td>
                                    <td>{{ $contingent->coach_name ?? '—' }}</td>
                                    <td><span class="badge bg-info-subtle text-info-emphasis">{{ $contingent->athletes_count }}</span></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            @can('contingents.update')
                                                <button type="button" class="btn btn-outline-secondary" title="Ubah"
                                                    aria-label="Ubah kontingen"
                                                    data-bs-toggle="modal" data-bs-target="#modal-edit-contingent"
                                                    data-url="{{ route('admin.contingents.update', $contingent) }}"
                                                    data-name="{{ $contingent->name }}"
                                                    data-code="{{ $contingent->code }}"
                                                    data-region="{{ $contingent->region }}"
                                                    data-coach="{{ $contingent->coach_name }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('contingents.delete')
                                                <button type="button" class="btn btn-outline-danger" title="Hapus"
                                                    aria-label="Hapus kontingen"
                                                    data-bs-toggle="modal" data-bs-target="#modal-delete-contingent"
                                                    data-url="{{ route('admin.contingents.delete', $contingent) }}"
                                                    data-name="{{ $contingent->name }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-body-secondary py-4">
                                        Tidak ada kontingen ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                {{ $contingents->links('pagination::bootstrap-5') }}
            </div>
        </div>

        @include('admin.contingent.create')
        @include('admin.contingent.edit')
        @include('admin.contingent.delete')
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModalEl = document.getElementById('modal-edit-contingent');
            const deleteModalEl = document.getElementById('modal-delete-contingent');

            editModalEl.addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                const form = editModalEl.querySelector('form');
                form.setAttribute('action', trigger.dataset.url);
                form.querySelector('[name="name"]').value = trigger.dataset.name || '';
                form.querySelector('[name="code"]').value = trigger.dataset.code || '';
                form.querySelector('[name="region"]').value = trigger.dataset.region || '';
                form.querySelector('[name="coach_name"]').value = trigger.dataset.coach || '';
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
                    @json(old('contingent_id') ? 'modal-edit-contingent' : 'modal-add-contingent')
                );
                new bootstrap.Modal(errorModal).show();
            @endif
        });
    </script>
@endpush
