@extends('layouts.admin.app')

@section('title', 'Riwayat')

@section('content')
    <div class="container-fluid">
        <div class="card mb-4">
            <div class="card-header">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-4">
                        <h3 class="card-title mb-0">Riwayat Kegiatan</h3>
                    </div>
                    <div class="col-12 col-md-8">
                        <form action="{{ route('admin.history.index') }}" method="GET"
                            class="d-flex flex-wrap justify-content-md-end gap-2">
                            <select name="type" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                <option value="">Semua jenis</option>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="date" name="from" value="{{ $from }}"
                                class="form-control form-control-sm w-auto" aria-label="Dari tanggal" />
                            <input type="date" name="to" value="{{ $to }}"
                                class="form-control form-control-sm w-auto" aria-label="Sampai tanggal" />
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            @if ($type !== '' || $from !== '' || $to !== '')
                                <a href="{{ route('admin.history.index') }}" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-x-circle me-1"></i>Reset
                                </a>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 170px">Waktu</th>
                                <th style="width: 190px">Jenis</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($history as $item)
                                <tr>
                                    <td class="text-body-secondary small">
                                        {{ $item['time']->format('d M Y') }}<br />
                                        <strong class="text-body">{{ $item['time']->format('H:i') }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge {{ $item['badge'] }}">
                                            <i class="bi {{ $item['icon'] }} me-1"></i>{{ $item['type_label'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-medium">{{ $item['title'] }}</div>
                                        <div class="small text-body-secondary">{{ $item['detail'] }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-body-secondary py-4">
                                        Tidak ada riwayat untuk filter yang dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                {{ $history->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection
