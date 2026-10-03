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

        @if (! $selected)
            <div class="card shadow-sm border-0">
                <div class="card-body text-center text-body-secondary py-5">
                    Belum ada kategori Daeryun terdaftar.
                </div>
            </div>
        @elseif (! $hasBracket)
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <i class="bi bi-diagram-3 fs-1 text-body-secondary"></i>
                    <p class="mt-3 mb-1 fw-semibold">Belum ada bracket untuk kategori {{ $selected->name }}</p>
                    <p class="text-body-secondary small mb-3">
                        Peserta aktif: {{ $athleteCount }} orang. Minimal 2 peserta untuk membuat bracket.
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

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body py-2 small text-body-secondary">
                    Klik kotak partai untuk membuka detail &middot;
                    <strong class="text-success">Nama hijau tebal</strong> = pemenang &middot;
                    <span class="badge bg-warning text-dark">BYE</span> = peserta lolos tanpa lawan &middot;
                    Kotak meredup = slot kosong (tidak ada peserta).
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
                            Bracket akan dibuat otomatis untuk kategori
                            <strong>{{ $selected?->name ?? '-' }}</strong>
                            berdasarkan {{ $athleteCount }} peserta aktif.
                        </p>
                        <div class="alert alert-warning small mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Bracket lama pada kategori ini akan dihapus dan dibuat ulang, termasuk partai yang sudah
                            dihasilkannya.
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

        .brackets-viewer .match.bracket-empty .opponents {
            opacity: 0.45;
        }

        /* Partai yang sedang berlangsung. */
        .brackets-viewer .match[data-match-status="3"] .opponents {
            border-color: #50b649;
            box-shadow: 0 0 0 1px #50b649;
        }

        /* Ikut palet tema aplikasi (biru gelap) agar konsisten dengan kartu di sekitarnya. */
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
                        text: 'Bracket lama pada kategori ini akan dihapus dan dibuat ulang.'
                    }).then(function(result) {
                        if (result.isConfirmed) HTMLFormElement.prototype.submit.call(form);
                    });
                });
            }

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
