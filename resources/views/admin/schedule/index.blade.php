@extends('layouts.admin.app')

@section('title', 'Jadwal Pertandingan')

@section('content')
    <div class="container-fluid">
        <div class="card mb-4">
            <div class="card-header">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-3">
                        <h3 class="card-title mb-0">Jadwal Pertandingan</h3>
                    </div>
                    <div class="col-12 col-md-9">
                        <form action="{{ route('admin.schedules.index') }}" method="GET"
                            class="d-flex flex-wrap justify-content-md-end gap-2">
                            <div class="input-group input-group-sm w-auto">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="search" name="q" value="{{ $q }}" class="form-control"
                                    placeholder="Cari kode laga" style="width: 150px" />
                            </div>
                            <input type="date" name="date" value="{{ $dateFilter }}"
                                class="form-control form-control-sm w-auto" title="Filter tanggal" />
                            <select name="arena" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                <option value="">Semua arena</option>
                                @foreach ($arenas as $arena)
                                    <option value="{{ $arena->id }}" @selected($arenaFilter == $arena->id)>
                                        {{ $arena->name }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="category" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                <option value="">Semua kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected($categoryFilter == $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                <option value="">Semua status</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected($statusFilter === $status)>
                                        {{ \App\Models\Schedule::statusText($status) }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            @can('schedules.create')
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#modal-add-schedule">
                                    <i class="bi bi-plus-lg me-1"></i>Tambah Jadwal
                                </button>
                            @endcan
                            @can('schedules.export')
                                <a href="{{ route('admin.schedules.export', request()->query()) }}"
                                    class="btn btn-sm btn-outline-secondary" title="Export jadwal ke Excel">
                                    <i class="bi bi-download me-1"></i>Export
                                </a>
                            @endcan
                            @can('schedules.import')
                                <a href="{{ route('admin.schedules.template') }}" class="btn btn-sm btn-outline-secondary"
                                    title="Unduh template jadwal">
                                    <i class="bi bi-download me-1"></i>Template
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                                    data-bs-target="#modal-import-schedule" title="Import jadwal dari Excel">
                                    <i class="bi bi-upload me-1"></i>Import
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
                                <th style="width: 110px">Kode Laga</th>
                                <th style="width: 130px">Jam</th>
                                <th>Kategori</th>
                                <th>Arena</th>
                                <th>Jenis</th>
                                <th>Ronde</th>
                                <th style="width: 70px">Urutan</th>
                                <th style="width: 110px">Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($schedules->groupBy(fn ($schedule) => optional($schedule->match_date)->format('d M Y')) as $dateLabel => $group)
                                <tr class="table-secondary">
                                    <td colspan="9" class="text-body-secondary fw-semibold">
                                        <i class="bi bi-calendar-event me-1"></i>{{ $dateLabel }}
                                    </td>
                                </tr>
                                @foreach ($group as $schedule)
                                    <tr>
                                        <td class="fw-medium">{{ $schedule->match_no }}</td>
                                        <td>
                                            {{ $schedule->start_time ? substr($schedule->start_time, 0, 5) : '—' }}
                                            @if ($schedule->start_time && $schedule->end_time)
                                                <span class="text-body-secondary">–</span>
                                                {{ substr($schedule->end_time, 0, 5) }}
                                            @endif
                                        </td>
                                        <td>{{ $schedule->category?->name ?? '—' }}</td>
                                        <td>{{ $schedule->arena?->name ?? '—' }}</td>
                                        <td>
                                            <span
                                                class="badge {{ $schedule->type === 'art' ? 'bg-warning text-dark' : 'bg-primary' }}">
                                                {{ $schedule->type === 'art' ? 'Seni' : 'Daeryun' }}
                                            </span>
                                        </td>
                                        <td>{{ $schedule->round }}</td>
                                        <td>{{ $schedule->order_no }}</td>
                                        <td>
                                            <span
                                                class="badge {{ ['pending' => 'bg-secondary', 'preparation' => 'bg-warning text-dark', 'running' => 'bg-info-subtle text-info-emphasis', 'finished' => 'bg-success', 'cancelled' => 'bg-dark'][$schedule->status] ?? 'bg-secondary' }}">
                                                {{ $schedule->statusLabel() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-info" title="Lihat detail"
                                                    aria-label="Lihat detail jadwal"
                                                    data-show-url="{{ route('admin.schedules.show', $schedule) }}">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                @can('schedules.update')
                                                    <button type="button" class="btn btn-outline-secondary" title="Ubah"
                                                        aria-label="Ubah jadwal" data-bs-toggle="modal"
                                                        data-bs-target="#modal-edit-schedule"
                                                        data-url="{{ route('admin.schedules.update', $schedule) }}"
                                                        data-match_no="{{ $schedule->match_no }}"
                                                        data-category="{{ $schedule->category_id }}"
                                                        data-arena="{{ $schedule->arena_id }}"
                                                        data-type="{{ $schedule->type }}" data-round="{{ $schedule->round }}"
                                                        data-order_no="{{ $schedule->order_no }}"
                                                        data-date="{{ $schedule->match_date?->format('Y-m-d') }}"
                                                        data-start="{{ $schedule->start_time ? substr($schedule->start_time, 0, 5) : '' }}"
                                                        data-end="{{ $schedule->end_time ? substr($schedule->end_time, 0, 5) : '' }}"
                                                        data-status="{{ $schedule->status }}">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('schedules.delete')
                                                    <button type="button" class="btn btn-outline-danger" title="Hapus"
                                                        aria-label="Hapus jadwal" data-bs-toggle="modal"
                                                        data-bs-target="#modal-delete-schedule"
                                                        data-url="{{ route('admin.schedules.delete', $schedule) }}"
                                                        data-name="{{ $schedule->match_no }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-body-secondary py-4">
                                        Tidak ada jadwal ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                {{ $schedules->links('pagination::bootstrap-5') }}
            </div>
        </div>

        @include('admin.schedule.create')
        @include('admin.schedule.show')
        @include('admin.schedule.edit')
        @include('admin.schedule.delete')
        @include('admin.schedule.import')
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModalEl = document.getElementById('modal-edit-schedule');
            const deleteModalEl = document.getElementById('modal-delete-schedule');

            editModalEl.addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                const form = editModalEl.querySelector('form');
                form.setAttribute('action', trigger.dataset.url);
                form.querySelector('[name="match_no"]').value = trigger.dataset.match_no || '';
                form.querySelector('[name="category_id"]').value = trigger.dataset.category || '';
                form.querySelector('[name="arena_id"]').value = trigger.dataset.arena || '';
                form.querySelector('[name="type"]').value = trigger.dataset.type || 'daeryun';
                form.querySelector('[name="round"]').value = trigger.dataset.round || '';
                form.querySelector('[name="order_no"]').value = trigger.dataset.order_no || '';
                form.querySelector('[name="match_date"]').value = trigger.dataset.date || '';
                form.querySelector('[name="start_time"]').value = trigger.dataset.start || '';
                form.querySelector('[name="end_time"]').value = trigger.dataset.end || '';
                form.querySelector('[name="status"]').value = trigger.dataset.status || 'pending';
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
                    fetch(button.dataset.showUrl, {
                            headers: {
                                Accept: 'application/json'
                            }
                        })
                        .then(function(response) {
                            if (!response.ok) throw new Error('Not found');
                            return response.json();
                        })
                        .then(function(schedule) {
                            const modal = document.getElementById('modal-show-schedule');
                            modal.querySelector('[data-field="match_no"]').textContent =
                                schedule.match_no;
                            modal.querySelector('[data-field="category"]').textContent =
                                schedule.category;
                            modal.querySelector('[data-field="arena"]').textContent = schedule
                                .arena;
                            modal.querySelector('[data-field="type"]').textContent = schedule
                                .type_label;
                            modal.querySelector('[data-field="round"]').textContent = schedule
                                .round;
                            modal.querySelector('[data-field="order_no"]').textContent =
                                schedule.order_no;
                            modal.querySelector('[data-field="match_date"]').textContent =
                                schedule.match_date;
                            modal.querySelector('[data-field="time"]').textContent = (schedule
                                    .start_time || '—') +
                                (schedule.end_time ? ' – ' + schedule.end_time : '');
                            modal.querySelector('[data-field="status"]').textContent = schedule
                                .status_label;
                            modal.querySelector('[data-field="athletes"]').textContent =
                                schedule.athletes.length ?
                                schedule.athletes.map(function(item) {
                                    return item.name + ' (' + item.contingent + ')';
                                }).join(', ') : '—';

                            new bootstrap.Modal(modal).show();
                        })
                        .catch(function() {
                            swalError('Gagal', 'Detail jadwal tidak dapat dimuat.');
                        });
                });
            });

            @if ($errors->any())
                const errorModal = document.getElementById(
                    @json(
                        $errors->has('file')
                            ? 'modal-import-schedule'
                            : (old('schedule_id')
                                ? 'modal-edit-schedule'
                                : 'modal-add-schedule'))
                );
                new bootstrap.Modal(errorModal).show();
            @endif
        });
    </script>
@endpush
