@extends('layouts.admin.app')

@section('title', 'Cetak Label Atlet')

@section('content')
    <div class="container-fluid">
        <div class="card mb-4 print-toolbar">
            <div class="card-header">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-5">
                        <h3 class="card-title mb-0">Cetak Label Barcode Atlet</h3>
                        <div class="text-body-secondary small">
                            Label memakai kode yang sama dengan halaman Scan / Verifikasi (Kode QR).
                        </div>
                    </div>
                    <div class="col-12 col-md-7">
                        <form action="{{ route('admin.athletes.labels') }}" method="GET"
                            class="d-flex flex-wrap justify-content-md-end gap-2">
                            <select name="contingent" class="form-select form-select-sm w-auto"
                                onchange="this.form.submit()">
                                <option value="">Semua kontingen</option>
                                @foreach ($contingents as $contingent)
                                    <option value="{{ $contingent->id }}" @selected($contingentFilter == $contingent->id)>
                                        {{ $contingent->name }} ({{ $contingent->code }})
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-print-labels"
                                title="Cetak label">
                                <i class="bi bi-printer me-1"></i>Cetak
                            </button>
                            <a href="{{ route('admin.athletes.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Kembali
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if ($athletes->isEmpty())
            <div class="alert alert-warning">
                Tidak ada atlet untuk dicetak labelnya.
            </div>
        @else
            <div class="label-grid" id="label-grid">
                @foreach ($athletes as $athlete)
                    @php($barcode = (string) ($athlete->qr_code ?: $athlete->participant_number))
                    <div class="label-item">
                        <div class="label-card">
                            <div class="label-head">
                                <span class="label-event">{{ config('app.name', 'Hapkido Championship') }}</span>
                                <span class="label-cat">{{ $athlete->category?->name ?? '—' }}</span>
                            </div>
                            <div class="label-name">{{ $athlete->name }}</div>
                            <div class="label-meta">
                                <span>{{ $athlete->contingent?->name ?? '—' }}</span>
                                <span>No. {{ $athlete->participant_number }}</span>
                            </div>
                            <svg class="label-barcode" data-code="{{ $barcode }}"></svg>
                            <div class="label-code">{{ $barcode }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection

@push('styles')
    <style>
        .label-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 14px;
            padding-bottom: 20px;
        }

        .label-card {
            border: 1px dashed #444;
            border-radius: 8px;
            padding: 10px 12px;
            background: #fff;
            color: #111;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .label-head {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: #555;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
        }

        .label-name {
            font-size: 15px;
            font-weight: 700;
            margin: 6px 0 2px;
            line-height: 1.2;
            word-break: break-word;
        }

        .label-meta {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            font-size: 11px;
            color: #444;
            margin-bottom: 6px;
        }

        .label-barcode {
            width: 100%;
            height: 54px;
            display: block;
        }

        .label-code {
            text-align: center;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1.5px;
            margin-top: 2px;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            body * {
                visibility: hidden !important;
            }

            .label-grid,
            .label-grid * {
                visibility: visible !important;
            }

            .label-grid {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 5mm !important;
                padding: 0 !important;
            }

            .print-toolbar,
            .app-content-header,
            .app-sidebar,
            .navbar,
            .app-footer,
            footer {
                display: none !important;
            }

            .label-item {
                padding: 0 !important;
            }

            .label-card {
                border: 1px dashed #666 !important;
                box-shadow: none !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('vendor/JsBarcode.all.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('svg.label-barcode').forEach(function(svg) {
                const code = svg.getAttribute('data-code') || '';
                if (!code) return;

                try {
                    JsBarcode(svg, code, {
                        format: 'CODE128',
                        width: 1.6,
                        height: 46,
                        margin: 0,
                        displayValue: false,
                        font: 'monospace'
                    });
                } catch (error) {
                    console.warn('Barcode tidak dapat dibuat:', code, error);
                }
            });

            const printButton = document.getElementById('btn-print-labels');
            if (printButton) {
                printButton.addEventListener('click', function() {
                    window.print();
                });
            }
        });
    </script>
@endpush
