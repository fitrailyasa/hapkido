@extends('layouts.admin.app')

@section('title', 'Detail Pertandingan')

@section('content')
    @php
        $isBracket = isset($bracketRounds[$match->schedule?->category_id])
            && in_array($match->round, $bracketRounds[$match->schedule?->category_id] ?? [], true);
        $scheduleStatusBadge = [
            'pending' => 'bg-info-subtle text-info-emphasis',
            'preparation' => 'bg-warning text-dark',
            'running' => 'bg-success',
            'finished' => 'bg-secondary',
            'cancelled' => 'bg-danger',
        ];
    @endphp
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <a href="{{ route('admin.matches.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
            <div class="d-flex flex-wrap gap-2">
                @can('matches.start')
                    @if ($match->status === 'pending')
                        <form action="{{ route('admin.matches.start', $match) }}" method="POST" class="d-inline js-confirm"
                            data-confirm-title="Mulai pertandingan?"
                            data-confirm-text="Status partai akan diubah menjadi berlangsung.">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success" title="Mulai pertandingan">
                                <i class="bi bi-play-fill me-1"></i>Mulai Pertandingan
                            </button>
                        </form>
                    @endif
                @endcan
                @can('matches.result')
                    @if ($match->status !== 'finished' && $match->athleteA && $match->athleteB)
                        <button type="button" class="btn btn-sm btn-primary" title="Selesaikan pertandingan"
                            data-bs-toggle="modal" data-bs-target="#modal-finish-match"
                            data-finish-matchup="{{ $match->id }}"
                            data-url="{{ route('admin.matches.finish', $match) }}"
                            data-info="Partai {{ $match->schedule?->match_no ?? '-' }} &middot; {{ $match->schedule?->category?->name ?? '-' }} &middot; {{ app(\App\Services\BracketService::class)->labelHuman($match->round) }}"
                            data-athlete-a="{{ $match->athlete_a_id }}" data-athlete-b="{{ $match->athlete_b_id }}"
                            data-name-a="{{ $match->athleteA?->name }}" data-name-b="{{ $match->athleteB?->name }}"
                            data-score-a="{{ $match->score_a }}" data-score-b="{{ $match->score_b }}"
                            data-bracket="{{ $isBracket ? '1' : '0' }}">
                            <i class="bi bi-flag-fill me-1"></i>Selesaikan Pertandingan
                        </button>
                    @endif
                @endcan
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h6 class="mb-0">
                            <i class="bi bi-broadcast-pin text-success me-2"></i>
                            Partai {{ $match->schedule?->match_no ?? '-' }}
                            &middot; {{ app(\App\Services\BracketService::class)->labelHuman($match->round) }}
                        </h6>
                        <span class="badge {{ $match->statusBadge() }}">{{ $match->statusLabel() }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center text-center g-3">
                            <div class="col-5">
                                <div class="rounded p-3 h-100 bg-danger-subtle text-danger-emphasis match-side match-side-a">
                                    <span class="badge bg-danger mb-2">Merah &middot; A</span>
                                    <div class="fw-bold fs-5">{{ $match->athleteA?->name ?? 'Belum ditentukan' }}</div>
                                    <div class="opacity-75 small mb-2">
                                        {{ $match->athleteA?->contingent?->name ?? '-' }}
                                    </div>
                                    @if ($match->athleteA?->contingent)
                                        <span class="badge bg-secondary">{{ $match->athleteA->contingent->code }}</span>
                                    @endif
                                    @if ($match->winner && (int) $match->winner_athlete_id === (int) $match->athlete_a_id)
                                        <div class="mt-2">
                                            <i class="bi bi-trophy-fill"></i>
                                            <span class="fw-semibold">Pemenang</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="fs-2 fw-bold">{{ $match->hasScore() ? $match->score_a : '-' }}</div>
                                <span class="badge bg-body-secondary text-body border fw-bold match-vs">VS</span>
                                <div class="fs-2 fw-bold">{{ $match->hasScore() ? $match->score_b : '-' }}</div>
                            </div>
                            <div class="col-5">
                                <div class="rounded p-3 h-100 bg-primary-subtle text-primary-emphasis match-side match-side-b">
                                    <span class="badge bg-primary mb-2">Biru &middot; B</span>
                                    <div class="fw-bold fs-5">{{ $match->athleteB?->name ?? 'Belum ditentukan' }}</div>
                                    <div class="opacity-75 small mb-2">
                                        {{ $match->athleteB?->contingent?->name ?? '-' }}
                                    </div>
                                    @if ($match->athleteB?->contingent)
                                        <span class="badge bg-secondary">{{ $match->athleteB->contingent->code }}</span>
                                    @endif
                                    @if ($match->winner && (int) $match->winner_athlete_id === (int) $match->athlete_b_id)
                                        <div class="mt-2">
                                            <i class="bi bi-trophy-fill"></i>
                                            <span class="fw-semibold">Pemenang</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if ($match->winner)
                            <div class="alert alert-success mt-3 mb-0 text-center">
                                Pemenang: <strong>{{ $match->winner->name }}</strong>
                                ({{ $match->winner?->contingent?->code ?? '-' }})
                                @if ($isBracket)
                                    &middot; Lolos ke babak berikutnya
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-bell-fill text-danger me-2"></i>Calling &amp; Verifikasi</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Atlet</th>
                                        <th>Calling</th>
                                        <th>Verifikasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $callings = $match->schedule?->callings ?? collect();
                                        $verifications = $match->schedule?->verifications ?? collect();
                                        $athleteIds = array_values(array_filter([$match->athlete_a_id, $match->athlete_b_id]));
                                    @endphp
                                    @forelse ($athleteIds as $athleteId)
                                        @php
                                            $calling = $callings->firstWhere('athlete_id', $athleteId);
                                            $verification = $verifications->firstWhere('athlete_id', $athleteId);
                                        @endphp
                                        <tr>
                                            <td>{{ $calling?->athlete?->name ?? $verification?->athlete?->name ?? '-' }}</td>
                                            <td>
                                                @if ($calling)
                                                    <span class="badge {{ $calling->status === 'called' ? 'bg-danger' : 'bg-secondary' }}">
                                                        {{ $calling->statusLabel() }}
                                                    </span>
                                                    <small class="text-body-secondary">{{ $calling->levelLabel() }}</small>
                                                @else
                                                    <span class="text-body-secondary">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($verification)
                                                    <span class="badge {{ $verification->status === 'present' ? 'bg-primary' : 'bg-danger' }}">
                                                        {{ $verification->status === 'present' ? 'Hadir' : 'Tidak hadir' }}
                                                    </span>
                                                    <small class="text-body-secondary">
                                                        {{ optional($verification->verified_at)->format('d M H:i') }}
                                                    </small>
                                                @else
                                                    <span class="text-body-secondary">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-body-secondary py-3">
                                                Belum ada data calling / verifikasi.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-calendar-event-fill text-primary me-2"></i>Informasi Jadwal</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-body-secondary">Kategori</span>
                                <span class="fw-medium">{{ $match->schedule?->category?->name ?? '-' }}</span>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-body-secondary">Jenis</span>
                                <span class="fw-medium">{{ $match->schedule?->isDaeryun() ? 'Daeryun' : 'Seni' }}</span>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-body-secondary">Ronde</span>
                                <span class="fw-medium">{{ app(\App\Services\BracketService::class)->labelHuman($match->round) }}</span>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-body-secondary">Posisi</span>
                                <span class="fw-medium">{{ $match->position }}</span>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-body-secondary">Arena</span>
                                <span class="fw-medium">{{ $match->schedule?->arena?->name ?? '-' }}</span>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-body-secondary">Tanggal</span>
                                <span class="fw-medium">{{ optional($match->schedule?->match_date)->format('d M Y') ?: '-' }}</span>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-body-secondary">Jam</span>
                                <span class="fw-medium">
                                    {{ substr((string) ($match->schedule?->start_time ?? ''), 0, 5) ?: '-' }}
                                    -
                                    {{ substr((string) ($match->schedule?->end_time ?? ''), 0, 5) ?: '-' }}
                                </span>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-body-secondary">Status jadwal</span>
                                <span class="badge {{ $scheduleStatusBadge[$match->schedule?->status] ?? 'bg-secondary' }}">
                                    {{ $match->schedule?->statusLabel() ?? '-' }}
                                </span>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-body-secondary">Mulai</span>
                                <span class="fw-medium">{{ optional($match->started_at)->format('d M Y, H:i') ?: '-' }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-2">
                                <span class="text-body-secondary">Selesai</span>
                                <span class="fw-medium">{{ optional($match->finished_at)->format('d M Y, H:i') ?: '-' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-diagram-3 text-info me-2"></i>Bracket</h6>
                    </div>
                    <div class="card-body">
                        @if ($isBracket)
                            <p class="small text-body-secondary mb-2">
                                Partai ini berasal dari bracket kategori
                                <strong>{{ $match->schedule?->category?->name ?? '-' }}</strong>.
                            </p>
                            @can('brackets.view')
                                <a href="{{ route('admin.brackets.index', ['category' => $match->schedule?->category_id]) }}"
                                    class="btn btn-sm btn-outline-info w-100">
                                    <i class="bi bi-diagram-3 me-1"></i>Lihat Bracket
                                </a>
                            @endcan
                        @else
                            <p class="small text-body-secondary mb-0">Partai ini dibuat manual melalui jadwal.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @include('admin.match.finish')
    </div>
@endsection

@push('styles')
    <style>
        /* Panel atlet: merah (A) vs biru (B) — tetap terbaca di tema terang & gelap. */
        .match-side {
            border: 1px solid var(--bs-border-color);
        }

        .match-side-a {
            border-color: rgba(220, 53, 69, 0.4);
        }

        .match-side-b {
            border-color: rgba(13, 111, 255, 0.4);
        }

        .match-vs {
            letter-spacing: 1px;
            padding-left: 0.7rem;
            padding-right: 0.7rem;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
