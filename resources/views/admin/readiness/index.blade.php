@extends('layouts.admin.app')

@section('title', 'Pemeriksaan Kesiapan')

@section('content')
    <div class="container-fluid">
        <div class="card mb-3">
            <div class="card-header">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-6">
                        <h3 class="card-title mb-0">Pemeriksaan Kesiapan Bertanding</h3>
                        <small class="text-body-secondary">Jadwal hari ini {{ $date }}</small>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="d-flex flex-wrap justify-content-md-end gap-2">
                            <span class="badge bg-success fs-6">Siap {{ $totals['ready'] }}</span>
                            <span class="badge bg-warning text-dark fs-6">Menunggu {{ $totals['pending'] }}</span>
                            <span class="badge bg-dark fs-6">Total {{ $totals['total'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div id="readiness-content">
                @if (empty($groups))
                    <div class="text-center text-body-secondary py-5">
                        <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                        Tidak ada jadwal pemeriksaan hari ini.
                    </div>
                @else
                    @foreach ($groups as $group)
                        <div class="border-bottom">
                            <div
                                class="bg-body-tertiary px-3 py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <span class="fw-semibold">
                                        <i class="bi bi-grid-3x3-gap-fill me-1"></i>{{ $group['label'] }}
                                    </span>
                                    <span class="text-body-secondary small ms-1">({{ $group['name'] }})</span>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge bg-success">Siap {{ $group['counts']['ready'] }}</span>
                                    <span class="badge bg-warning text-dark">Menunggu
                                        {{ $group['counts']['pending'] }}</span>
                                </div>
                            </div>

                            @foreach ($group['schedules'] as $item)
                                @php($schedule = $item['schedule'])
                                <div class="px-3 pt-3">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="badge bg-dark">{{ $schedule->match_no }}</span>
                                            <strong>{{ $schedule->category?->name ?? '—' }}</strong>
                                            <span class="text-body-secondary small">
                                                {{ $schedule->type === 'art' ? 'Seni' : 'Daeryun' }}
                                                &middot; {{ substr((string) $schedule->start_time, 0, 5) }}
                                                &middot; {{ $schedule->arena?->label ?? 'Tanpa Arena' }}
                                            </span>
                                            <span
                                                class="badge bg-secondary">{{ $item['counts']['ready'] }}/{{ $item['counts']['total'] }}
                                                siap</span>
                                        </div>
                                        @can('readiness.update')
                                            <button type="button" class="btn btn-sm btn-success"
                                                data-ready-url="{{ route('admin.readiness.mark-ready', $schedule) }}"
                                                data-match-no="{{ $schedule->match_no }}">
                                                <i class="bi bi-check-all me-1"></i>Tandai Semua Siap
                                            </button>
                                        @endcan
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width: 70px">No. Urut</th>
                                                    <th>Atlet</th>
                                                    <th>Kontingen</th>
                                                    <th class="text-center" style="width: 90px">Hadir</th>
                                                    <th class="text-center" style="width: 90px">Atlet</th>
                                                    <th class="text-center" style="width: 110px">Perlengkapan</th>
                                                    <th style="width: 110px">Status</th>
                                                    <th>Catatan</th>
                                                    @can('readiness.update')
                                                        <th class="text-end" style="width: 150px">Aksi</th>
                                                    @endcan
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($item['rows'] as $row)
                                                    @php($check = $row['check'])
                                                    <tr class="{{ $check?->isReady() ? 'table-success' : '' }}">
                                                        <td class="text-body-secondary">
                                                            {{ $row['athlete']->participant_number }}</td>
                                                        <td class="text-body-secondary">
                                                            {{ $row['athlete']->name }}
                                                        </td>
                                                        <td><span class="badge bg-dark">{{ $row['contingent'] }}</span>
                                                        </td>
                                                        <td class="text-center">
                                                            @if ($check?->attendance_check)
                                                                <i class="bi bi-check2-square text-success fs-5"></i>
                                                            @else
                                                                <i class="bi bi-square text-body-secondary fs-5"></i>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if ($check?->athlete_check)
                                                                <i class="bi bi-check2-square text-success fs-5"></i>
                                                            @else
                                                                <i class="bi bi-square text-body-secondary fs-5"></i>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if ($check?->equipment_check)
                                                                <i class="bi bi-check2-square text-success fs-5"></i>
                                                            @else
                                                                <i class="bi bi-square text-body-secondary fs-5"></i>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($check?->isReady())
                                                                <span class="badge bg-success">Siap</span>
                                                            @else
                                                                <span class="badge bg-warning text-dark">Menunggu</span>
                                                            @endif
                                                        </td>
                                                        <td class="small text-body-secondary">{{ $check?->notes ?? '—' }}
                                                        </td>
                                                        @can('readiness.update')
                                                            <td class="text-end">
                                                                @if ($check)
                                                                    <button type="button"
                                                                        class="btn btn-sm {{ $check->isReady() ? 'btn-outline-secondary' : 'btn-success' }}"
                                                                        data-toggle-url="{{ route('admin.readiness.update', $check) }}"
                                                                        data-target-status="{{ $check->isReady() ? 'pending' : 'ready' }}"
                                                                        data-athlete="{{ $row['athlete']->name }}">
                                                                        @if ($check->isReady())
                                                                            <i
                                                                                class="bi bi-arrow-counterclockwise me-1"></i>Batalkan
                                                                        @else
                                                                            <i class="bi bi-check2-circle me-1"></i>Tandai Siap
                                                                        @endif
                                                                    </button>
                                                                @endif
                                                            </td>
                                                        @endcan
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="9" class="text-center text-body-secondary py-3">
                                                            Belum ada atlet pada partai ini.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const board = document.getElementById('readiness-content');
            if (!board) return;

            board.addEventListener('click', function(event) {
                const toggleButton = event.target.closest('[data-toggle-url]');
                const readyButton = event.target.closest('[data-ready-url]');

                if (toggleButton) {
                    const markReady = toggleButton.dataset.targetStatus === 'ready';
                    swalConfirm({
                        title: markReady ? 'Tandai Siap' : 'Batalkan Kesiapan',
                        text: markReady ?
                            'Tandai ' + toggleButton.dataset.athlete + ' SIAP bertanding?' :
                            'Kembalikan ' + toggleButton.dataset.athlete + ' ke status menunggu?'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            postForm(toggleButton.dataset.toggleUrl, {
                                status: toggleButton.dataset.targetStatus
                            });
                        }
                    });
                }

                if (readyButton) {
                    swalConfirm({
                        title: 'Tandai Semua Siap',
                        text: 'Tandai seluruh atlet partai ' + readyButton.dataset.matchNo +
                            ' siap bertanding?'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            postForm(readyButton.dataset.readyUrl, {});
                        }
                    });
                }
            });
        });
    </script>
@endpush
