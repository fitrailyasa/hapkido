@extends('layouts.admin.app')

@section('title', 'Dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row g-3" id="stat-cards">
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="bi bi-person-check-fill text-primary fs-4"></i>
                        </div>
                        <div>
                            <div class="fs-3 fw-bold" id="stat-verified">0</div>
                            <div class="text-muted small">Atlet Terdaftar Hadir</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="bi bi-clipboard-check-fill text-warning fs-4"></i>
                        </div>
                        <div>
                            <div class="fs-3 fw-bold" id="stat-ready">0</div>
                            <div class="text-muted small">Lolos Pemeriksaan</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="bi bi-broadcast-pin text-success fs-4"></i>
                        </div>
                        <div>
                            <div class="fs-3 fw-bold" id="stat-running">0</div>
                            <div class="text-muted small">Partai Berjalan</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                            <i class="bi bi-bell-fill text-danger fs-4"></i>
                        </div>
                        <div>
                            <div class="fs-3 fw-bold" id="stat-waiting">0</div>
                            <div class="text-muted small">Menunggu Calling</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-bell-fill text-danger me-2"></i>Calling Sekarang</h6>
                    </div>
                    <div class="card-body" id="calling-now">
                        <p class="text-muted mb-0">Tidak ada partai yang dipanggil.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-calendar-event-fill text-primary me-2"></i>Jadwal Hari Ini</h6>
                        @can('schedules.view')
                            <a href="{{ route('admin.schedules.index') }}" class="btn btn-sm btn-outline-primary">Lihat</a>
                        @endcan
                    </div>
                    <div class="card-body p-0" id="jadwal-list">
                        <div class="text-center text-muted py-4">Memuat jadwal...</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-broadcast-pin text-success me-2"></i>Antrean Calling</h6>
                        @can('callings.view')
                            <a href="{{ route('admin.callings.index') }}" class="btn btn-sm btn-outline-primary">Kelola</a>
                        @endcan
                    </div>
                    <div class="card-body p-0" id="calling-list">
                        <div class="text-center text-muted py-4">Memuat antrean...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-grid-3x3-gap-fill text-info me-2"></i>Status Arena</h6>
            </div>
            <div class="card-body">
                <div class="row g-3" id="arena-cards">
                    <div class="col-md-4 text-center text-muted py-4">Memuat arena...</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const statusBadge = function(status) {
            const map = {
                running: 'success',
                preparation: 'warning',
                finished: 'secondary',
                pending: 'info'
            };
            return map[status] || 'secondary';
        };

        const renderDashboard = function(data) {
            document.getElementById('stat-verified').textContent =
                data.counts.verification_present + ' / ' + data.counts.verification_total;
            document.getElementById('stat-ready').textContent =
                data.counts.readiness_ready + ' / ' + data.counts.readiness_total;
            document.getElementById('stat-running').textContent = data.counts.running_matches;
            document.getElementById('stat-waiting').textContent = data.counts.waiting_callings;

            // Calling sekarang
            const callingBox = document.getElementById('calling-now');
            if (data.active_match) {
                const m = data.active_match;
                const athleteA = m.athlete_a ? `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="fw-bold">${m.athlete_a.name}</div>
                            <small class="text-muted">${m.athlete_a.contingent}</small>
                        </div>
                        <span class="badge bg-primary">${m.athlete_a.contingent}</span>
                    </div>` : '<div class="text-muted">—</div>';
                const athleteB = m.athlete_b ? `
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">${m.athlete_b.name}</div>
                            <small class="text-muted">${m.athlete_b.contingent}</small>
                        </div>
                        <span class="badge bg-danger">${m.athlete_b.contingent}</span>
                    </div>` : `
                    <div class="text-center py-2">
                        <div class="fs-4 fw-bold text-primary">${(m.performances || []).length ? m.performances[0].athlete : 'Seni'}</div>
                    </div>`;
                callingBox.innerHTML = `
                    <div class="d-flex justify-content-between mb-2">
                        <span class="badge bg-dark">${m.match_no}</span>
                        <span class="badge bg-${statusBadge(m.status)}">${m.status_label}</span>
                    </div>
                    <div class="small text-muted mb-2">${m.category} &middot; ${m.type === 'art' ? 'Seni' : 'Daeryun'} &middot; ${m.arena}</div>
                    ${athleteA}
                    <div class="text-center my-1"><span class="badge bg-secondary">VS</span></div>
                    ${athleteB}`;
            } else {
                const next = data.next_match;
                callingBox.innerHTML = `<p class="text-muted mb-0">Tidak ada partai yang sedang berjalan.</p>` +
                    (next ? `<div class="mt-2 small">Partai berikutnya: <strong>${next.match_no}</strong> ${next.category} (Arena ${next.arena})</div>` : '');
            }

            // Jadwal hari ini
            let jadwalHtml = '';
            if (!data.jadwal.length) {
                jadwalHtml = '<div class="text-center text-muted py-4">Tidak ada jadwal hari ini.</div>';
            } else {
                data.jadwal.forEach(function(item) {
                    jadwalHtml += `
                        <div class="d-flex justify-content-between align-items-center border-bottom px-3 py-2">
                            <div>
                                <span class="fw-bold">${item.match_no}</span>
                                <span class="text-muted small ms-2">${item.time}</span>
                                <div class="small">${item.category} <span class="text-muted">(${item.arena})</span></div>
                            </div>
                            <span class="badge bg-${statusBadge(item.status)}">${item.status_label}</span>
                        </div>`;
                });
            }
            document.getElementById('jadwal-list').innerHTML = jadwalHtml;

            // Antrean calling
            let callingHtml = '';
            if (!data.callings.length) {
                callingHtml = '<div class="text-center text-muted py-4">Antrean kosong.</div>';
            } else {
                data.callings.forEach(function(item) {
                    callingHtml += `
                        <div class="d-flex justify-content-between align-items-center border-bottom px-3 py-2">
                            <div>
                                <div class="fw-semibold">${item.athlete} <span class="text-muted small">[${item.contingent}]</span></div>
                                <div class="small text-muted">${item.match_no ?? '—'} &middot; ${item.category} &middot; ${item.arena ?? '—'}</div>
                            </div>
                            <span class="badge bg-${item.status === 'called' ? 'danger' : 'secondary'}">${item.level_label}</span>
                        </div>`;
                });
            }
            document.getElementById('calling-list').innerHTML = callingHtml;

            // Arena cards
            let arenaHtml = '';
            data.arenas.forEach(function(arena) {
                const athletes = (arena.athletes || []).map(a => `
                    <div class="d-flex justify-content-between small">
                        <span>${a.name}</span><span class="text-muted">${a.contingent}</span>
                    </div>`).join('');
                arenaHtml += `
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>${arena.label}</strong>
                                <span class="badge bg-${statusBadge(arena.status)}">${arena.status_label}</span>
                            </div>
                            <div class="small text-muted mb-1">${arena.category} &middot; ${arena.match_no}</div>
                            <div class="small mb-2"><span class="badge bg-dark">${arena.match_status}</span></div>
                            ${athletes || '<div class="small text-muted">Belum ada partai</div>'}
                        </div>
                    </div>`;
            });
            document.getElementById('arena-cards').innerHTML = arenaHtml;
        };

        startPolling('{{ route('admin.dashboard.data') }}', AppConfig.pollInterval, renderDashboard);
    </script>
@endpush
