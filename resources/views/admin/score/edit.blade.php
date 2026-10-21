@extends('layouts.admin.app')

@section('title', 'Input Nilai Seni')

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
                                <span class="text-body-secondary">Kelas</span>
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
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span class="fw-semibold">Nilai akhir</span>
                                <span class="fw-bold fs-5 text-primary" data-final-value>
                                    {{ $finalScore !== null ? number_format($finalScore, 2) : '-' }}
                                </span>
                            </li>
                        </ul>

                        @if (collect($judgeScores)->filter(fn ($score) => $score !== null)->isNotEmpty())
                            <hr class="my-3">
                            <p class="small text-body-secondary mb-2">
                                Nilai juri tersimpan (referensi, tidak dipakai untuk nilai akhir):
                            </p>
                            <ul class="list-unstyled mb-0">
                                @foreach ($judgeScores as $judgeNo => $judgeScore)
                                    <li class="d-flex justify-content-between border-bottom py-2">
                                        <span class="text-body-secondary">Juri {{ $judgeNo }}</span>
                                        <span class="fw-semibold">
                                            {{ $judgeScore !== null ? number_format($judgeScore, 2) : '-' }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-award text-warning me-2"></i>Nilai Akhir Penampil</h6>
                    </div>
                    <div class="card-body">
                        @can('scores.create')
                            <form action="{{ route('admin.scores.store', $performance) }}" method="POST" id="form-final-score">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="final_score" class="form-label">
                                            Nilai akhir <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="final_score" id="final_score"
                                            class="form-control form-control-lg @error('final_score') is-invalid @enderror"
                                            min="0" max="100" step="0.01" required
                                            value="{{ old('final_score', $finalScore !== null ? number_format($finalScore, 2, '.', '') : '') }}" />
                                        @error('final_score')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">
                                            Satu nilai akhir (0&ndash;99999) yang dipakai untuk peringkat dan Juara Umum.
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-light border mt-4 mb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <span class="text-body-secondary">Nilai akhir penampil:</span>
                                        <div class="fs-4 fw-bold text-primary" data-live-value>0.00</div>
                                        <small class="text-body-secondary">Dibulatkan hingga 2 desimal.</small>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-primary px-4 js-score-submit"
                                        title="Simpan nilai akhir">
                                        <i class="bi bi-save me-1"></i>Simpan Nilai
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="alert alert-info mb-0">
                                Anda hanya memiliki hak melihat nilai.
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
            const input = document.querySelector('#final_score');
            const liveValue = document.querySelector('[data-live-value]');

            const recalculate = function() {
                if (!input || !liveValue) {
                    return;
                }

                const value = parseFloat(input.value);

                liveValue.textContent = isNaN(value) ? '0.00' : value.toFixed(2);
            };

            if (input) {
                input.addEventListener('input', recalculate);
            }
            recalculate();

            const form = document.getElementById('form-final-score');

            if (form) {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    swalConfirm({
                        title: 'Simpan nilai akhir?',
                        text: 'Nilai akhir dipakai untuk peringkat kelas dan penampil ditandai selesai.'
                    }).then(function(result) {
                        if (result.isConfirmed) HTMLFormElement.prototype.submit.call(form);
                    });
                });
            }
        });
    </script>
@endpush
