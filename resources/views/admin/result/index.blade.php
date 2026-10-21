@extends('layouts.admin.app')

@section('title', 'Hasil & Ranking')

@section('content')
    @php
        $medalLabels = ['gold' => 'Emas', 'silver' => 'Perak', 'bronze' => 'Perunggu'];
        // Bronze has its own colour (not the red danger state).
        $medalBadges = ['gold' => 'bg-warning text-dark', 'silver' => 'bg-secondary', 'bronze' => 'medal-bronze'];
        // The same medal emoji as the public display.
        $juaraEmojis = ['gold' => "\u{1F947}", 'silver' => "\u{1F948}", 'bronze' => "\u{1F949}"];

        // Indonesian label of the deciding match of a daeryun podium entry.
        $roundLabels = [
            'penyisihan' => 'Penyisihan',
            'perempat_final' => 'Perempat Final',
            'semifinal' => 'Semifinal',
            'final' => 'Final',
        ];

        $podiumSections = $daeryun->map(fn (array $section) => [
            'category' => $section['category'],
            'type' => 'daeryun',
            'arena' => $section['arena'],
            'podium' => $section['podium'],
        ])->concat($art->map(fn (array $section) => [
            'category' => $section['category'],
            'type' => 'art',
            'arena' => $section['arena'],
            'podium' => $section['podium'],
        ]))->values();
    @endphp
    <div class="container-fluid">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-trophy-fill text-warning me-2"></i>Juara Umum (Perolehan Medali)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 70px">Peringkat</th>
                                <th>Kontingen</th>
                                <th class="text-center">Emas</th>
                                <th class="text-center">Perak</th>
                                <th class="text-center">Perunggu</th>
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($juaraUmum as $row)
                                <tr>
                                    <td class="fw-bold">{{ $row['rank'] }}</td>
                                    <td>
                                        <div class="fw-medium">{{ $row['contingent']?->name ?? '-' }}</div>
                                        <small class="text-body-secondary">{{ $row['contingent']?->code ?? '-' }}</small>
                                    </td>
                                    <td class="text-center"><span class="badge {{ $medalBadges['gold'] }}">{{ $row['gold'] }}</span></td>
                                    <td class="text-center"><span class="badge {{ $medalBadges['silver'] }}">{{ $row['silver'] }}</span></td>
                                    <td class="text-center"><span class="badge {{ $medalBadges['bronze'] }}">{{ $row['bronze'] }}</span></td>
                                    <td class="text-center fw-semibold">{{ $row['total'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-body-secondary py-4">
                                        Belum ada medali yang diraih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-trophy me-2 text-warning"></i>Podium Juara per Kelas</h6>
            </div>
            <div class="card-body">
                @forelse ($podiumSections as $section)
                    @php
                        // Podium display order: 2 - 1 - 3 - 3 (the champion in the
                        // middle, highest). The third place always has two athletes.
                        $slots = [];
                        $bronzeIndex = 0;

                        foreach ($section['podium'] as $entry) {
                            $medal = $entry['medal'];
                            $isArt = $section['type'] === 'art';

                            if ($medal === 'bronze') {
                                $bronzeIndex++;
                                $position = 2 + $bronzeIndex;
                            } else {
                                $position = $medal === 'gold' ? 2 : 1;
                            }

                            // The value of a daeryun podium is the score of the
                            // deciding match (nilai akhir), never the total points.
                            $value = $isArt
                                ? ($entry['score'] !== null ? number_format((float) $entry['score'], 2) : '')
                                : ($entry['score_line'] ?? '');

                            $sub = $isArt
                                ? 'Nilai akhir seni'
                                : trim(($entry['result'] ?? '').' · '.($roundLabels[$entry['round'] ?? ''] ?? ''));

                            $slots[] = [
                                'position' => $position,
                                'rank' => $entry['rank'],
                                'medal' => $medal,
                                'emoji' => $juaraEmojis[$medal],
                                'name' => $entry['athlete']->name,
                                'contingent' => $entry['contingent']?->name ?? '-',
                                'code' => $entry['contingent']?->code ?? '',
                                'value' => $value,
                                'sub' => $sub,
                            ];
                        }

                        usort($slots, fn (array $a, array $b) => $a['position'] <=> $b['position']);
                    @endphp
                    <div class="{{ $loop->last ? '' : 'border-bottom pb-3 mb-3' }}">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                            <span class="fw-semibold">
                                <i class="bi bi-trophy me-1 text-warning"></i>{{ $section['category']->name }}
                            </span>
                            <span class="badge {{ $section['type'] === 'art' ? 'bg-warning text-dark' : 'bg-primary' }}">
                                {{ $section['type'] === 'art' ? 'Seni' : 'Daeryun' }}
                            </span>
                            <span class="ms-auto">
                                @include('admin.partials.display-button', ['arena' => $section['arena']])
                            </span>
                        </div>

                        @if (! $slots)
                            <p class="text-center text-body-secondary py-3 mb-0">Belum ada juara pada kelas ini.</p>
                        @else
                            <div class="row g-3 align-items-stretch podium-row">
                                @foreach ($slots as $slot)
                                    @php
                                        $orderClass = match ($slot['position']) {
                                            1 => 'order-2 order-md-1',
                                            2 => 'order-1 order-md-2',
                                            default => 'order-'.$slot['position'].' order-md-'.$slot['position'],
                                        };
                                    @endphp
                                    <div class="col-12 col-sm-6 col-lg-3 {{ $orderClass }}">
                                        <div class="juara-admin-card rank-{{ $slot['rank'] }}">
                                            <div class="juara-admin-medal">{{ $slot['emoji'] }}</div>
                                            <div class="juara-admin-rank">Juara {{ $slot['rank'] }}</div>
                                            <div class="juara-admin-name" title="{{ $slot['name'] }}">
                                                {{ $slot['name'] }}
                                            </div>
                                            <div class="juara-admin-cont" title="{{ $slot['contingent'] }}">
                                                @if ($slot['code'] !== '')
                                                    <span class="fw-semibold">{{ $slot['code'] }}</span> ·
                                                @endif
                                                {{ $slot['contingent'] }}
                                            </div>
                                            @if ($slot['value'] !== '')
                                                <div class="juara-admin-score">{{ $slot['value'] }}</div>
                                            @endif
                                            @if ($slot['sub'] !== '')
                                                <div class="juara-admin-sub">{{ $slot['sub'] }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-center text-body-secondary py-4 mb-0">Belum ada kelas untuk dipamerkan.</p>
                @endforelse
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-broadcast-pin text-success me-2"></i>Ranking Daeryun
                            <small class="text-body-secondary">(peringkat 1, 2, 3 dan 3 dari final &amp; semifinal)</small>
                        </h6>
                    </div>
                </div>
            </div>

            @forelse ($daeryun as $section)
                @php
                    $medalByAthlete = [];
                    foreach ($section['medals'] as $medal) {
                        $medalByAthlete[$medal['athlete_id']] = $medal['medal'];
                    }
                @endphp
                <div class="col-xl-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <h6 class="mb-0">{{ $section['category']->name }}</h6>
                            @include('admin.partials.display-button', ['arena' => $section['arena']])
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 110px">Peringkat</th>
                                            <th>Atlet</th>
                                            <th>Kontingen</th>
                                            <th class="text-center">Main</th>
                                            <th class="text-center">Menang</th>
                                            <th class="text-center">Poin</th>
                                            <th class="text-center">Selisih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($section['rows'] as $row)
                                            <tr>
                                                <td>
                                                    <span class="fw-bold me-1">{{ $row['rank'] }}.</span>
                                                    @if (isset($medalByAthlete[$row['athlete']->id]))
                                                        @php $medal = $medalByAthlete[$row['athlete']->id]; @endphp
                                                        <span class="badge {{ $medalBadges[$medal] ?? 'bg-secondary' }}">
                                                            {{ $medalLabels[$medal] ?? '' }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="fw-medium">{{ $row['athlete']->name }}</td>
                                                <td>{{ $row['contingent']?->code ?? '-' }}</td>
                                                <td class="text-center">{{ $row['played'] }}</td>
                                                <td class="text-center fw-semibold">{{ $row['wins'] }}</td>
                                                <td class="text-center">{{ $row['points_for'] }} - {{ $row['points_against'] }}</td>
                                                <td class="text-center">{{ $row['diff'] > 0 ? '+' : '' }}{{ $row['diff'] }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-body-secondary py-4">
                                                    Belum ada peserta pada kelas ini.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-secondary mb-0">Belum ada kelas Daeryun.</div>
                </div>
            @endforelse

            <div class="col-12 mt-2">
                <div class="card shadow-sm border-0">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-star-fill text-warning me-2"></i>Ranking Seni
                            <small class="text-body-secondary">(berdasarkan nilai akhir)</small>
                        </h6>
                    </div>
                </div>
            </div>

            @forelse ($art as $section)
                @php
                    $medalByAthlete = [];
                    foreach ($section['medals'] as $medal) {
                        $medalByAthlete[$medal['athlete_id']] = $medal['medal'];
                    }
                @endphp
                <div class="col-xl-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <h6 class="mb-0">{{ $section['category']->name }}</h6>
                            @include('admin.partials.display-button', ['arena' => $section['arena']])
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 110px">Peringkat</th>
                                            <th>Atlet</th>
                                            <th>Kontingen</th>
                                            <th>Partai</th>
                                            <th class="text-end">Nilai Akhir</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($section['rows'] as $row)
                                            <tr>
                                                <td>
                                                    <span class="fw-bold me-1">{{ $row['rank'] }}.</span>
                                                    @if (isset($medalByAthlete[$row['athlete']->id]))
                                                        @php $medal = $medalByAthlete[$row['athlete']->id]; @endphp
                                                        <span class="badge {{ $medalBadges[$medal] ?? 'bg-secondary' }}">
                                                            {{ $medalLabels[$medal] ?? '' }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="fw-medium">{{ $row['athlete']->name }}</td>
                                                <td>{{ $row['contingent']?->code ?? '-' }}</td>
                                                <td>{{ $row['schedule']->match_no ?? '-' }}</td>
                                                <td class="text-end fw-bold">
                                                    {{ $row['score'] !== null ? number_format($row['score'], 2) : '-' }}
                                                </td>
                                                <td>
                                                    <span class="badge {{ ($row['performance']->status ?? '') === 'finished' ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ $row['performance']?->statusLabel() ?? '-' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-body-secondary py-4">
                                                    Belum ada penampil dinilai pada kelas ini.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-secondary mb-0">Belum ada kelas Seni.</div>
                </div>
            @endforelse
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .podium-row {
            padding-top: 10px;
        }

        /* Juara card, the same look as the public display juara cards. */
        .juara-admin-card {
            height: 100%;
            background-color: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            border-top: 5px solid var(--bs-secondary);
            border-radius: 12px;
            padding: 1rem 0.75rem;
            text-align: center;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .juara-admin-card.rank-1 {
            border-top-color: #f0ad4e;
            background-image: linear-gradient(180deg, rgba(240, 173, 78, 0.18), transparent 60%);
        }

        .juara-admin-card.rank-2 {
            border-top-color: #c9ced6;
            background-image: linear-gradient(180deg, rgba(201, 206, 214, 0.25), transparent 60%);
        }

        .juara-admin-card.rank-3 {
            border-top-color: #cd7f32;
            background-image: linear-gradient(180deg, rgba(205, 127, 50, 0.18), transparent 60%);
        }

        .juara-admin-medal {
            font-size: 2.25rem;
            line-height: 1;
        }

        .juara-admin-rank {
            margin-top: 0.5rem;
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--bs-secondary-color);
        }

        .juara-admin-name {
            margin-top: 0.25rem;
            font-size: 1.25rem;
            font-weight: 800;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .juara-admin-cont {
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: var(--bs-secondary-color);
            overflow-wrap: anywhere;
        }

        .juara-admin-score {
            margin-top: 0.5rem;
            font-size: 1.0625rem;
            font-weight: 800;
        }

        .juara-admin-sub {
            margin-top: 0.25rem;
            font-size: 0.8125rem;
            color: var(--bs-secondary-color);
        }

        /* Bronze medal badge: bronze, not the red danger state. */
        .medal-bronze {
            background-color: #cd7f32;
            color: #fff;
        }
    </style>
@endpush
