<!doctype html>
<html lang="id" data-bs-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $arena->label ?: $arena->name }} - {{ config('app.name') }}</title>
    <script>
        (function() {
            var theme = 'light';
            try {
                var saved = localStorage.getItem('theme');
                if (saved === 'dark' || saved === 'light') {
                    theme = saved;
                }
            } catch (e) {}
            var root = document.documentElement;
            root.setAttribute('data-bs-theme', theme);
            root.style.colorScheme = theme;
        })();
    </script>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.css') }}" />
    @include('display.partials.style')
</head>

<body>
    <div class="topbar">
        <div class="topbar-left">
            <a class="back-btn" href="{{ route('display.index') }}"><i class="bi bi-arrow-left"></i> Kembali</a>
            <div class="arena-heading">
                <div class="arena-code">{{ $arena->label ?: $arena->name }}</div>
                <div class="arena-title">{{ $arena->name }}{{ $arena->category?->name ? ' · ' . $arena->category->name : '' }}</div>
            </div>
        </div>
        <div class="topbar-right">
            <span class="live-pill"><span class="live-dot"></span>LIVE</span>
            <span class="last-update" id="last-update">Memuat...</span>
            <div class="clock" id="clock">--:--:--</div>
            <button class="theme-btn" id="theme-toggle" type="button" title="Ganti tema tampilan" aria-label="Ganti tema tampilan">
                <i id="theme-icon" class="bi bi-sun"></i>
            </button>
        </div>
    </div>

    <div class="arena-switch">
        @foreach ($arenas as $item)
            <a class="arena-chip {{ $item->id === $arena->id ? 'active' : '' }}" href="{{ route('display.arena', $item->id) }}">
                <i class="bi bi-trophy"></i>
                {{ $item->label ?: $item->name }}
            </a>
        @endforeach
    </div>

    <div class="ds-shell">
        <div id="match-frame">
            <div class="match-frame">
                <div class="match-frame-head">
                    <span class="match-frame-title"><i class="bi bi-broadcast"></i> Partai Berjalan</span>
                    <span class="match-frame-meta"></span>
                </div>
                <div class="match-body is-empty">
                    <div class="match-empty">
                        <i class="bi bi-arrow-repeat"></i>
                        <div class="match-empty-title">MEMUAT...</div>
                    </div>
                </div>
                <div class="match-status"><span class="match-status-dot"></span><span>STATUS : MENUNGGU</span></div>
            </div>
        </div>

        <div class="ds-columns">
            <div class="ds-col">
                <div class="panel" id="perf-panel" style="display:none;">
                    <div class="panel-title"><i class="bi bi-music-note-beamed"></i> Penampil Seni &amp; Nilai</div>
                    <div id="perf-list"></div>
                </div>

                <div class="panel">
                    <div class="panel-title"><i class="bi bi-clipboard-check"></i> Hasil Arena</div>
                    <div id="results">
                        <div class="list-row muted">Memuat...</div>
                    </div>
                </div>
            </div>

            <div class="ds-col">
                <div class="panel">
                    <div class="panel-title"><i class="bi bi-bell-fill"></i> Panggilan Atlet</div>
                    <div class="panel-body" id="callings">
                        <div class="muted">Memuat...</div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-title"><i class="bi bi-calendar-event"></i> Jadwal Berikutnya</div>
                    <div id="next-list">
                        <div class="list-row muted">Memuat...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('display.partials.frame-js')

    <script>
        const DATA_URL = @json(route('display.arena.data', $arena));

        const esc = window.dsEsc;

        window.applyTheme = function(theme) {
            const root = document.documentElement;
            root.setAttribute('data-bs-theme', theme);
            root.style.colorScheme = theme;
            try {
                localStorage.setItem('theme', theme);
            } catch (e) {}
            const icon = document.getElementById('theme-icon');
            if (icon) {
                icon.className = theme === 'dark' ? 'bi bi-moon-stars' : 'bi bi-sun';
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
            const icon = document.getElementById('theme-icon');
            if (icon) {
                icon.className = current === 'dark' ? 'bi bi-moon-stars' : 'bi bi-sun';
            }
            const toggle = document.getElementById('theme-toggle');
            if (toggle) {
                toggle.addEventListener('click', function() {
                    const now = document.documentElement.getAttribute('data-bs-theme') || 'light';
                    window.applyTheme(now === 'dark' ? 'light' : 'dark');
                });
            }
        });

        const tickClock = function() {
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            document.getElementById('clock').textContent =
                pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
        };
        tickClock();
        setInterval(tickClock, 1000);

        const frameConfig = function(data) {
            const cur = data.current || {};
            const arena = data.arena || {};
            const isArt = !!data.is_art || cur.type === 'art';
            const isLive = !!cur.is_live || cur.status === 'Tampil';
            const hasWinner = !!cur.winner_name;

            let title = 'Partai Berjalan';
            if (hasWinner) {
                title = 'Hasil Partai';
            } else if (!isLive && cur.match_no && cur.match_no !== '—') {
                title = 'Partai Berikutnya';
            }

            return {
                title: title,
                isLive: isLive,
                arena: arena.label || arena.name || '—',
                category: cur.category || '',
                matchNo: cur.match_no || '',
                status: cur.status || 'Menunggu',
                type: isArt ? 'art' : (cur.type || 'daeryun'),
                meta: [
                    cur.round && cur.round !== '—' ? cur.round : '',
                    cur.time || ''
                ].filter(Boolean).join(' · '),
                a: { name: cur.athlete_a, code: cur.contingent_a, photo: cur.photo_a },
                b: { name: cur.athlete_b, code: cur.contingent_b, photo: cur.photo_b },
                scoreA: cur.score_a,
                scoreB: cur.score_b,
                winner: hasWinner ? {
                    name: cur.winner_name,
                    code: cur.winner_code,
                    side: cur.winner_side,
                    isFinal: !!cur.is_final
                } : null,
                performers: cur.performers || [],
                emptyTitle: 'BELUM ADA PARTAI',
                emptySub: (isArt ? 'SENI' : 'DAERYUN') + (cur.category && cur.category !== '—' ? ' · ' + cur.category : '')
            };
        };

        const render = function(data) {
            window.dsRenderFrame('match-frame', frameConfig(data));

            // Panggilan
            let callHtml = '';
            (data.callings || []).forEach(function(item, index) {
                callHtml += `
                    <div class="list-row">
                        <div class="list-main">
                            <div class="${index === 0 ? 'call-big' : 'perf-name'}">${esc(item.athlete)} <span class="code">${esc(item.contingent)}</span></div>
                            <div class="list-sub">${esc(item.match_no)} &middot; ${esc(item.category)}</div>
                        </div>
                        <span class="badge ${item.status_label === 'Terpanggil' ? 'ds-badge-live' : 'ds-badge-warn'} list-aside">${esc(item.level_label)}</span>
                    </div>`;
            });
            document.getElementById('callings').innerHTML =
                callHtml || '<div class="muted">Tidak ada panggilan aktif.</div>';

            // Penampil seni
            const perfPanel = document.getElementById('perf-panel');
            const performances = data.performances || [];
            if (performances.length) {
                perfPanel.style.display = '';
                let perfHtml = '';
                let lastGroup = null;
                performances.forEach(function(item) {
                    const group = item.schedule_key || (item.match_no + '|' + item.category);
                    if (group !== lastGroup) {
                        lastGroup = group;
                        perfHtml += `<div class="list-group-label">${esc(item.match_no)} &middot; ${esc(item.category)}</div>`;
                    }
                    perfHtml += `
                        <div class="list-row ${item.is_performing ? 'highlight' : ''}">
                            <div class="list-main">
                                <div class="perf-name">${esc(item.order_no)}. ${esc(item.athlete)} <span class="code">${esc(item.contingent)}</span></div>
                                <div class="list-sub">${esc(item.status)}</div>
                            </div>
                            <div class="list-aside">
                                <div class="perf-score">${item.score !== null && item.score !== undefined ? esc(item.score) : '-'}</div>
                                <div class="list-sub">${item.is_performing ? 'Sedang tampil' : 'Nilai akhir'}</div>
                            </div>
                        </div>`;
                });
                document.getElementById('perf-list').innerHTML = perfHtml;
            } else {
                perfPanel.style.display = 'none';
            }

            // Hasil
            let resultHtml = '';
            (data.results || []).forEach(function(item) {
                resultHtml += `
                    <div class="list-row">
                        <div class="list-main">
                            <div class="list-title">${esc(item.winner)} <span class="code">${esc(item.winner_code || '')}</span></div>
                            <div class="list-sub">${esc(item.match_no)} &middot; ${esc(item.category)}</div>
                        </div>
                        <div class="score list-aside">${esc(item.score)}</div>
                    </div>`;
            });
            document.getElementById('results').innerHTML =
                resultHtml || '<div class="list-row muted">Belum ada hasil.</div>';

            // Jadwal berikutnya
            let nextHtml = '';
            (data.next || []).forEach(function(item) {
                nextHtml += `
                    <div class="list-row">
                        <div class="list-main">
                            <div class="list-title">${esc(item.match_no)} <span class="muted">${esc(item.time)}</span></div>
                            <div class="list-sub">${esc(item.category)}</div>
                        </div>
                        <span class="badge ds-badge-info list-aside">${item.type === 'art' ? 'SENI' : 'DAERYUN'}</span>
                    </div>`;
            });
            document.getElementById('next-list').innerHTML =
                nextHtml || '<div class="list-row muted">Tidak ada jadwal.</div>';

            const stamp = document.getElementById('last-update');
            if (stamp) {
                stamp.textContent = 'Diperbarui ' + (data.now || '') + ':' + String(new Date().getSeconds()).padStart(2, '0');
            }
        };

        const poll = function() {
            fetch(DATA_URL, { headers: { Accept: 'application/json' }, cache: 'no-store' })
                .then(function(r) { return r.ok ? r.json() : null; })
                .then(function(data) { if (data) render(data); })
                .catch(function() {});
        };
        poll();
        setInterval(poll, 3000);
    </script>
</body>

</html>
