@extends('layouts.admin.app')

@section('title', 'Seni - Urutan Tampil')

@section('content')
    <div class="container-fluid">
        <div class="card mb-4">
            <div class="card-header">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-5">
                        <h3 class="card-title mb-0">Jadwal Penampilan Seni</h3>
                    </div>
                    <div class="col-12 col-md-7">
                        <form action="{{ route('admin.performances.index') }}" method="GET"
                            class="d-flex flex-wrap justify-content-md-end gap-2">
                            <select name="category" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                <option value="">Semua kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected($categoryFilter === (string) $category->id)>{{ $category->name }}</option>
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
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @forelse ($schedules as $schedule)
            @php
                $scheduleBadge = [
                    'pending' => 'bg-info-subtle text-info-emphasis',
                    'preparation' => 'bg-warning text-dark',
                    'running' => 'bg-success',
                    'finished' => 'bg-secondary',
                    'cancelled' => 'bg-danger',
                ];
            @endphp
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <span class="badge bg-dark me-1">Partai {{ $schedule->match_no }}</span>
                        <strong>{{ $schedule->category?->name ?? '-' }}</strong>
                        <span class="text-body-secondary small ms-2">
                            <i class="bi bi-geo-alt"></i> {{ $schedule->arena?->name ?? '-' }}
                            &middot; {{ optional($schedule->match_date)->format('d M Y') }}
                            &middot; {{ substr((string) ($schedule->start_time ?? ''), 0, 5) ?: '-' }}
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge {{ $scheduleBadge[$schedule->status] ?? 'bg-secondary' }}">
                            {{ $schedule->statusLabel() }}
                        </span>
                        @can('performances.create')
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                data-bs-target="#modal-add-performance" title="Tambah penampil"
                                data-schedule-id="{{ $schedule->id }}"
                                data-category-id="{{ $schedule->category_id }}"
                                data-order-no="{{ $schedule->performances->count() + 1 }}">
                                <i class="bi bi-plus-lg me-1"></i>Tambah Penampil
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 70px">No. Urut</th>
                                    <th>Penampil</th>
                                    <th>Jadwal</th>
                                    <th>Status</th>
                                    <th class="text-end">Nilai Akhir</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($schedule->performances->sortBy('order_no') as $performance)
                                    <tr>
                                        <td class="text-center fw-semibold">{{ $performance->order_no }}</td>
                                        <td>
                                            <div class="fw-medium">{{ $performance->athlete?->name ?? '-' }}</div>
                                            <small class="text-body-secondary">
                                                {{ $performance->athlete?->contingent?->name ?? '-' }}
                                            </small>
                                        </td>
                                        <td>
                                            <small>
                                                {{ optional($performance->performed_at)->format('d M Y, H:i') ?: 'Belum tampil' }}
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $performance->status === 'finished' ? 'bg-success' : ($performance->status === 'performing' ? 'bg-warning text-dark' : 'bg-info-subtle text-info-emphasis') }}">
                                                {{ $performance->statusLabel() }}
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold">
                                            {{ $performance->final_score !== null ? number_format((float) $performance->final_score, 2) : '-' }}
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                @can('scores.view')
                                                    <a href="{{ route('admin.scores.edit', $performance) }}"
                                                        class="btn btn-outline-primary" title="Input nilai juri"
                                                        aria-label="Input nilai juri">
                                                        <i class="bi bi-clipboard2-data"></i>
                                                    </a>
                                                @endcan
                                                @can('performances.update')
                                                    @if ($performance->status === 'waiting')
                                                        <form action="{{ route('admin.performances.status', $performance) }}"
                                                            method="POST" class="d-inline js-confirm"
                                                            data-confirm-title="Mulai tampil?"
                                                            data-confirm-text="Status penampil diubah menjadi Tampil.">
                                                            @csrf
                                                            <input type="hidden" name="status" value="performing" />
                                                             <button type="submit" class="btn btn-outline-success" title="Mulai tampil"
                                                                 aria-label="Mulai tampil">
                                                                 <i class="bi bi-play-fill"></i>
                                                             </button>
                                                        </form>
                                                    @endif
                                                    @if ($performance->status !== 'finished')
                                                        <form action="{{ route('admin.performances.status', $performance) }}"
                                                            method="POST" class="d-inline js-confirm"
                                                            data-confirm-title="Selesaikan penampilan?"
                                                            data-confirm-text="Status penampil diubah menjadi Selesai.">
                                                            @csrf
                                                            <input type="hidden" name="status" value="finished" />
                                                             <button type="submit" class="btn btn-outline-secondary" title="Selesai tampil"
                                                                 aria-label="Selesai tampil">
                                                                 <i class="bi bi-check2"></i>
                                                             </button>
                                                        </form>
                                                    @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-body-secondary py-4">
                                            Belum ada penampil pada jadwal ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="card shadow-sm border-0">
                <div class="card-body text-center text-body-secondary py-5">
                    Tidak ada jadwal seni ditemukan.
                </div>
            </div>
        @endforelse
    </div>

    <!--begin::Tambah Penampil Modal-->
    <div class="modal fade" id="modal-add-performance" tabindex="-1" aria-labelledby="modal-add-performance-label"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.performances.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-add-performance-label">Tambah Penampil</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add-performance-schedule" class="form-label">Jadwal</label>
                            <select name="schedule_id" id="add-performance-schedule"
                                class="form-select @error('schedule_id') is-invalid @enderror" required>
                                <option value="">-- Pilih jadwal --</option>
                                @foreach ($schedules as $schedule)
                                    <option value="{{ $schedule->id }}" data-category="{{ $schedule->category_id }}"
                                        @selected((string) old('schedule_id') === (string) $schedule->id)>
                                        Partai {{ $schedule->match_no }} - {{ $schedule->category?->name ?? '-' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('schedule_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="add-performance-athlete" class="form-label">Atlet</label>
                            <select name="athlete_id" id="add-performance-athlete"
                                class="form-select @error('athlete_id') is-invalid @enderror" required>
                                <option value="">-- Pilih atlet --</option>
                            </select>
                            @error('athlete_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-0">
                            <label for="add-performance-order" class="form-label">No. Urut Tampil</label>
                            <input type="number" name="order_no" id="add-performance-order" min="1" max="999"
                                class="form-control @error('order_no') is-invalid @enderror"
                                value="{{ old('order_no', 1) }}" required />
                            @error('order_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
    <!--end::Tambah Penampil Modal-->
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const athletesByCategory = @json($athletesByCategory);
            const scheduleSelect = document.getElementById('add-performance-schedule');
            const athleteSelect = document.getElementById('add-performance-athlete');
            const orderInput = document.getElementById('add-performance-order');

            const fillAthletes = function(categoryId) {
                const current = athleteSelect.value;
                const list = athletesByCategory[categoryId] || [];

                athleteSelect.innerHTML = '<option value="">-- Pilih atlet --</option>' +
                    (list.length ? '' : '<option value="" disabled>Tidak ada atlet aktif</option>');

                list.forEach(function(athlete) {
                    const option = document.createElement('option');
                    option.value = athlete.id;
                    option.textContent = athlete.name + ' (' + athlete.contingent + ')';
                    athleteSelect.appendChild(option);
                });

                if (list.some(function(athlete) { return String(athlete.id) === String(current); })) {
                    athleteSelect.value = current;
                }
            };

            scheduleSelect.addEventListener('change', function() {
                const option = scheduleSelect.options[scheduleSelect.selectedIndex];
                fillAthletes(option ? option.dataset.category : '');
            });

            document.getElementById('modal-add-performance').addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                scheduleSelect.value = trigger.dataset.scheduleId || '';
                scheduleSelect.dispatchEvent(new Event('change'));
                orderInput.value = trigger.dataset.orderNo || 1;
            });

            @if ($errors->any() && old('schedule_id'))
                scheduleSelect.value = @json((string) old('schedule_id'));
                scheduleSelect.dispatchEvent(new Event('change'));
                athleteSelect.value = @json((string) old('athlete_id'));
                orderInput.value = @json((string) old('order_no', 1));
                new bootstrap.Modal(document.getElementById('modal-add-performance')).show();
            @endif

            document.querySelectorAll('form.js-confirm').forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    swalConfirm({
                        title: form.dataset.confirmTitle || 'Konfirmasi',
                        text: form.dataset.confirmText || 'Apakah Anda yakin?'
                    }).then(function(result) {
                        if (result.isConfirmed) HTMLFormElement.prototype.submit.call(form);
                    });
                });
            });
        });
    </script>
@endpush
