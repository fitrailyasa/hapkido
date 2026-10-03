@extends('layouts.admin.app')

@section('title', 'Input Nilai Juri')

@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <a href="{{ route('admin.performances.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Seni
            </a>
            <span class="badge {{ $performance->status === 'finished' ? 'bg-success' : ($performance->status === 'performing' ? 'bg-warning text-dark' : 'bg-info-subtle text-info-emphasis') }}">
                {{ $performance->statusLabel() }}
            </span>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-person-badge text-primary me-2"></i>Data Penampil</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-body-secondary">Atlet</span>
                                <span class="fw-medium">{{ $performance->athlete?->name ?? '-' }}</span>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-body-secondary">Kontingen</span>
                                <span class="fw-medium">{{ $performance->athlete?->contingent?->name ?? '-' }}</span>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-body-secondary">Kategori</span>
                                <span class="fw-medium">{{ $performance->schedule?->category?->name ?? '-' }}</span>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-body-secondary">Partai</span>
                                <span class="fw-medium">{{ $performance->schedule?->match_no ?? '-' }}</span>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-body-secondary">Arena</span>
                                <span class="fw-medium">{{ $performance->schedule?->arena?->name ?? '-' }}</span>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-body-secondary">No. urut</span>
                                <span class="fw-medium">{{ $performance->order_no }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-2">
                                <span class="text-body-secondary">Waktu tampil</span>
                                <span class="fw-medium">
                                    {{ optional($performance->performed_at)->format('d M Y, H:i') ?: '-' }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-clipboard2-data text-success me-2"></i>Nilai Tersimpan</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            @foreach ([1, 2, 3] as $judgeNo)
                                <li class="d-flex justify-content-between border-bottom py-2">
                                    <span class="text-body-secondary">Juri {{ $judgeNo }}</span>
                                    <span class="fw-semibold">
                                        {{ $judgeScores[$judgeNo] !== null ? number_format($judgeScores[$judgeNo], 2) : '-' }}
                                    </span>
                                </li>
                            @endforeach
                            <li class="d-flex justify-content-between pt-3">
                                <span class="fw-semibold">Nilai akhir</span>
                                <span class="fw-bold fs-5 text-primary" data-final-value>
                                    {{ $finalScore !== null ? number_format($finalScore, 2) : '-' }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-people text-warning me-2"></i>Nilai Tiga Juri</h6>
                    </div>
                    <div class="card-body">
                        @can('scores.create')
                            <form action="{{ route('admin.scores.store', $performance) }}" method="POST" id="form-judge-scores">
                                @csrf
                                <div class="row g-3">
                                    @foreach ([1, 2, 3] as $judgeNo)
                                        <div class="col-md-4">
                                            <label for="judge-{{ $judgeNo }}" class="form-label">Juri {{ $judgeNo }}</label>
                                            <input type="number" name="score_{{ $judgeNo }}" id="judge-{{ $judgeNo }}"
                                                class="form-control @error('score_' . $judgeNo) is-invalid @enderror"
                                                min="0" max="100" step="0.01" required
                                                value="{{ old('score_' . $judgeNo, $judgeScores[$judgeNo] ?? '') }}"
                                                data-judge />
                                            @error('score_' . $judgeNo)
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endforeach
                                </div>

                                <div class="alert alert-light border mt-4 mb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <span class="text-body-secondary">Rata-rata tiga juri (nilai akhir):</span>
                                        <div class="fs-4 fw-bold text-primary" data-live-average>0.00</div>
                                        <small class="text-body-secondary">Dibulatkan hingga 2 desimal.</small>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-primary px-4 js-score-submit"
                                        title="Simpan nilai juri">
                                        <i class="bi bi-save me-1"></i>Simpan Nilai
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="alert alert-info mb-0">
                                Anda hanya memiliki hak melihat nilai juri.
                            </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = Array.from(document.querySelectorAll('[data-judge]'));
            const liveAverage = document.querySelector('[data-live-average]');
            const finalValue = document.querySelector('[data-final-value]');

            const recalculate = function() {
                const values = inputs
                    .map(function(input) { return parseFloat(input.value); })
                    .filter(function(value) { return !isNaN(value); });

                if (!values.length) {
                    liveAverage.textContent = '0.00';
                    return;
                }

                const average = values.reduce(function(total, value) { return total + value; }, 0) / values.length;
                liveAverage.textContent = average.toFixed(2);
            };

            inputs.forEach(function(input) { input.addEventListener('input', recalculate); });
            recalculate();

            const form = document.getElementById('form-judge-scores');

            if (form) {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    swalConfirm({
                        title: 'Simpan nilai juri?',
                        text: 'Nilai akhir dihitung dari rata-rata tiga juri dan penampil ditandai selesai.'
                    }).then(function(result) {
                        if (result.isConfirmed) HTMLFormElement.prototype.submit.call(form);
                    });
                });
            }

            @if ($errors->any())
                if (finalValue && !isNaN(parseFloat(finalValue.textContent))) {
                    recalculate();
                }
            @endif
        });
    </script>
@endpush
