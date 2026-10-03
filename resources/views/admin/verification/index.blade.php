@extends('layouts.admin.app')

@section('title', 'Scan / Verifikasi Atlet')

@section('content')
    <div class="container-fluid">
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-qr-code-scan me-1"></i>Scan / Input Kode Atlet
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <button type="button" id="btn-open-scanner" class="btn btn-outline-primary w-100">
                                <i class="bi bi-upc-scan me-1"></i>Pindai dengan Kamera
                            </button>
                        </div>

                        <div id="scanner-section" class="d-none mb-3 border rounded overflow-hidden">
                            <div class="bg-dark text-white d-flex justify-content-between align-items-center px-3 py-2">
                                <span class="small">
                                    <i class="bi bi-camera-video-fill me-1"></i>Pemindai Barcode (Offline)
                                </span>
                                <span class="badge bg-secondary" id="scanner-status">Siap</span>
                            </div>
                            <div id="scanner-view" class="scanner-viewport position-relative"></div>
                            <div class="p-2 d-flex gap-2">
                                <button type="button" id="btn-scanner-start"
                                    class="btn btn-sm btn-success flex-fill">
                                    <i class="bi bi-play-fill me-1"></i>Mulai
                                </button>
                                <button type="button" id="btn-scanner-stop" disabled
                                    class="btn btn-sm btn-outline-warning flex-fill">
                                    <i class="bi bi-stop-fill me-1"></i>Berhenti
                                </button>
                                <button type="button" id="btn-scanner-close"
                                    class="btn btn-sm btn-outline-secondary flex-fill">
                                    <i class="bi bi-x-lg me-1"></i>Tutup
                                </button>
                            </div>
                        </div>

                        <form id="scan-form" action="{{ route('admin.verifications.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="scan-code" class="form-label">Input Manual (ketik/scan barcode)</label>
                                <input type="text" name="code" id="scan-code"
                                    class="form-control @error('code') is-invalid @enderror"
                                    value="{{ old('code') }}" placeholder="Contoh: QR0001"
                                    autocomplete="off" autofocus required />
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Input manual: ketik kode QR / nomor peserta / NIK, lalu tekan Enter.
                                    Jika kamera tersedia, gunakan tombol "Pindai dengan Kamera" di atas.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="scan-schedule" class="form-label">Jadwal</label>
                                <select name="schedule_id" id="scan-schedule"
                                    class="form-select @error('schedule_id') is-invalid @enderror">
                                    <option value="">— Deteksi otomatis —</option>
                                    @foreach ($schedules as $schedule)
                                        <option value="{{ $schedule->id }}" @selected((string) old('schedule_id') === (string) $schedule->id)>
                                            {{ $schedule->match_no }} &middot; {{ $schedule->category?->name }}
                                            &middot; {{ $schedule->arena?->label ?? 'Tanpa Arena' }}
                                            ({{ substr((string) $schedule->start_time, 0, 5) }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('schedule_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @can('verifications.create')
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-person-check-fill me-1"></i>Verifikasi Atlet
                                </button>
                            @endcan
                        </form>

                        <div class="border-top mt-3 pt-3 d-flex justify-content-between align-items-center">
                            <span class="text-body-secondary small">Terverifikasi hari ini</span>
                            <span class="badge bg-success fs-6">{{ $presentCount }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <div class="row g-2 align-items-center">
                            <div class="col-12 col-md-5">
                                <h5 class="card-title mb-0">Atlet Terverifikasi Hari Ini</h5>
                            </div>
                            <div class="col-12 col-md-7">
                                <form action="{{ route('admin.verifications.index') }}" method="GET"
                                    class="d-flex justify-content-md-end gap-2">
                                    <div class="input-group input-group-sm w-auto">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="search" name="q" value="{{ $search }}" class="form-control"
                                            placeholder="Cari atlet / kode" style="width: 180px" />
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-funnel me-1"></i>Filter
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 90px">Waktu</th>
                                        <th>Atlet</th>
                                        <th>No. Urut</th>
                                        <th>Kontingen</th>
                                        <th>Jadwal</th>
                                        <th style="width: 90px">Metode</th>
                                        <th>Petugas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($verifications as $verification)
                                        <tr>
                                            <td class="fw-semibold">
                                                {{ $verification->verified_at?->format('H:i') ?? '—' }}
                                            </td>
                                            <td class="fw-medium">{{ $verification->athlete?->name ?? '—' }}</td>
                                            <td>{{ $verification->athlete?->participant_number ?? '—' }}</td>
                                            <td>
                                                <span class="badge bg-dark">
                                                    {{ $verification->athlete?->contingent?->code ?? '—' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    {{ $verification->schedule?->match_no ?? '—' }}
                                                </span>
                                                <span class="text-body-secondary small ms-1">
                                                    {{ $verification->schedule?->arena?->label ?? '—' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $verification->method === 'qr' ? 'bg-info-subtle text-info-emphasis' : 'bg-warning text-dark' }}">
                                                    {{ strtoupper($verification->method) }}
                                                </span>
                                            </td>
                                            <td>{{ $verification->verifiedBy?->name ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-body-secondary py-4">
                                                Belum ada atlet terverifikasi hari ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer clearfix">
                        {{ $verifications->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .scanner-viewport {
            min-height: 220px;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .scanner-viewport video {
            display: block;
            width: 100%;
            max-height: 280px;
            object-fit: cover;
            background: #000;
        }

        .scanner-viewport canvas {
            max-width: 100%;
            height: auto;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('vendor/quagga.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('scan-form');
            const codeInput = document.getElementById('scan-code');
            const submitButton = form ? form.querySelector('button[type="submit"]') : null;

            let audioContext = null;

            function playBeep(success) {
                try {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (!AudioCtx) return;

                    if (!audioContext) audioContext = new AudioCtx();
                    if (audioContext.state === 'suspended') audioContext.resume();

                    const now = audioContext.currentTime;
                    const duration = success ? 0.15 : 0.3;
                    const oscillator = audioContext.createOscillator();
                    const gain = audioContext.createGain();

                    oscillator.type = 'square';
                    oscillator.frequency.setValueAtTime(success ? 1180 : 320, now);
                    gain.gain.setValueAtTime(0.0001, now);
                    gain.gain.exponentialRampToValueAtTime(0.12, now + 0.015);
                    gain.gain.exponentialRampToValueAtTime(0.0001, now + duration);

                    oscillator.connect(gain);
                    gain.connect(audioContext.destination);
                    oscillator.start(now);
                    oscillator.stop(now + duration + 0.05);
                } catch (error) {
                    /* browser tanpa dukungan audio: abaikan */
                }
            }

            if (form && codeInput && submitButton) {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();

                    submitButton.disabled = true;

                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': AppConfig.csrf,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new URLSearchParams(new FormData(form))
                    })
                        .then(function(response) {
                            return response.json().then(function(payload) {
                                return { ok: response.ok, payload: payload };
                            });
                        })
                        .then(function(result) {
                            submitButton.disabled = false;

                            if (!result.ok) {
                                const message = result.payload.message ||
                                    Object.values(result.payload.errors || {}).flat().join(' • ') ||
                                    'Kode tidak ditemukan.';
                                playBeep(false);
                                swalError('Verifikasi gagal', message);
                                codeInput.focus();
                                codeInput.select();
                                return;
                            }

                            swalSuccess('Terverifikasi', result.payload.message).then(function() {
                                window.location.reload();
                            });
                        })
                        .catch(function() {
                            submitButton.disabled = false;
                            playBeep(false);
                            swalError('Gagal', 'Terjadi kesalahan, coba lagi.');
                            codeInput.focus();
                        });
                });

                codeInput.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        form.requestSubmit();
                    }
                });
            }

            /* ==== Pemindai kamera offline (Quagga2) ==== */
            const openButton = document.getElementById('btn-open-scanner');
            const section = document.getElementById('scanner-section');
            const startButton = document.getElementById('btn-scanner-start');
            const stopButton = document.getElementById('btn-scanner-stop');
            const closeButton = document.getElementById('btn-scanner-close');
            const statusBadge = document.getElementById('scanner-status');
            const viewport = document.getElementById('scanner-view');

            if (!openButton || !section || !startButton || !stopButton || !closeButton || !viewport) return;

            let scannerActive = false;
            let handled = false;

            function setStatus(text, className) {
                if (!statusBadge) return;
                statusBadge.textContent = text;
                statusBadge.className = 'badge ' + (className || 'bg-secondary');
            }

            function stopCameraTracks() {
                viewport.querySelectorAll('video').forEach(function(video) {
                    try {
                        if (video.srcObject) {
                            video.srcObject.getTracks().forEach(function(track) {
                                track.stop();
                            });
                            video.srcObject = null;
                        }
                        video.pause();
                    } catch (error) {
                        /* abaikan */
                    }
                });
            }

            function handleCameraError(error) {
                const name = (error && error.name) || '';
                const message = (error && error.message) || '';

                if (name === 'NotAllowedError' || name === 'PermissionDeniedError' ||
                    name === 'SecurityError' || /permission|denied/i.test(message)) {
                    swalError('Izin kamera ditolak',
                        'Akses kamera ditolak oleh browser. Izinkan kamera lewat ikon gembok pada bilah alamat, lalu tekan "Pindai dengan Kamera" lagi.');
                } else if (name === 'NotFoundError' || name === 'DevicesNotFoundError' ||
                    /no camera|no video input|not found|requested device/i.test(message)) {
                    swalError('Kamera tidak ditemukan',
                        'Tidak ada kamera yang terdeteksi pada perangkat ini. Gunakan "Input Manual (ketik/scan barcode)" di bawah.');
                } else if (name === 'NotReadableError' || name === 'TrackStartError' ||
                    /in use|could not start|busy/i.test(message)) {
                    swalError('Kamera tidak dapat diakses',
                        'Kamera sedang digunakan aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.');
                } else if (/getUserMedia is not defined|not supported|insecure/i.test(message)) {
                    swalError('Kamera tidak didukung',
                        'Fitur kamera tidak tersedia. Pastikan halaman dibuka melalui HTTPS atau localhost, atau gunakan Input Manual.');
                } else {
                    swalError('Kamera gagal dimulai',
                        (message || 'Terjadi kesalahan saat membuka kamera.') + ' Gunakan Input Manual jika kamera bermasalah.');
                }
            }

            function stopScanner() {
                if (window.Quagga) {
                    try {
                        Quagga.offDetected(onDetected);
                    } catch (error) { /* abaikan */ }
                    try {
                        Quagga.stop();
                    } catch (error) { /* abaikan */ }
                }

                stopCameraTracks();
                scannerActive = false;
                startButton.disabled = false;
                stopButton.disabled = true;
                setStatus('Berhenti', 'bg-secondary');
            }

            function onDetected(result) {
                if (handled) return;

                const code = result && result.codeResult && result.codeResult.code;
                if (!code) return;

                handled = true;
                playBeep(true);

                if (codeInput) {
                    codeInput.value = code;
                    codeInput.focus();
                }

                stopScanner();
                setStatus('Kode terdeteksi', 'bg-info-subtle text-info-emphasis');

                window.setTimeout(function() {
                    if (form) {
                        form.requestSubmit();
                    }
                    window.setTimeout(function() {
                        handled = false;
                    }, 1200);
                }, 250);
            }

            function startScanner() {
                if (scannerActive) return;

                if (typeof Quagga === 'undefined') {
                    swalError('Pemindai tidak tersedia',
                        'Library barcode belum termuat. Muat ulang halaman, atau gunakan Input Manual.');
                    return;
                }

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    handleCameraError({ name: 'NotFoundError', message: 'getUserMedia is not defined.' });
                    return;
                }

                startButton.disabled = true;
                stopButton.disabled = true;
                setStatus('Memulai kamera…', 'bg-warning text-dark');

                Quagga.init({
                    inputStream: {
                        type: 'LiveStream',
                        target: viewport,
                        constraints: {
                            width: { min: 320 },
                            height: { min: 240 },
                            facingMode: 'environment'
                        }
                    },
                    decoder: {
                        readers: ['code_128_reader', 'ean_reader', 'ean_8_reader', 'code_39_reader']
                    },
                    locate: true,
                    numOfWorkers: 0,
                    debug: false
                }, function(error) {
                    if (error) {
                        startButton.disabled = false;
                        stopButton.disabled = true;
                        setStatus('Gagal', 'bg-danger');
                        stopCameraTracks();
                        handleCameraError(error);
                        return;
                    }

                    try {
                        Quagga.offDetected(onDetected);
                    } catch (e) { /* abaikan */ }
                    Quagga.onDetected(onDetected);

                    Quagga.start();
                    scannerActive = true;
                    startButton.disabled = true;
                    stopButton.disabled = false;
                    setStatus('Kamera aktif', 'bg-success');
                });
            }

            function closeScanner() {
                stopScanner();
                section.classList.add('d-none');
                setStatus('Siap', 'bg-secondary');
            }

            openButton.addEventListener('click', function() {
                section.classList.remove('d-none');
                startScanner();
            });

            startButton.addEventListener('click', startScanner);
            stopButton.addEventListener('click', stopScanner);
            closeButton.addEventListener('click', closeScanner);

            window.addEventListener('pagehide', stopScanner);
            window.addEventListener('beforeunload', stopScanner);
        });
    </script>
@endpush
