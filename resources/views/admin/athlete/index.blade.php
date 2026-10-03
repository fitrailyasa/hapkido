@extends('layouts.admin.app')

@section('title', 'Data Atlet')

@section('content')
    <div class="container-fluid">
        <div class="card mb-4">
            <div class="card-header">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-4">
                        <h3 class="card-title mb-0">Daftar Atlet</h3>
                    </div>
                    <div class="col-12 col-md-8">
                        <form action="{{ route('admin.athletes.index') }}" method="GET"
                            class="d-flex flex-wrap justify-content-md-end gap-2">
                            <div class="input-group input-group-sm w-auto">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="search" name="q" value="{{ $search }}" class="form-control"
                                    placeholder="Cari nama / no. peserta" style="width: 190px" />
                            </div>
                            <select name="contingent" class="form-select form-select-sm w-auto"
                                onchange="this.form.submit()">
                                <option value="">Semua kontingen</option>
                                @foreach ($contingents as $contingent)
                                    <option value="{{ $contingent->id }}" @selected($contingentFilter == $contingent->id)>
                                        {{ $contingent->name }} ({{ $contingent->code }})
                                    </option>
                                @endforeach
                            </select>
                            <select name="category" class="form-select form-select-sm w-auto"
                                onchange="this.form.submit()">
                                <option value="">Semua kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected($categoryFilter == $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            @can('athletes.view')
                                <a href="{{ route('admin.athletes.labels', array_filter(['contingent' => $contingentFilter])) }}"
                                    class="btn btn-sm btn-outline-secondary" title="Cetak label barcode atlet">
                                    <i class="bi bi-printer me-1"></i>Cetak Label
                                </a>
                            @endcan
                            @can('athletes.create')
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#modal-add-athlete" title="Tambah atlet baru">
                                    <i class="bi bi-plus-lg me-1"></i>Tambah Atlet
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
                                <th>Nama</th>
                                <th>No. Peserta</th>
                                <th>Jenis Kelamin</th>
                                <th>Kontingen</th>
                                <th>Kategori</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($athletes as $athlete)
                                <tr>
                                    <td>{{ $athlete->id }}</td>
                                    <td class="fw-medium">{{ $athlete->name }}</td>
                                    <td><span class="badge bg-body-secondary text-body border">{{ $athlete->participant_number }}</span></td>
                                    <td>{{ $athlete->genderLabel() }}</td>
                                    <td>
                                        {{ $athlete->contingent?->name ?? '—' }}
                                        @if ($athlete->contingent)
                                            <span class="text-body-secondary small">({{ $athlete->contingent->code }})</span>
                                        @endif
                                    </td>
                                    <td>{{ $athlete->category?->name ?? '—' }}</td>
                                    <td>
                                        <span class="badge {{ $athlete->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $athlete->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info" title="Lihat detail"
                                                aria-label="Lihat detail atlet"
                                                data-show-url="{{ route('admin.athletes.show', $athlete) }}">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            @can('athletes.update')
                                                <button type="button" class="btn btn-outline-secondary" title="Ubah"
                                                    aria-label="Ubah atlet"
                                                    data-bs-toggle="modal" data-bs-target="#modal-edit-athlete"
                                                    data-url="{{ route('admin.athletes.update', $athlete) }}"
                                                    data-name="{{ $athlete->name }}"
                                                    data-gender="{{ $athlete->gender }}"
                                                    data-birth_date="{{ $athlete->birth_date?->format('Y-m-d') }}"
                                                    data-id_number="{{ $athlete->id_number }}"
                                                    data-contingent="{{ $athlete->contingent_id }}"
                                                    data-category="{{ $athlete->category_id }}"
                                                    data-participant_number="{{ $athlete->participant_number }}"
                                                    data-qr_code="{{ $athlete->qr_code }}"
                                                    data-status="{{ $athlete->status }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('athletes.delete')
                                                <button type="button" class="btn btn-outline-danger" title="Hapus"
                                                    aria-label="Hapus atlet"
                                                    data-bs-toggle="modal" data-bs-target="#modal-delete-athlete"
                                                    data-url="{{ route('admin.athletes.destroy', $athlete) }}"
                                                    data-name="{{ $athlete->name }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-body-secondary py-4">
                                        Tidak ada atlet ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                {{ $athletes->links('pagination::bootstrap-5') }}
            </div>
        </div>

        @include('admin.athlete.create')
        @include('admin.athlete.show')
        @include('admin.athlete.edit')
        @include('admin.athlete.delete')
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModalEl = document.getElementById('modal-edit-athlete');
            const deleteModalEl = document.getElementById('modal-delete-athlete');

            editModalEl.addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                const form = editModalEl.querySelector('form');
                form.setAttribute('action', trigger.dataset.url);
                form.querySelector('[name="name"]').value = trigger.dataset.name || '';
                form.querySelector('[name="gender"]').value = trigger.dataset.gender || '';
                form.querySelector('[name="birth_date"]').value = trigger.dataset.birth_date || '';
                form.querySelector('[name="id_number"]').value = trigger.dataset.id_number || '';
                form.querySelector('[name="contingent_id"]').value = trigger.dataset.contingent || '';
                form.querySelector('[name="category_id"]').value = trigger.dataset.category || '';
                form.querySelector('[name="participant_number"]').value = trigger.dataset.participant_number || '';
                form.querySelector('[name="qr_code"]').value = trigger.dataset.qr_code || '';
                form.querySelector('[name="status"]').value = trigger.dataset.status || 'active';
                form.querySelector('[name="photo"]').value = '';
            });

            deleteModalEl.addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                const form = deleteModalEl.querySelector('form');
                form.setAttribute('action', trigger.dataset.url);
                deleteModalEl.querySelector('[data-delete-name]').textContent = trigger.dataset.name || '';
            });

            document.querySelectorAll('[data-show-url]').forEach(function(button) {
                button.addEventListener('click', function() {
                    fetch(button.dataset.showUrl, { headers: { Accept: 'application/json' } })
                        .then(function(response) {
                            if (!response.ok) throw new Error('Not found');
                            return response.json();
                        })
                        .then(function(athlete) {
                            const modal = document.getElementById('modal-show-athlete');
                            modal.querySelector('[data-field="name"]').textContent = athlete.name;
                            modal.querySelector('[data-field="gender"]').textContent = athlete.gender_label;
                            modal.querySelector('[data-field="birth_date"]').textContent = (athlete.birth_date || '—') +
                                (athlete.age !== null ? ' (' + athlete.age + ' th)' : '');
                            modal.querySelector('[data-field="id_number"]').textContent = athlete.id_number;
                            modal.querySelector('[data-field="contingent"]').textContent = athlete.contingent;
                            modal.querySelector('[data-field="region"]').textContent = athlete.region;
                            modal.querySelector('[data-field="category"]').textContent = athlete.category;
                            modal.querySelector('[data-field="category_type"]').textContent = athlete.category_type;
                            modal.querySelector('[data-field="participant_number"]').textContent = athlete.participant_number;
                            modal.querySelector('[data-field="qr_code"]').textContent = athlete.qr_code;
                            modal.querySelector('[data-field="status"]').textContent = athlete.status_label;
                            modal.querySelector('[data-field="created_at"]').textContent = athlete.created_at;

                            const photo = modal.querySelector('[data-field="photo"]');
                            if (athlete.photo_url) {
                                photo.src = athlete.photo_url;
                                photo.parentElement.classList.remove('d-none');
                            } else {
                                photo.parentElement.classList.add('d-none');
                            }

                            new bootstrap.Modal(modal).show();
                        })
                        .catch(function() {
                            swalError('Gagal', 'Detail atlet tidak dapat dimuat.');
                        });
                });
            });

            @if ($errors->any())
                const errorModal = document.getElementById(
                    @json(old('athlete_id') ? 'modal-edit-athlete' : 'modal-add-athlete')
                );
                new bootstrap.Modal(errorModal).show();
            @endif
        });
    </script>
@endpush
