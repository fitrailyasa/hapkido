@extends('layouts.admin.app')

@section('title', 'Pertandingan Daeryun')

@section('content')
    <div class="container-fluid">
        <div class="card mb-4">
            <div class="card-header">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-4">
                        <h3 class="card-title mb-0">Daftar Pertandingan</h3>
                    </div>
                    <div class="col-12 col-md-8">
                        <form action="{{ route('admin.matches.index') }}" method="GET"
                            class="d-flex flex-wrap justify-content-md-end gap-2">
                            <select name="category" class="form-select form-select-sm w-auto"
                                onchange="this.form.submit()">
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
                            <select name="arena" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                <option value="">Semua arena</option>
                                @foreach ($arenas as $arena)
                                    <option value="{{ $arena->id }}" @selected($arenaFilter === (string) $arena->id)>{{ $arena->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            @can('brackets.view')
                                <a href="{{ route('admin.brackets.index') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-diagram-3 me-1"></i>Bracket
                                </a>
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
                                <th>Partai</th>
                                <th>Kategori / Ronde</th>
                                <th>Arena &amp; Jadwal</th>
                                <th>Atlet</th>
                                <th class="text-center">Skor</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($matches as $match)
                                @php
                                    $isBracket = isset($bracketRounds[$match->schedule?->category_id])
                                        && in_array($match->round, $bracketRounds[$match->schedule?->category_id] ?? [], true);
                                @endphp
                                <tr>
                                    <td>{{ $match->id }}</td>
                                    <td>
                                        <span class="badge bg-dark">{{ $match->schedule?->match_no ?? '-' }}</span>
                                        @if ($isBracket)
                                            <span class="badge bg-primary">Bracket</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-medium">{{ $match->schedule?->category?->name ?? '-' }}</div>
                                        <small class="text-body-secondary">{{ app(\App\Services\BracketService::class)->labelHuman($match->round) }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $match->schedule?->arena?->name ?? '-' }}</div>
                                        <small class="text-body-secondary">
                                            {{ optional($match->schedule?->match_date)->format('d M Y') }}
                                            &middot; {{ substr((string) ($match->schedule?->start_time ?? ''), 0, 5) ?: '-' }}
                                        </small>
                                    </td>
                                    <td>
                                        <div class="rounded p-2 bg-danger-subtle text-danger-emphasis d-flex align-items-center gap-2">
                                            <span class="badge bg-danger flex-shrink-0" aria-hidden="true">A</span>
                                            <span class="fw-medium text-truncate">
                                                {{ $match->athleteA?->name ?? 'Belum ditentukan' }}
                                            </span>
                                            @if ($match->athleteA?->contingent)
                                                <span class="badge bg-secondary flex-shrink-0">{{ $match->athleteA->contingent->code }}</span>
                                            @endif
                                        </div>
                                        <div class="text-center my-1">
                                            <span class="badge bg-body-secondary text-body border fw-bold">VS</span>
                                        </div>
                                        <div class="rounded p-2 bg-primary-subtle text-primary-emphasis d-flex align-items-center gap-2">
                                            <span class="badge bg-primary flex-shrink-0" aria-hidden="true">B</span>
                                            <span class="fw-medium text-truncate">
                                                {{ $match->athleteB?->name ?? 'Belum ditentukan' }}
                                            </span>
                                            @if ($match->athleteB?->contingent)
                                                <span class="badge bg-secondary flex-shrink-0">{{ $match->athleteB->contingent->code }}</span>
                                            @endif
                                        </div>
                                        @if ($match->winner)
                                            <div class="small text-success mt-1">
                                                <i class="bi bi-trophy-fill me-1"></i>Pemenang: {{ $match->winner->name }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center fs-6 fw-bold">{{ $match->scoreLabel() }}</td>
                                    <td>
                                        <span class="badge {{ $match->statusBadge() }}">{{ $match->statusLabel() }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.matches.show', $match) }}" class="btn btn-outline-info"
                                                title="Lihat detail" aria-label="Lihat detail partai">
                                                <i class="bi bi-eye"></i></a>
                                            @can('matches.start')
                                                @if ($match->status === 'pending')
                                                    <form action="{{ route('admin.matches.start', $match) }}" method="POST"
                                                        class="d-inline js-confirm"
                                                        data-confirm-title="Mulai pertandingan?"
                                                        data-confirm-text="Status partai akan diubah menjadi berlangsung.">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-success" title="Mulai"
                                                            aria-label="Mulai pertandingan">
                                                            <i class="bi bi-play-fill"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan
                                            @can('matches.result')
                                                @if ($match->status !== 'finished' && $match->athleteA && $match->athleteB)
                                                    <button type="button" class="btn btn-outline-primary" title="Selesaikan"
                                                        aria-label="Selesaikan pertandingan"
                                                        data-bs-toggle="modal" data-bs-target="#modal-finish-match"
                                                        data-finish-matchup="{{ $match->id }}"
                                                        data-url="{{ route('admin.matches.finish', $match) }}"
                                                        data-info="Partai {{ $match->schedule?->match_no ?? '-' }} &middot; {{ $match->schedule?->category?->name ?? '-' }} &middot; {{ app(\App\Services\BracketService::class)->labelHuman($match->round) }}"
                                                        data-athlete-a="{{ $match->athlete_a_id }}"
                                                        data-athlete-b="{{ $match->athlete_b_id }}"
                                                        data-name-a="{{ $match->athleteA?->name }}"
                                                        data-name-b="{{ $match->athleteB?->name }}"
                                                        data-score-a="{{ $match->score_a }}"
                                                        data-score-b="{{ $match->score_b }}"
                                                        data-bracket="{{ $isBracket ? '1' : '0' }}">
                                                        <i class="bi bi-flag-fill"></i>
                                                    </button>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-body-secondary py-4">
                                        Tidak ada pertandingan ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                {{ $matches->links('pagination::bootstrap-5') }}
            </div>
        </div>

        @include('admin.match.finish')
    </div>
@endsection

@push('styles')
    <style>
        /* Panel atlet: merah (A) vs biru (B) — tetap terbaca di tema terang & gelap. */
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
