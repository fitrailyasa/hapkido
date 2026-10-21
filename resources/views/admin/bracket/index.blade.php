@extends('layouts.admin.app')

@section('title', 'Bracket')

@section('content')
    <div class="container-fluid">
        <div class="card mb-4">
            <div class="card-header">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-5">
                        <h3 class="card-title mb-0">Bagan Pertandingan (Bracket)</h3>
                    </div>
                    <div class="col-12 col-md-7">
                        <form action="{{ route('admin.brackets.index') }}" method="GET"
                            class="d-flex flex-wrap justify-content-md-end gap-2">
                            <select name="category" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected($selected && $selected->id === $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @can('brackets.generate')
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#modal-generate-bracket" @disabled(!$selected)>
                                    <i class="bi bi-magic me-1"></i>Generate Bracket
                                </button>
                            @endcan
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if ($selected && auth()->user()?->can('brackets.generate'))
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-people me-2 text-primary"></i>Partai Manual
                        <small class="text-body-secondary">
                            (pilih pasangan dari kelas {{ $selected->name }} — tanpa wajib isi jadwal dulu)
                        </small>
                    </h6>
                </div>

                @if ($athleteCount < 2)
                    <div class="card-body text-center text-body-secondary">
                        Minimal 2 peserta aktif di kelas ini untuk membuat partai.
                    </div>
                @elseif ($manualPartai->isEmpty())
                    <div class="card-body text-center text-body-secondary">
                        Semua partai masuk sudah terisi atlet.
                        @if (! $hasBracket)
                            Atau pakai <strong>Generate Bracket</strong> untuk mengundi otomatis.
                        @endif
                    </div>
                @else
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @foreach ($manualPartai as $partai)
                                <form action="{{ route('admin.brackets.manual') }}" method="POST"
                                    class="list-group-item js-manual-partai">
                                    @csrf
                                    <input type="hidden" name="schedule_id" value="{{ $partai->id }}" />
                                    <div class="row g-2 align-items-center">
                                        <div class="col-12 col-md-2">
                                            <span class="fw-semibold">{{ $partai->match_no }}</span>
                                            <div class="small text-body-secondary">
                                                {{ \App\Models\Schedule::roundText($partai->round) }}
                                                @if ($partai->start_time)
                                                    &middot; {{ substr($partai->start_time, 0, 5) }}
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <select name="athlete_a_id"
                                                class="form-select form-select-sm js-manual-athlete" required>
                                                <option value="">— Pilih atlet —</option>
                                                @foreach ($athletes as $athlete)
                                                    @php $isUsed = $usedAthleteIds->contains($athlete->id); @endphp
                                                    <option value="{{ $athlete->id }}" @disabled($isUsed)
                                                        data-used="{{ $isUsed ? '1' : '0' }}">
                                                        {{ $athlete->name }} ({{ $athlete->participant_number }})
                                                        @if ($isUsed)
                                                            — sudah bertanding
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-1 text-center fw-bold text-body-secondary">VS</div>
                                        <div class="col-12 col-md-4">
                                            <select name="athlete_b_id"
                                                class="form-select form-select-sm js-manual-athlete">
                                                <option value="">— BYE (tanpa lawan) —</option>
                                                @foreach ($athletes as $athlete)
                                                    @php $isUsed = $usedAthleteIds->contains($athlete->id); @endphp
                                                    <option value="{{ $athlete->id }}" @disabled($isUsed)
                                                        data-used="{{ $isUsed ? '1' : '0' }}">
                                                        {{ $athlete->name }} ({{ $athlete->participant_number }})
                                                        @if ($isUsed)
                                                            — sudah bertanding
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-1 text-md-end">
                                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                                <i class="bi bi-check-lg me-1"></i>Simpan
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @if (! $selected)
            <div class="card shadow-sm border-0">
                <div class="card-body text-center text-body-secondary py-5">
                    Belum ada kelas Daeryun terdaftar.
                </div>
            </div>
        @elseif (! $hasBracket)
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <i class="bi bi-diagram-3 fs-1 text-body-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">Belum ada bracket untuk kelas {{ $selected->name }}</p>
                    <p class="text-body-secondary small mb-3">
                        Peserta aktif: {{ $athleteCount }} orang.<br />
                        Isi <strong>Partai Manual</strong> di atas, atau generate otomatis — jadwal dibuat belakangan.
                    </p>
                    @can('brackets.generate')
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#modal-generate-bracket">
                            <i class="bi bi-magic me-1"></i>Generate Bracket
                        </button>
                    @endcan
                </div>
            </div>
        @else
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="mb-0">
                        <i class="bi bi-trophy text-warning me-2"></i>{{ $selected->name }}
                        <span class="text-body-secondary small">({{ $athleteCount }} peserta aktif)</span>
                        @if ($champion)
                            <span class="badge bg-warning text-dark ms-2">
                                <i class="bi bi-trophy me-1"></i>Juara: {{ $champion['name'] }}
                            </span>
                        @endif
                    </h6>
                    @can('brackets.generate')
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#modal-generate-bracket" title="Generate ulang bracket">
                            <i class="bi bi-arrow-clockwise me-1"></i>Generate Ulang Bracket
                        </button>
                    @endcan
                </div>
            </div>

            @if (isset($prelimMatches) && $prelimMatches->isNotEmpty())
                @php
                    $prelimAthleteCount = $prelimMatches->sum(
                        fn ($m) => (int) (bool) $m->athlete_a_id + (int) (bool) $m->athlete_b_id
                    );
                @endphp
                <div class="card shadow-sm border-0 mb-3 border-warning">
                    <div class="card-header bg-warning-subtle">
                        <h6 class="mb-0">
                            <i class="bi bi-lightning me-2"></i>Partai Penyisihan
                            <small class="text-body-secondary">
                                — {{ $prelimAthleteCount }} peserta bertanding di sini; pemenang masuk slot kosong di bagan
                            </small>
                        </h6>
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach ($prelimMatches as $prelim)
                            <a href="{{ route('admin.matches.show', $prelim) }}"
                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span>
                                    <strong>{{ $prelim->schedule?->match_no }}</strong>
                                    &middot;
                                    {{ $prelim->athleteA?->name ?? '—' }}
                                    vs
                                    {{ $prelim->athleteB?->name ?? '—' }}
                                </span>
                                <span class="badge text-bg-{{ $prelim->status === 'finished' ? 'success' : 'secondary' }}">
                                    {{ $prelim->status === 'finished' ? 'Selesai' : 'Belum' }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body py-2 small text-body-secondary">
                    Klik kotak partai untuk membuka detail &middot;
                    <strong class="text-success">Nama hijau tebal</strong> = pemenang &middot;
                    Tulisan <em>Nama vs Nama</em> di bagan = pasangan penyisihan (belum ada pemenang).
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="bracket-scroll">
                        <div class="brackets-viewer"></div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!--begin::Generate Bracket Modal-->
    <div class="modal fade" id="modal-generate-bracket" tabindex="-1" aria-labelledby="modal-generate-bracket-label"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.brackets.generate') }}" method="POST" id="form-generate-bracket">
                    @csrf
                    <input type="hidden" name="category_id" value="{{ $selected?->id }}" />
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-generate-bracket-label">Generate Bracket</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">
                            Bracket akan dibuat otomatis untuk kelas
                            <strong>{{ $selected?->name ?? '-' }}</strong>
                            berdasarkan {{ $athleteCount }} peserta aktif.
                            Jadwal partai yang belum ada akan dibuat otomatis.
                        </p>
                        <div class="alert alert-warning small mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Bracket lama pada kelas ini akan dihapus lalu dibuat ulang.
                            Hasil pertandingan pada partai tersebut akan hilang. Jadwal yang sudah ada
                            (jam, arena, tanggal) tetap dipakai bila masih cocok.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-magic me-1"></i>Generate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!--end::Generate Bracket Modal-->
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/brackets/brackets-viewer.css') }}" />
    <style>
        .bracket-scroll {
            overflow-x: auto;
        }

        .brackets-viewer .match.bracket-click {
            cursor: pointer;
        }

        .brackets-viewer .match.bracket-bye .bracket-badge {
            position: absolute;
            top: -9px;
            right: -6px;
            z-index: 3;
            font-size: 0.62em;
            line-height: 1.5;
            padding: 0 7px;
            border-radius: 8px;
            background: #ffc107;
            color: #212529;
            font-weight: 700;
        }

        /* Dead bracket regions (no entrant) — hide so the bagan does not look hollow. */
        .brackets-viewer .match.bracket-empty {
            visibility: hidden;
            pointer-events: none;
        }

        /* Match in progress. */
        .brackets-viewer .match[data-match-status="3"] .opponents {
            border-color: #50b649;
            box-shadow: 0 0 0 1px #50b649;
        }

        /* Follow the app theme palette (dark blue) to stay consistent with the surrounding cards. */
        [data-bs-theme="dark"] .brackets-viewer {
            --primary-background: var(--bs-card-bg);
            --secondary-background: var(--bs-secondary-bg);
            --match-background: var(--bs-secondary-bg);
            --font-color: var(--bs-body-color);
            --border-color: var(--bs-border-color);
            --border-hover-color: #38496e;
            --border-selected-color: #4d94ff;
            --hint-color: var(--bs-tertiary-color);
            --label-color: var(--bs-secondary-color);
            --connector-color: #46567a;
        }

        [data-bs-theme="light"] .brackets-viewer {
            --secondary-background: var(--bs-secondary-bg);
            --border-color: var(--bs-border-color);
            --connector-color: #93a3c4;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('vendor/brackets/brackets-viewer.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('form-generate-bracket');
            if (form) {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    swalConfirm({
                        title: 'Generate bracket sekarang?',
                        text: 'Bracket lama pada kelas ini akan dihapus dan dibuat ulang.'
                    }).then(function(result) {
                        if (result.isConfirmed) HTMLFormElement.prototype.submit.call(form);
                    });
                });
            }

            document.querySelectorAll('.js-manual-partai').forEach(function(partaiForm) {
                const selects = partaiForm.querySelectorAll('.js-manual-athlete');
                if (selects.length !== 2) return;

                function syncManualAthletes() {
                    const selected = Array.from(selects).map(function(select) {
                        return select.value;
                    });

                    selects.forEach(function(select, index) {
                        const otherValue = selected[index === 0 ? 1 : 0];

                        Array.from(select.options).forEach(function(option) {
                            if (!option.value) return;

                            const used = option.dataset.used === '1';
                            option.disabled = used || (otherValue !== '' && option.value === otherValue);
                        });
                    });
                }

                selects.forEach(function(select) {
                    select.addEventListener('change', syncManualAthletes);
                });
                syncManualAthletes();

                partaiForm.addEventListener('submit', function(event) {
                    const athleteA = selects[0].value;
                    const athleteB = selects[1].value;

                    // athlete_b kosong = BYE; hanya atlet A yang wajib.
                    if (!athleteA) return;

                    if (athleteB && athleteA === athleteB) {
                        event.preventDefault();
                        swalError('Validasi gagal', 'Dua atlet yang dipilih tidak boleh sama.');
                        return;
                    }

                    const usedSelected = Array.from(selects).some(function(select) {
                        if (!select.value) return false;
                        const option = select.selectedOptions[0];
                        return option && option.dataset.used === '1';
                    });

                    if (usedSelected) {
                        event.preventDefault();
                        swalError('Validasi gagal', 'Salah satu atlet sudah bertanding di partai lain.');
                    }
                });
            });

            const data = @json($viewerData);
            const container = document.querySelector('.brackets-viewer');

            if (!data || !container) return;

            const roundLabels = @json($roundLabels);
            const matchUrls = @json($matchUrls);
            const byeIds = @json($byeIds);
            const emptyIds = @json($emptyIds);

            window.bracketsViewer.render(data, {
                clear: true,
                customRoundName: function(info) {
                    return roundLabels[info.roundNumber - 1] || ('Babak ' + info.roundNumber);
                },
                onMatchClick: function(match) {
                    const url = matchUrls[match.id] || matchUrls[String(match.id)];
                    if (url) window.location.href = url;
                }
            }).then(function() {
                byeIds.forEach(function(id) {
                    const el = container.querySelector('[data-match-id="' + id + '"]');
                    if (!el) return;

                    el.classList.add('bracket-bye');

                    const badge = document.createElement('span');
                    badge.className = 'bracket-badge';
                    badge.textContent = 'BYE';
                    badge.title = 'Peserta lolos otomatis tanpa lawan (BYE)';
                    el.appendChild(badge);
                });

                emptyIds.forEach(function(id) {
                    const el = container.querySelector('[data-match-id="' + id + '"]');
                    if (!el) return;

                    el.classList.add('bracket-empty');
                    el.title = 'Slot kosong - tidak ada peserta';
                });

                Object.keys(matchUrls).forEach(function(id) {
                    const el = container.querySelector('[data-match-id="' + id + '"]');
                    if (el) el.classList.add('bracket-click');
                });
            }).catch(function(error) {
                container.innerHTML = '<div class="alert alert-danger mb-0">Bagan gagal dimuat: ' +
                    (error && error.message ? error.message : error) + '</div>';
            });
        });
    </script>
@endpush
