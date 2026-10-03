@extends('layouts.admin.app')

@section('title', 'Master Perlengkapan')

@section('content')
    <div class="container-fluid">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="bi bi-box-seam-fill text-primary fs-4"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold">{{ $totalQty }}</div>
                            <div class="text-body-secondary small">Total Stok</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="bi bi-check-circle-fill text-success fs-4"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold">{{ $availableQty }}</div>
                            <div class="text-body-secondary small">Tersedia</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="bi bi-arrow-left-right text-warning fs-4"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold">{{ $loanedQty }}</div>
                            <div class="text-body-secondary small">Sedang Dipinjam</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-4">
                        <h3 class="card-title mb-0">Daftar Perlengkapan</h3>
                    </div>
                    <div class="col-12 col-md-8">
                        <form action="{{ route('admin.equipments.index') }}" method="GET"
                            class="d-flex flex-wrap justify-content-md-end gap-2">
                            <div class="input-group input-group-sm w-auto">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="search" name="q" value="{{ $search }}" class="form-control"
                                    placeholder="Cari kode / warna" style="width: 170px" />
                            </div>
                            <select name="type" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                <option value="">Semua jenis</option>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}" @selected($typeFilter === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <select name="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                <option value="">Semua status</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            @can('equipments.create')
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#modal-add-equipment" title="Tambah perlengkapan baru">
                                    <i class="bi bi-plus-lg me-1"></i>Tambah Perlengkapan
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
                                <th>Jenis</th>
                                <th>Warna</th>
                                <th>Ukuran</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Tersedia</th>
                                <th>Status</th>
                                @canany(['equipments.update', 'equipments.delete'])
                                    <th class="text-end">Aksi</th>
                                @endcanany
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($equipments as $equipment)
                                <tr>
                                    <td>{{ $equipment->id }}</td>
                                    <td class="fw-semibold">{{ $equipment->code }}</td>
                                    <td>{{ $equipment->typeLabel() }}</td>
                                    <td>{{ $equipment->color }}</td>
                                    <td>{{ $equipment->size }}</td>
                                    <td class="text-center">{{ $equipment->total_qty }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $equipment->available_qty > 0 ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $equipment->available_qty }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $equipment->status === 'available' ? 'bg-success' : ($equipment->status === 'loaned' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                            {{ $equipment->statusLabel() }}
                                        </span>
                                    </td>
                                    @canany(['equipments.update', 'equipments.delete'])
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                @can('equipments.update')
                                                    <button type="button" class="btn btn-outline-secondary" title="Ubah"
                                                        aria-label="Ubah perlengkapan"
                                                        data-bs-toggle="modal" data-bs-target="#modal-edit-equipment"
                                                        data-url="{{ route('admin.equipments.update', $equipment) }}"
                                                        data-type="{{ $equipment->type }}"
                                                        data-color="{{ $equipment->color }}"
                                                        data-size="{{ $equipment->size }}"
                                                        data-code="{{ $equipment->code }}"
                                                        data-status="{{ $equipment->status }}"
                                                        data-total-qty="{{ $equipment->total_qty }}">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('equipments.delete')
                                                    <button type="button" class="btn btn-outline-danger" title="Hapus"
                                                        aria-label="Hapus perlengkapan"
                                                        data-bs-toggle="modal" data-bs-target="#modal-delete-equipment"
                                                        data-url="{{ route('admin.equipments.delete', $equipment) }}"
                                                        data-code="{{ $equipment->code }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    @endcanany
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-body-secondary py-4">
                                        Tidak ada perlengkapan ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                {{ $equipments->links('pagination::bootstrap-5') }}
            </div>
        </div>

        @can('equipments.create')
            @include('admin.equipment.create')
        @endcan
        @can('equipments.update')
            @include('admin.equipment.edit')
        @endcan
        @can('equipments.delete')
            @include('admin.equipment.delete')
        @endcan
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModalEl = document.getElementById('modal-edit-equipment');
            const deleteModalEl = document.getElementById('modal-delete-equipment');

            if (editModalEl) {
                editModalEl.addEventListener('show.bs.modal', function(event) {
                    const trigger = event.relatedTarget;
                    if (!trigger) return;

                    const form = editModalEl.querySelector('form');
                    form.setAttribute('action', trigger.dataset.url);
                    form.querySelector('[name="type"]').value = trigger.dataset.type || '';
                    form.querySelector('[name="color"]').value = trigger.dataset.color || '';
                    form.querySelector('[name="size"]').value = trigger.dataset.size || '';
                    form.querySelector('[name="code"]').value = trigger.dataset.code || '';
                    form.querySelector('[name="status"]').value = trigger.dataset.status || '';
                    form.querySelector('[name="total_qty"]').value = trigger.dataset.totalQty || 1;
                });
            }

            if (deleteModalEl) {
                deleteModalEl.addEventListener('show.bs.modal', function(event) {
                    const trigger = event.relatedTarget;
                    if (!trigger) return;

                    const form = deleteModalEl.querySelector('form');
                    form.setAttribute('action', trigger.dataset.url);
                    deleteModalEl.querySelector('[data-delete-code]').textContent = trigger.dataset.code || '';
                });
            }

            @if ($errors->any())
                const errorModalId = document.querySelector('#modal-edit-equipment .is-invalid')
                    ? 'modal-edit-equipment'
                    : 'modal-add-equipment';
                const errorModalEl = document.getElementById(errorModalId);
                if (errorModalEl) new bootstrap.Modal(errorModalEl).show();
            @endif
        });
    </script>
@endpush
