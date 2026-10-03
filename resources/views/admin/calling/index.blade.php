@extends('layouts.admin.app')

@section('title', 'Calling Atlet')

@section('content')
    <div class="container-fluid">
        <div class="card mb-3">
            <div class="card-header">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-5">
                        <h3 class="card-title mb-0">Calling Atlet Hari Ini</h3>
                        <small class="text-body-secondary" id="calling-updated">
                            {{ $payload['date'] }} &middot; diperbarui {{ $payload['generated_at'] }} WIB
                        </small>
                    </div>
                    <div class="col-12 col-md-7">
                        <div class="d-flex flex-wrap justify-content-md-end gap-2" id="calling-counts">
                            <span class="badge bg-secondary fs-6">Menunggu {{ $payload['counts']['waiting'] }}</span>
                            <span class="badge bg-danger fs-6">Terpanggil {{ $payload['counts']['called'] }}</span>
                            <span class="badge bg-success fs-6">Siap {{ $payload['counts']['ready'] }}</span>
                            <span class="badge bg-dark fs-6">Total {{ $payload['counts']['total'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0" id="calling-board">
                @if (empty($payload['arenas']))
                    <div class="text-center text-body-secondary py-5">
                        <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                        Tidak ada jadwal calling hari ini.
                    </div>
                @else
                    @foreach ($payload['arenas'] as $group)
                        <div class="border-bottom">
                            <div class="bg-body-tertiary px-3 py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <span class="fw-semibold">
                                        <i class="bi bi-grid-3x3-gap-fill me-1"></i>{{ $group['label'] }}
                                    </span>
                                    <span class="text-body-secondary small ms-1">({{ $group['name'] }})</span>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge bg-secondary">Menunggu {{ $group['counts']['waiting'] }}</span>
                                    <span class="badge bg-danger">Terpanggil {{ $group['counts']['called'] }}</span>
                                    <span class="badge bg-success">Siap {{ $group['counts']['ready'] }}</span>
                                </div>
                            </div>

                            @foreach ($group['schedules'] as $schedule)
                                <div class="px-3 pt-3">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="badge bg-dark">{{ $schedule['match_no'] }}</span>
                                            <strong>{{ $schedule['category'] }}</strong>
                                            <span class="text-body-secondary small">
                                                {{ $schedule['type'] === 'art' ? 'Seni' : 'Daeryun' }}
                                                &middot; {{ $schedule['start_time'] !== '' ? $schedule['start_time'] : '—' }}
                                                &middot; {{ $schedule['round'] }}
                                            </span>
                                            <span class="badge bg-{{ $schedule['status_class'] }}">{{ $schedule['status_label'] }}</span>
                                        </div>
                                        @can('callings.send')
                                            <div class="d-flex align-items-center gap-2">
                                                <select class="form-select form-select-sm w-auto"
                                                    data-level-key="schedule-{{ $schedule['id'] }}"
                                                    onchange="window.levelChoice['schedule-{{ $schedule['id'] }}'] = this.value">
                                                    <option value="30" selected>30 menit</option>
                                                    <option value="15">15 menit</option>
                                                    <option value="5">5 menit</option>
                                                </select>
                                                <button type="button" class="btn btn-sm btn-primary"
                                                    data-send-all-url="{{ route('admin.callings.send-all', ['schedule' => $schedule['id']]) }}"
                                                    data-level-key="schedule-{{ $schedule['id'] }}"
                                                    data-match-no="{{ $schedule['match_no'] }}">
                                                    <i class="bi bi-bell-fill me-1"></i>Kirim Semua
                                                </button>
                                            </div>
                                        @endcan
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width: 70px">No. Urut</th>
                                                    <th>Atlet</th>
                                                    <th>Kontingen</th>
                                                    <th style="width: 130px">Level</th>
                                                    <th style="width: 120px">Status</th>
                                                    <th style="width: 90px">Jam Kirim</th>
                                                    @can('callings.send')
                                                        <th class="text-end" style="width: 160px">Aksi</th>
                                                    @endcan
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($schedule['callings'] as $row)
                                                    <tr>
                                                        <td class="text-body-secondary">{{ $row['participant_number'] }}</td>
                                                        <td class="fw-medium">{{ $row['athlete'] }}</td>
                                                        <td><span class="badge bg-dark">{{ $row['contingent'] }}</span></td>
                                                        <td>
                                                            @can('callings.send')
                                                                <select class="form-select form-select-sm w-auto"
                                                                    data-level-key="{{ $row['calling_id'] }}"
                                                                    onchange="window.levelChoice['{{ $row['calling_id'] }}'] = this.value">
                                                                    <option value="30" @selected(($row['level'] ?? null) === '30')>30 menit</option>
                                                                    <option value="15" @selected(($row['level'] ?? null) === '15')>15 menit</option>
                                                                    <option value="5" @selected(($row['level'] ?? null) === '5')>5 menit</option>
                                                                </select>
                                                            @else
                                                                {{ $row['level_label'] }}
                                                            @endcan
                                                        </td>
                                                        <td><span class="badge bg-{{ $row['status_class'] }}">{{ $row['status_label'] }}</span></td>
                                                        <td>{{ $row['called_at'] ?? '—' }}</td>
                                                        @can('callings.send')
                                                            <td class="text-end">
                                                                @if ($row['calling_id'])
                                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                                        data-send-url="{{ route('admin.callings.send', ['calling' => $row['calling_id']]) }}"
                                                                        data-level-key="{{ $row['calling_id'] }}"
                                                                        data-athlete="{{ $row['athlete'] }}">
                                                                        <i class="bi bi-bell me-1"></i>Kirim Calling
                                                                    </button>
                                                                @else
                                                                    <span class="text-body-secondary small">Belum siap</span>
                                                                @endif
                                                            </td>
                                                        @endcan
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="7" class="text-center text-body-secondary py-3">
                                                            Belum ada atlet pada partai ini.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.levelChoice = {};
            const canSend = {{ auth()->user()->can('callings.send') ? 'true' : 'false' }};

            const sendUrlTemplate = @json(route('admin.callings.send', ['calling' => '__ID__']));
            const sendAllUrlTemplate = @json(route('admin.callings.send-all', ['schedule' => '__ID__']));

            const escapeHtml = function(value) {
                return String(value === null || value === undefined ? '' : value)
                    .replace(/[&<>"']/g, function(char) {
                        return {
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;',
                            '"': '&quot;',
                            "'": '&#039;'
                        }[char];
                    });
            };

            const currentLevel = function(key) {
                if (window.levelChoice[key]) return window.levelChoice[key];
                const select = document.querySelector('select[data-level-key="' + key + '"]');
                return select && select.value ? select.value : '30';
            };

            const levelOptions = function(key, selected) {
                const value = window.levelChoice[key] || selected || '30';
                return ['30', '15', '5'].map(function(level) {
                    return '<option value="' + level + '"' + (value === level ? ' selected' : '') + '>' +
                        level + ' menit</option>';
                }).join('');
            };

            const countsHtml = function(counts) {
                return '<span class="badge bg-secondary fs-6">Menunggu ' + counts.waiting + '</span>' +
                    '<span class="badge bg-danger fs-6">Terpanggil ' + counts.called + '</span>' +
                    '<span class="badge bg-success fs-6">Siap ' + counts.ready + '</span>' +
                    '<span class="badge bg-dark fs-6">Total ' + counts.total + '</span>';
            };

            const groupCountsHtml = function(counts) {
                return '<span class="badge bg-secondary">Menunggu ' + counts.waiting + '</span>' +
                    '<span class="badge bg-danger">Terpanggil ' + counts.called + '</span>' +
                    '<span class="badge bg-success">Siap ' + counts.ready + '</span>';
            };

            const renderRow = function(row) {
                const levelCell = canSend
                    ? '<select class="form-select form-select-sm w-auto" data-level-key="' + row.calling_id + '"' +
                        ' onchange="window.levelChoice[' + row.calling_id + '] = this.value">' +
                        levelOptions(row.calling_id, row.level) + '</select>'
                    : escapeHtml(row.level_label);

                const actionCell = canSend
                    ? (row.calling_id
                        ? '<button type="button" class="btn btn-sm btn-outline-primary"' +
                            ' data-send-url="' + sendUrlTemplate.replace('__ID__', row.calling_id) + '"' +
                            ' data-level-key="' + row.calling_id + '"' +
                            ' data-athlete="' + escapeHtml(row.athlete) + '">' +
                            '<i class="bi bi-bell me-1"></i>Kirim Calling</button>'
                        : '<span class="text-body-secondary small">Belum siap</span>')
                    : '';

                return '<tr>' +
                    '<td class="text-body-secondary">' + escapeHtml(row.participant_number) + '</td>' +
                    '<td class="fw-medium">' + escapeHtml(row.athlete) + '</td>' +
                    '<td><span class="badge bg-dark">' + escapeHtml(row.contingent) + '</span></td>' +
                    '<td>' + levelCell + '</td>' +
                    '<td><span class="badge bg-' + row.status_class + '">' + escapeHtml(row.status_label) + '</span></td>' +
                    '<td>' + escapeHtml(row.called_at || '—') + '</td>' +
                    (canSend ? '<td class="text-end">' + actionCell + '</td>' : '') +
                    '</tr>';
            };

            const renderSchedule = function(schedule) {
                const action = canSend
                    ? '<div class="d-flex align-items-center gap-2">' +
                        '<select class="form-select form-select-sm w-auto" data-level-key="schedule-' + schedule.id + '"' +
                        ' onchange="window.levelChoice[\'schedule-' + schedule.id + '\'] = this.value">' +
                        levelOptions('schedule-' + schedule.id, null) + '</select>' +
                        '<button type="button" class="btn btn-sm btn-primary"' +
                        ' data-send-all-url="' + sendAllUrlTemplate.replace('__ID__', schedule.id) + '"' +
                        ' data-level-key="schedule-' + schedule.id + '"' +
                        ' data-match-no="' + escapeHtml(schedule.match_no) + '">' +
                        '<i class="bi bi-bell-fill me-1"></i>Kirim Semua</button>' +
                        '</div>'
                    : '';

                const rows = schedule.callings.length
                    ? schedule.callings.map(renderRow).join('')
                    : '<tr><td colspan="7" class="text-center text-body-secondary py-3">Belum ada atlet pada partai ini.</td></tr>';

                return '<div class="px-3 pt-3">' +
                    '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">' +
                        '<div class="d-flex align-items-center gap-2 flex-wrap">' +
                            '<span class="badge bg-dark">' + escapeHtml(schedule.match_no) + '</span>' +
                            '<strong>' + escapeHtml(schedule.category) + '</strong>' +
                            '<span class="text-body-secondary small">' +
                                (schedule.type === 'art' ? 'Seni' : 'Daeryun') +
                                ' &middot; ' + escapeHtml(schedule.start_time || '—') +
                                ' &middot; ' + escapeHtml(schedule.round) +
                            '</span>' +
                            '<span class="badge bg-' + schedule.status_class + '">' + escapeHtml(schedule.status_label) + '</span>' +
                        '</div>' +
                        action +
                    '</div>' +
                    '<div class="table-responsive">' +
                        '<table class="table table-sm table-hover align-middle mb-0">' +
                            '<thead><tr>' +
                                '<th style="width: 70px">No. Urut</th>' +
                                '<th>Atlet</th>' +
                                '<th>Kontingen</th>' +
                                '<th style="width: 130px">Level</th>' +
                                '<th style="width: 120px">Status</th>' +
                                '<th style="width: 90px">Jam Kirim</th>' +
                                (canSend ? '<th class="text-end" style="width: 160px">Aksi</th>' : '') +
                            '</tr></thead>' +
                            '<tbody>' + rows + '</tbody>' +
                        '</table>' +
                    '</div>' +
                '</div>';
            };

            const renderBoard = function(data) {
                document.getElementById('calling-updated').textContent =
                    data.date + ' · diperbarui ' + data.generated_at + ' WIB';
                document.getElementById('calling-counts').innerHTML = countsHtml(data.counts);

                const board = document.getElementById('calling-board');

                if (!data.arenas.length) {
                    board.innerHTML = '<div class="text-center text-body-secondary py-5">' +
                        '<i class="bi bi-calendar-x fs-1 d-block mb-2"></i>' +
                        'Tidak ada jadwal calling hari ini.</div>';
                    return;
                }

                board.innerHTML = data.arenas.map(function(group) {
                    return '<div class="border-bottom">' +
                        '<div class="bg-body-tertiary px-3 py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">' +
                            '<div>' +
                                '<span class="fw-semibold"><i class="bi bi-grid-3x3-gap-fill me-1"></i>' +
                                escapeHtml(group.label) + '</span>' +
                                '<span class="text-body-secondary small ms-1">(' + escapeHtml(group.name) + ')</span>' +
                            '</div>' +
                            '<div class="d-flex gap-2 flex-wrap">' + groupCountsHtml(group.counts) + '</div>' +
                        '</div>' +
                        group.schedules.map(renderSchedule).join('') +
                    '</div>';
                }).join('');
            };

            document.getElementById('calling-board').addEventListener('click', function(event) {
                const sendButton = event.target.closest('[data-send-url]');
                const sendAllButton = event.target.closest('[data-send-all-url]');

                if (sendButton) {
                    const level = currentLevel(sendButton.dataset.levelKey);
                    swalConfirm({
                        title: 'Kirim Calling',
                        text: 'Kirim calling untuk ' + (sendButton.dataset.athlete || 'atlet') +
                            ' dengan peringatan ' + level + ' menit?'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            postForm(sendButton.dataset.sendUrl, { level: level });
                        }
                    });
                }

                if (sendAllButton) {
                    const level = currentLevel(sendAllButton.dataset.levelKey);
                    swalConfirm({
                        title: 'Kirim Semua Calling',
                        text: 'Kirim calling untuk seluruh atlet partai ' +
                            (sendAllButton.dataset.matchNo || '') + ' dengan peringatan ' + level + ' menit?'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            postForm(sendAllButton.dataset.sendAllUrl, { level: level });
                        }
                    });
                }
            });

            let lastPayload = '';

            startPolling('{{ route('admin.callings.data') }}', AppConfig.pollInterval, function(data) {
                const serialized = JSON.stringify(data);

                if (serialized === lastPayload) return;

                lastPayload = serialized;
                renderBoard(data);
            });
        });
    </script>
@endpush
