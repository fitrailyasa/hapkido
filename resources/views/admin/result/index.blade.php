@extends('layouts.admin.app')

@section('title', 'Hasil & Ranking')

@section('content')
    @php
        $medalLabels = ['gold' => 'Emas', 'silver' => 'Perak', 'bronze' => 'Perunggu'];
        $medalBadges = ['gold' => 'bg-warning text-dark', 'silver' => 'bg-secondary', 'bronze' => 'bg-danger'];
        $medalSoft = [
            'gold' => 'bg-warning-subtle text-warning-emphasis',
            'silver' => 'bg-secondary-subtle text-secondary-emphasis',
            'bronze' => 'bg-danger-subtle text-danger-emphasis',
        ];
        $medalIcons = ['gold' => 'bi-trophy-fill', 'silver' => 'bi-award-fill', 'bronze' => 'bi-award'];
        $medalByRank = [1 => 'gold', 2 => 'silver', 3 => 'bronze'];

        $podiumSections = $daeryun->map(fn (array $section) => [
            'category' => $section['category'],
            'type' => 'daeryun',
            'rows' => $section['rows'],
        ])->concat($art->map(fn (array $section) => [
            'category' => $section['category'],
            'type' => 'art',
            'rows' => $section['rows'],
        ]))->values();

        // Urutan tampil podium: 2 - 1 - 3 (juara 1 di tengah, lebih tinggi).
        $podiumOrder = [
            ['rank' => 2, 'pos' => 'order-2 order-md-1'],
            ['rank' => 1, 'pos' => 'order-1 order-md-2'],
            ['rank' => 3, 'pos' => 'order-3 order-md-3'],
        ];
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
                <h6 class="mb-0"><i class="bi bi-trophy me-2 text-warning"></i>Podium Juara per Kategori</h6>
            </div>
            <div class="card-body">
                @forelse ($podiumSections as $section)
                    @php
                        $slots = [];
                        foreach (array_slice($section['rows'], 0, 3) as $row) {
                            $rank = $row['rank'] ?? 0;
                            if ($rank > 3 || $rank < 1 || empty($row['athlete'])) {
                                continue;
                            }

                            $medal = $medalByRank[$rank];
                            $isArt = $section['type'] === 'art';
                            $slots[$rank] = [
                                'medal' => $medal,
                                'label' => $medalLabels[$medal],
                                'badge' => $medalBadges[$medal],
                                'soft' => $medalSoft[$medal],
                                'icon' => $medalIcons[$medal],
                                'name' => $row['athlete']->name,
                                'contingent' => $row['contingent']?->name ?? '-',
                                'code' => $row['contingent']?->code ?? '-',
                                'value' => $isArt
                                    ? ($row['score'] !== null ? number_format((float) $row['score'], 2) : '-')
                                    : (string) $row['wins'],
                                'valueLabel' => $isArt ? 'Nilai akhir' : 'Menang',
                                'sub' => $isArt
                                    ? 'Tiga juri'
                                    : $row['points_for'] . ' poin',
                            ];
                        }
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
                            <p class="text-center text-body-secondary py-3 mb-0">Belum ada penampil pada kategori ini.</p>
                        @else
                            <div class="row g-2 align-items-end podium-row">
                                @foreach ($podiumOrder as $step)
                                    @php
                                        $slot = $slots[$step['rank']] ?? null;
                                        $isFirst = $step['rank'] === 1;
                                    @endphp
                                    <div class="col-12 col-sm-4 {{ $step['pos'] }}">
                                        <div class="card shadow-sm border-0 h-100 text-center {{ $isFirst ? 'podium-first' : '' }}">
                                            <div class="card-body {{ $isFirst ? 'py-4' : 'py-3' }}">
                                                <span class="badge {{ $slot['badge'] ?? 'bg-body-secondary text-body' }}">
                                                    {{ $slot['label'] ?? 'Juara ' . $step['rank'] }}
                                                </span>
                                                <div
                                                    class="podium-medal mx-auto my-2 {{ $slot['soft'] ?? 'bg-body-secondary text-body' }}">
                                                    <i class="bi {{ $slot['icon'] ?? 'bi-dash-lg' }} {{ $isFirst ? 'fs-3' : 'fs-5' }}"></i>
                                                </div>
                                                <div class="{{ $isFirst ? 'fs-5' : 'fs-6' }} fw-bold text-truncate"
                                                    title="{{ $slot['name'] ?? '' }}">
                                                    {{ $slot['name'] ?? '—' }}
                                                </div>
                                                <div class="small text-body-secondary text-truncate"
                                                    title="{{ $slot['contingent'] ?? '' }}">
                                                    {{ $slot['contingent'] ?? '-' }} ({{ $slot['code'] ?? '-' }})
                                                </div>
                                                <div class="mt-2 pt-2 border-top">
                                                    <div class="fs-5 fw-bold">
                                                        {{ $slot['value'] ?? '—' }}
                                                    </div>
                                                    <div class="small text-body-secondary">
                                                        {{ $slot['valueLabel'] ?? '' }}
                                                        @if ($slot)
                                                            &middot; {{ $slot['sub'] }}
                                                        @endif
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
                    <p class="text-center text-body-secondary py-4 mb-0">Belum ada kategori untuk dipamerkan.</p>
                @endforelse
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-broadcast-pin text-success me-2"></i>Ranking Daeryun
                            <small class="text-body-secondary">(menang &rarr; selisih poin &rarr; total poin)</small>
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
                                                    Belum ada peserta pada kategori ini.
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
                    <div class="alert alert-secondary mb-0">Belum ada kategori Daeryun.</div>
                </div>
            @endforelse

            <div class="col-12 mt-2">
                <div class="card shadow-sm border-0">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-star-fill text-warning me-2"></i>Ranking Seni
                            <small class="text-body-secondary">(berdasarkan nilai akhir tiga juri)</small>
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
                                                    Belum ada penampil dinilai pada kategori ini.
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
                    <div class="alert alert-secondary mb-0">Belum ada kategori Seni.</div>
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
