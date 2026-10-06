<!doctype html>
<html lang="id" data-bs-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ config('app.name') }} - Public Display</title>
    <meta name="description" content="{{ config('app.description') }}" />
    @if (config('app.favicon'))
        <link rel="icon" href="{{ config('app.favicon') }}" />
    @endif
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
        <div class="topbar-title">
            @if (config('app.logo'))
                <img src="{{ config('app.logo') }}" alt=""
                    style="height: 1.1em; width: auto; vertical-align: -0.15em; margin-right: .4rem;" />
            @endif<i class="bi bi-trophy-fill me-2"></i>{{ strtoupper(config('app.short_name', config('app.name'))) }}
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

    <div class="ds-shell">
        <div id="live-frame">
            <div class="match-frame">
                <div class="match-frame-head">
                    <span class="match-frame-title"><i class="bi bi-broadcast"></i> Laga Berlangsung</span>
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
                <div class="panel">
                    <div class="panel-title"><i class="bi bi-grid-3x3-gap-fill"></i> Status Arena</div>
                    <div class="panel-body">
                        <div class="arena-grid" id="arena-cards">
                            <div class="muted">Memuat...</div>
                        </div>
                    </div>
                </div>

                <div class="ds-row-2">
                    <div class="panel">
                        <div class="panel-title"><i class="bi bi-clipboard-check"></i> Hasil Terakhir</div>
                        <div id="results">
                            <div class="list-row muted">Memuat...</div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel-title"><i class="bi bi-music-note-beamed"></i> Penampil Seni</div>
                        <div id="performances">
                            <div class="list-row muted">Memuat...</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ds-col">
                <div class="panel" id="calling-panel">
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

        <div class="panel" id="juara-panel">
            <div class="panel-title"><i class="bi bi-trophy-fill"></i> Juara 1 - 2 - 3</div>
            <div class="panel-body">
                <div id="juara-body">
                    <div class="muted">Memuat...</div>
                </div>
            </div>
        </div>
    </div>

    @include('display.partials.frame-js')

    <script>
        const ARENA_BASE = @json(url('/display/arena'));
        @php
            $arenaLinks = collect($arenas)->mapWithKeys(fn ($a) => [(string) $a['id'] => route('display.arena', $a['id'])]);
        @endphp
        const ARENA_LINKS = @json($arenaLinks);

        const esc = window.dsEsc;
        const avatarInner = window.dsAvatarInner;

        const arenaLink = function(id) {
            return ARENA_LINKS[String(id)] || (ARENA_BASE + '/' + encodeURIComponent(id));
        };

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

        const MEDAL_EMOJI = { emas: '\u{1F947}', perak: '\u{1F948}', perunggu: '\u{1F949}' };

        const badgeFor = function(label) {
            return label === 'Tampil' ? 'ds-badge-live' : 'ds-badge-muted';
        };

        const miniSide = function(name, code, photo, color) {
            return `
                <div class="mini-side ${color}">
                    <div class="mini-avatar ds-avatar ${color}">${avatarInner(photo, name)}</div>
                    <span class="mini-name">${esc(name || 'TBD')}</span>
                    <span class="mini-code">${esc(code || '')}</span>
                </div>`;
        };

        const miniPerformers = function(list) {
            list = (list || []).slice(0, 3);
            if (!list.length) {
                return '<div class="muted small">Belum ada penampil</div>';
            }
            let html = '<div class="mini-vs">';
            list.forEach(function(p, i) {
                const order = (p.order_no === null || p.order_no === undefined) ? i + 1 : p.order_no;
                html += `
                    <div class="mini-side neutral">
                        <span class="mini-order">${esc(String(order).padStart(2, '0'))}</span>
                        <span class="mini-name">${esc(p.athlete)}</span>
                        <span class="mini-code">${esc(p.contingent || '')}</span>
                    </div>`;
            });
            return html + '</div>';
        };

        const miniMatch = function(arena) {
            if ((arena.type || 'daeryun') === 'art') {
                return miniPerformers(arena.performers);
            }
            if (!arena.athlete_a && !arena.athlete_b) {
                return '<div class="muted small">Belum ada partai</div>';
            }
            return `
                <div class="mini-vs">
                    ${miniSide(arena.athlete_a, arena.contingent_a, arena.photo_a, 'red')}
                    <div class="mini-divider">VS</div>
                    ${miniSide(arena.athlete_b, arena.contingent_b, arena.photo_b, 'blue')}
                </div>`;
        };

        const arenaCard = function(arena) {
            return `
                <a class="arena-card" href="${esc(arenaLink(arena.id))}" title="Buka layar arena ${esc(arena.label || arena.name || '')}">
                    <div class="arena-card-head">
                        <span class="arena-card-name">${esc(arena.label)}</span>
                        <span class="badge ${badgeFor(arena.status_match)}">${esc(arena.status_match)}</span>
                    </div>
                    <div class="arena-card-meta">${esc(arena.category)} &middot; ${esc(arena.match_no)}</div>
                    ${miniMatch(arena)}
                    <div class="arena-card-foot"><i class="bi bi-arrow-right-circle"></i> Layar arena</div>
                </a>`;
        };

        const liveConfig = function(live) {
            const base = {
                title: 'Laga Berlangsung',
                arena: '—',
                category: '',
                matchNo: '',
                status: 'Menunggu',
                type: 'daeryun',
                isLive: false,
                a: {},
                b: {},
                emptyTitle: 'BELUM ADA PARTAI',
                emptySub: 'Menunggu jadwal hari ini'
            };

            if (!live) {
                return base;
            }

            return Object.assign(base, {
                title: live.status === 'Tampil' ? 'Laga Berlangsung'
                    : (live.status === 'Selesai' ? 'Hasil Partai' : 'Partai Berikutnya'),
                isLive: !!live.is_live,
                arena: live.arena,
                category: live.category,
                matchNo: live.match_no,
                status: live.status,
                type: live.type || 'daeryun',
                meta: [live.round && live.round !== '—' ? live.round : '', live.time || '']
                    .filter(Boolean).join(' · '),
                a: { name: live.athlete_a, code: live.contingent_a, photo: live.photo_a },
                b: { name: live.athlete_b, code: live.contingent_b, photo: live.photo_b },
                scoreA: live.score_a,
                scoreB: live.score_b,
                winner: live.winner_name ? {
                    name: live.winner_name,
                    code: live.winner_code,
                    side: live.winner_side,
                    isFinal: !!live.is_final
                } : null,
                performers: live.performers || [],
                emptySub: live.category || ''
            });
        };

        const render = function(data) {
            window.dsRenderFrame('live-frame', liveConfig(data.live));

            let arenaHtml = '';
            (data.arenas || []).forEach(function(arena) {
                arenaHtml += arenaCard(arena);
            });
            document.getElementById('arena-cards').innerHTML =
                arenaHtml || '<div class="muted">Tidak ada arena</div>';

            // Panggilan
            let callHtml = '';
            (data.callings || []).slice(0, 5).forEach(function(item, index) {
                callHtml += `
                    <div class="list-row">
                        <div class="list-main">
                            <div class="${index === 0 ? 'call-big' : 'list-title'}">${esc(item.athlete)} <span class="code">${esc(item.contingent)}</span></div>
                            <div class="list-sub">${esc(item.match_no)} &middot; ${esc(item.category)} &middot; ${esc(item.arena)}</div>
                        </div>
                        <span class="badge ${item.status_label === 'Dipanggil' ? 'ds-badge-live' : 'ds-badge-warn'} list-aside">${esc(item.level_label)}</span>
                    </div>`;
            });
            document.getElementById('callings').innerHTML =
                callHtml || '<div class="muted">Tidak ada panggilan aktif.</div>';

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

            // Seni
            let perfHtml = '';
            let lastPerfGroup = null;
            (data.performances || []).forEach(function(item) {
                const group = item.schedule_key || (item.match_no + '|' + item.category);
                if (group !== lastPerfGroup) {
                    lastPerfGroup = group;
                    perfHtml += `<div class="list-group-label">${esc(item.match_no)} &middot; ${esc(item.category)}</div>`;
                }
                perfHtml += `
                    <div class="list-row">
                        <div class="list-main">
                            <div class="list-title">${esc(item.order_no)}. ${esc(item.athlete)} <span class="code">${esc(item.contingent)}</span></div>
                            <div class="list-sub">${esc(item.category)}</div>
                        </div>
                        <div class="list-aside">
                            <div class="perf-score">${item.score !== null && item.score !== undefined ? esc(item.score) : '-'}</div>
                            <div class="list-sub">${esc(item.status)}</div>
                        </div>
                    </div>`;
            });
            document.getElementById('performances').innerHTML =
                perfHtml || '<div class="list-row muted">Belum ada penampil.</div>';

            // Jadwal berikutnya
            let nextHtml = '';
            (data.next || []).forEach(function(item) {
                nextHtml += `
                    <div class="list-row">
                        <div class="list-main">
                            <div class="list-title">${esc(item.match_no)} <span class="muted">${esc(item.time)}</span></div>
                            <div class="list-sub">${esc(item.category)} &middot; ${esc(item.arena)}</div>
                        </div>
                        <span class="badge ds-badge-info list-aside">${item.type === 'art' ? 'SENI' : 'DAERYUN'}</span>
                    </div>`;
            });
            document.getElementById('next-list').innerHTML =
                nextHtml || '<div class="list-row muted">Tidak ada jadwal.</div>';

            renderJuara(data.juara);

            const stamp = document.getElementById('last-update');
            if (stamp) {
                stamp.textContent = 'Diperbarui ' + (data.now || '') + ':' + String(new Date().getSeconds()).padStart(2, '0');
            }
        };

        const juaraCard = function(item) {
            const medal = MEDAL_EMOJI[item.medal] || '\u{1F3C5}';
            const score = (item.score !== null && item.score !== undefined)
                ? `<div class="juara-score">${esc(item.score)}</div>` : '';
            return `
                <div class="juara-card rank-${esc(item.rank)}">
                    <div class="juara-medal">${medal}</div>
                    <div class="juara-rank">Juara ${esc(item.rank)}</div>
                    <div class="juara-name">${esc(item.name)}</div>
                    <div class="juara-cont">${esc(item.contingent_code || '')} &middot; ${esc(item.contingent || '')}</div>
                    ${score}
                </div>`;
        };

        const juaraGroupHtml = function(group, title) {
            let html = `<div class="juara-group"><div class="juara-group-title">${title} &middot; ${esc(group.category)}</div><div class="juara-row">`;
            (group.items || []).forEach(function(item) {
                html += juaraCard(item);
            });
            return html + '</div></div>';
        };

        const renderJuara = function(juara) {
            const body = document.getElementById('juara-body');
            if (!body) return;

            const daeryun = (juara && juara.daeryun) || [];
            const art = (juara && juara.art) || [];
            const total = (juara && juara.total) || [];

            if (!daeryun.length && !art.length) {
                body.innerHTML = '<div class="muted">Belum ada juara pada kategori selesai.</div>';
                return;
            }

            let html = '<div class="juara-groups">';
            daeryun.forEach(function(group) {
                html += juaraGroupHtml(group, 'Daeryun');
            });
            art.forEach(function(group) {
                html += juaraGroupHtml(group, 'Seni');
            });
            html += '</div>';

            if (total.length) {
                html += '<div class="juara-group-title" style="margin-top:var(--ds-stack)">Perolehan Medali Kontingen</div><div class="juara-total-row">';
                total.forEach(function(row) {
                    html += `
                        <div class="total-card${row.rank <= 3 ? ' top' : ''}">
                            <div class="total-rank">#${esc(row.rank)}</div>
                            <div class="total-name">${esc(row.contingent || row.code || '')}</div>
                            <div class="total-medals">
                                <span>${MEDAL_EMOJI.emas} ${esc(row.emas)}</span>
                                <span>${MEDAL_EMOJI.perak} ${esc(row.perak)}</span>
                                <span>${MEDAL_EMOJI.perunggu} ${esc(row.perunggu)}</span>
                            </div>
                            <div class="total-sum">${esc(row.total)} medali &middot; ${esc(row.poin)} poin</div>
                        </div>`;
                });
                html += '</div>';
            }

            body.innerHTML = html;
        };

        const poll = function() {
            fetch('{{ route('display.data') }}', { headers: { Accept: 'application/json' }, cache: 'no-store' })
                .then(function(r) { return r.ok ? r.json() : null; })
                .then(function(data) { if (data) render(data); })
                .catch(function() {});
        };
        poll();
        setInterval(poll, 3000);
    </script>
</body>

</html>
