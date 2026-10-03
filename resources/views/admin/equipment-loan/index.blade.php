@extends('layouts.admin.app')

@section('title', 'Peminjaman Perlengkapan')

@section('content')
    <div class="container-fluid">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
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
            <div class="col-md-3">
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
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="bi bi-arrow-left-right text-warning fs-4"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold">{{ $loanedQty }}</div>
                            <div class="text-body-secondary small">Unit Dipinjam</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                            <i class="bi bi-clock-history text-danger fs-4"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold">{{ $activeLoans }}</div>
                            <div class="text-body-secondary small">Transaksi Aktif</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-4">
                        <h3 class="card-title mb-0">Riwayat Peminjaman</h3>
                    </div>
                    <div class="col-12 col-md-8">
                        <form action="{{ route('admin.equipment-loans.index') }}" method="GET"
                            class="d-flex flex-wrap justify-content-md-end gap-2">
                            <div class="input-group input-group-sm w-auto">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="search" name="q" value="{{ $search }}" class="form-control"
                                    placeholder="Cari atlet / kode alat" style="width: 180px" />
                            </div>
                            <select name="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                <option value="">Semua status</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            @can('equipment-loans.borrow')
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#modal-borrow-equipment">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>Pinjam Perlengkapan
                                </button>
                            @endcan
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body p-0" id="loan-list">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 60px">#</th>
                                <th>Atlet</th>
                                <th>Kontingen</th>
                                <th>Perlengkapan</th>
                                <th class="text-center" style="width: 60px">Qty</th>
                                <th style="width: 120px">Dipinjam</th>
                                <th style="width: 120px">Dikembalikan</th>
                                <th>Status</th>
                                <th>Catatan / Kondisi</th>
                                <th class="text-end" style="width: 130px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($loans as $loan)
                                <tr>
                                    <td>{{ $loan->id }}</td>
                                    <td class="fw-medium">{{ $loan->athlete?->name ?? 'â€”' }}</td>
                                    <td>
                                        <span class="badge bg-dark">
                                            {{ $loan->athlete?->contingent?->code ?? 'â€”' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ $loan->equipment?->code ?? 'â€”' }}</span>
                                        <div class="small text-body-secondary">
                                            {{ $loan->equipment?->typeLabel() ?? 'â€”' }}
                                            &middot; {{ $loan->equipment?->color ?? 'â€”' }}
                                            &middot; {{ $loan->equipment?->size ?? 'â€”' }}
                                        </div>
                                    </td>
                                    <td class="text-center">{{ $loan->qty }}</td>
                                    <td>{{ $loan->loaned_at?->format('d M H:i') ?? 'â€”' }}</td>
                                    <td>{{ $loan->returned_at?->format('d M H:i') ?? 'â€”' }}</td>
                                    <td>
                                        <span class="badge {{ $loan->isReturned() ? 'bg-secondary' : 'bg-warning text-dark' }}">
                                            {{ $loan->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="small text-body-secondary">
                                        @if ($loan->notes)
                                            <div>{{ $loan->notes }}</div>
                                        @endif
                                        @if ($loan->loan_condition)
                                            <div>Pinjam: {{ $loan->loan_condition }}</div>
                                        @endif
                                        @if ($loan->return_condition)
                                            <div>Kembali: {{ $loan->return_condition }}</div>
                                        @endif
                                        @if (! $loan->notes && ! $loan->loan_condition && ! $loan->return_condition)
                                                                    â€”
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if (! $loan->isReturned())
                                            @can('equipment-loans.return')
                                                <button type="button" class="btn btn-sm btn-outline-warning"
                                                    title="Kembalikan perlengkapan"
                                                    aria-label="Kembalikan perlengkapan"
                                                    data-return-url="{{ route('admin.equipment-loans.return', $loan) }}"
                                                    data-athlete="{{ $loan->athlete?->name ?? 'atlet' }}"
                                                    data-equipment="{{ $loan->equipment?->code ?? 'â€”' }}">
                                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Kembalikan
                                                </button>
                                            @endcan
                                        @else
                                            <span class="text-body-secondary small">
                                                {{ $loan->returnedBy?->name ?? 'â€”' }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-body-secondary py-4">
                                        Belum ada data peminjaman.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                {{ $loans->links('pagination::bootstrap-5') }}
            </div>
        </div>

        @can('equipment-loans.borrow')
            @include('admin.equipment-loan.create')
        @endcan
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const equipmentSelect = document.getElementById('borrow-equipment');
            const qtyInput = document.getElementById('borrow-qty');
            const stockHint = document.getElementById('borrow-stock-hint');
            const borrowModalEl = document.getElementById('modal-borrow-equipment');

            if (equipmentSelect && qtyInput) {
                equipmentSelect.addEventListener('change', function() {
                    const option = equipmentSelect.options[equipmentSelect.selectedIndex];
                    const available = parseInt(option.dataset.available || '0', 10);

                    qtyInput.max = Math.max(available, 1);
                    if (available < parseInt(qtyInput.value || '1', 10)) qtyInput.value = Math.max(available, 1);

                    if (stockHint) {
                        stockHint.textContent = option.value
                            ? 'Stok tersedia: ' + available + ' unit'
                            : '';
                    }
                });
            }

            if (borrowModalEl && stockHint && equipmentSelect) {
                borrowModalEl.addEventListener('shown.bs.modal', function() {
                    equipmentSelect.dispatchEvent(new Event('change'));
                });
            }

            const loanList = document.getElementById('loan-list');

            if (loanList) {
                loanList.addEventListener('click', function(event) {
                    const returnButton = event.target.closest('[data-return-url]');
                    if (!returnButton) return;

                    swalConfirm({
                        title: 'Kembalikan Perlengkapan',
                        text: 'Kembalikan ' + returnButton.dataset.equipment + ' dari ' +
                            returnButton.dataset.athlete + '?',
                        input: 'text',
                        inputLabel: 'Kondisi barang',
                        inputPlaceholder: 'Opsional, contoh: baik / rusak ringan',
                        confirmButtonText: 'Ya, kembalikan'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            postForm(returnButton.dataset.returnUrl, {
                                return_condition: result.value || ''
                            });
                        }
                    });
                });
            }

            @if ($errors->any())
                if (borrowModalEl) new bootstrap.Modal(borrowModalEl).show();
            @endif
        });
    </script>
@endpush
