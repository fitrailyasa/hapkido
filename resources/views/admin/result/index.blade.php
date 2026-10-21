@extends('layouts.admin.app')

@section('title', 'Hasil & Ranking')

@section('content')
    @php
        $medalLabels = ['gold' => 'Emas', 'silver' => 'Perak', 'bronze' => 'Perunggu'];
        // Bronze has its own colour (not the red danger state).
        $medalBadges = ['gold' => 'bg-warning text-dark', 'silver' => 'bg-secondary', 'bronze' => 'medal-bronze'];
        $medalSoft = [
            'gold' => 'bg-warning-subtle text-warning-emphasis',
            'silver' => 'bg-secondary-subtle text-secondary-emphasis',
            'bronze' => 'medal-bronze-soft',
        ];
        $medalIcons = ['gold' => 'bi-trophy-fill', 'silver' => 'bi-award-fill', 'bronze' => 'bi-award'];

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

                            $slots[] = [
                                'position' => $position,
                                'rank' => $entry['rank'],
                                'medal' => $medal,
                                'label' => $medalLabels[$medal],
                                'badge' => $medalBadges[$medal],
                                'soft' => $medalSoft[$medal],
                                'icon' => $medalIcons[$medal],
                                'name' => $entry['athlete']->name,
                                'contingent' => $entry['contingent']?->name ?? '-',
                                'code' => $entry['contingent']?->code ?? '-',
                                'value' => $isArt
                                    ? ($entry['score'] !== null ? number_format((float) $entry['score'], 2) : '-')
                                    : (string) $entry['wins'],
                                'valueLabel' => $isArt ? 'Nilai akhir' : 'Menang',
                                'sub' => $isArt ? 'Seni' : $entry['points_for'] . ' poin',
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
                        </div>

                        @if (! $slots)
                            <p class="text-center text-body-secondary py-3 mb-0">Belum ada penampil pada kelas ini.</p>
                        @else
                            <div class="row g-2 align-items-end podium-row">
                                @foreach ($slots as $slot)
                                    @php
                                        $isFirst = $slot['rank'] === 1;
                                        $orderClass = match ($slot['position']) {
                                            1 => 'order-2 order-md-1',
                                            2 => 'order-1 order-md-2',
                                            default => 'order-' . $slot['position'] . ' order-md-' . $slot['position'],
                                        };
                                    @endphp
                                    <div class="col-12 col-sm-6 col-lg-3 {{ $orderClass }}">
                                        <div class="card shadow-sm border-0 h-100 text-center {{ $isFirst ? 'podium-first' : '' }}">
                                            <div class="card-body {{ $isFirst ? 'py-4' : 'py-3' }}">
                                                <span class="badge {{ $slot['badge'] }}">
                                                    {{ $slot['label'] }}
                                                </span>
                                                <div class="podium-medal mx-auto my-2 {{ $slot['soft'] }}">
                                                    <i class="bi {{ $slot['icon'] }} {{ $isFirst ? 'fs-3' : 'fs-5' }}"></i>
                                                </div>
                                                <div class="{{ $isFirst ? 'fs-5' : 'fs-6' }} fw-bold text-truncate"
                                                    title="{{ $slot['name'] }}">
                                                    {{ $slot['name'] }}
                                                </div>
                                                <div class="small text-body-secondary text-truncate"
                                                    title="{{ $slot['contingent'] }}">
                                                    {{ $slot['contingent'] }} ({{ $slot['code'] }})
                                                </div>
                                                <div class="mt-2 pt-2 border-top">
                                                    <div class="fs-5 fw-bold">
                                                        {{ $slot['value'] }}
                                                    </div>
                                                    <div class="small text-body-secondary">
                                                        {{ $slot['valueLabel'] }} &middot; {{ $slot['sub'] }}
                                                    </div>
                                                </div>
                                            </div>
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
                        <div class="card-header">
                            <h6 class="mb-0">{{ $section['category']->name }}</h6>
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
                        <div class="card-header">
                            <h6 class="mb-0">{{ $section['category']->name }}</h6>
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

        .podium-medal {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .podium-first {
            transform: translateY(-10px);
        }
    </style>
@endpush
