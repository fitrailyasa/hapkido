<script>
    (function() {
        var esc = function(value) {
            return String(value === null || value === undefined ? '' : value)
                .replace(/[&<>"]/g, function(c) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c];
                });
        };

        var initials = function(name) {
            var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
            if (!parts.length) return '?';
            var out = parts[0].charAt(0);
            if (parts.length > 1) out += parts[parts.length - 1].charAt(0);
            return out.toUpperCase();
        };

        var avatarInner = function(photo, name) {
            var img = photo ? '<img src="' + esc(photo) + '" alt="" onerror="this.remove()">' : '';
            return img + '<span class="ds-initials">' + esc(initials(name)) + '</span>';
        };

        var hasName = function(side) {
            return !!(side && side.name);
        };

        var sideHtml = function(side, color, tag, state) {
            side = side || {};
            var badge = state === 'is-winner'
                ? '<div class="match-side-badge"><i class="bi bi-trophy-fill"></i> PEMENANG</div>'
                : '';
            return '<div class="match-side match-side--' + color + (state ? ' ' + state : '') + '">'
                + badge
                + '<div class="match-avatar ds-avatar">' + avatarInner(side.photo, side.name) + '</div>'
                + '<div class="match-side-tag">' + esc(tag) + '</div>'
                + '<div class="match-side-name">' + esc(side.name || 'TBD') + '</div>'
                + '<div class="match-side-code">' + esc(side.code || '—') + '</div>'
                + '</div>';
        };

        var centerHtml = function(cfg, isArt) {
            var hasScore = cfg.scoreA !== null && cfg.scoreA !== undefined
                && cfg.scoreB !== null && cfg.scoreB !== undefined;
            var score = hasScore
                ? '<div class="match-center-score">' + esc(cfg.scoreA) + ' : ' + esc(cfg.scoreB) + '</div>'
                : '';
            var done = !!(cfg.winner && cfg.winner.name);
            var mark;

            if (isArt) {
                mark = '<div class="match-center-vs is-type">SENI</div>';
            } else if (done) {
                mark = '<div class="match-center-vs is-done"><i class="bi bi-trophy-fill"></i></div>';
            } else {
                mark = '<div class="match-center-vs">VS</div>';
            }

            return '<div class="match-center">'
                + '<div class="match-center-arena" title="' + esc(cfg.arena || '') + '">' + esc(cfg.arena || '') + '</div>'
                + '<div class="match-center-category">' + esc(cfg.category || '') + '</div>'
                + '<div class="match-center-no">' + esc(cfg.matchNo || '') + '</div>'
                + mark + score
                + '</div>';
        };

        var performersHtml = function(list) {
            list = list || [];
            if (!list.length) return '';
            var rows = '';
            list.forEach(function(p, i) {
                var order = (p.order_no === null || p.order_no === undefined) ? i + 1 : p.order_no;
                rows += '<div class="match-performer' + (p.is_performing ? ' is-performing' : '') + '">'
                    + '<div class="match-performer-order">' + esc(String(order).padStart(2, '0')) + '</div>'
                    + '<div class="match-performer-main">'
                    + '<div class="match-avatar match-avatar--sm ds-avatar">' + avatarInner(p.photo, p.athlete) + '</div>'
                    + '<div class="match-performer-info">'
                    + '<div class="match-performer-name">' + esc(p.athlete) + '</div>'
                    + '<div class="match-performer-code">' + esc(p.contingent || '') + '</div>'
                    + '</div></div>'
                    + '<div class="match-performer-status">' + esc(p.status || '') + '</div>'
                    + '</div>';
            });
            return '<div class="match-performers">' + rows + '</div>';
        };

        var emptyHtml = function(cfg) {
            return '<div class="match-body is-empty"><div class="match-empty">'
                + '<i class="bi bi-calendar-x"></i>'
                + '<div class="match-empty-title">' + esc(cfg.emptyTitle || 'BELUM ADA PARTAI') + '</div>'
                + '<div class="match-empty-sub">' + esc(cfg.emptySub || '') + '</div>'
                + '</div></div>';
        };

        var statusHtml = function(cfg) {
            var label = String(cfg.status || 'Menunggu').toUpperCase();
            var done = !!(cfg.winner && cfg.winner.name);
            return '<div class="match-status' + (done ? ' is-done' : '') + '">'
                + '<span class="match-status-dot"></span>'
                + '<span>STATUS : ' + esc(label) + '</span>'
                + '</div>';
        };

        var winnerHtml = function(cfg) {
            var w = cfg.winner;
            if (!w || !w.name) return '';
            var side = w.side === 'b' ? 'side-blue' : 'side-red';
            var hasScore = cfg.scoreA !== null && cfg.scoreA !== undefined
                && cfg.scoreB !== null && cfg.scoreB !== undefined;
            var score = hasScore
                ? '<div class="match-winner-score">SKOR ' + esc(cfg.scoreA) + ' : ' + esc(cfg.scoreB) + '</div>'
                : '';
            return '<div class="match-winner">'
                + '<div class="match-winner-head"><i class="bi bi-trophy-fill"></i> PEMENANG</div>'
                + '<div class="match-winner-name ' + side + '">' + esc(w.name)
                + (w.code ? '<span class="match-winner-code">' + esc(w.code) + '</span>' : '')
                + '</div>'
                + score
                + '<div class="match-winner-caption">' + (w.isFinal ? 'JUARA' : 'LANJUT KE BABAK BERIKUTNYA') + '</div>'
                + '</div>';
        };

        var headHtml = function(cfg) {
            var meta = [];
            if (cfg.isLive) {
                meta.push('<span class="match-live-tag"><span class="live-dot"></span> Berlangsung</span>');
            }
            if (cfg.meta) {
                meta.push('<span>' + esc(cfg.meta) + '</span>');
            }
            return '<div class="match-frame-head">'
                + '<span class="match-frame-title"><i class="bi bi-broadcast"></i> ' + esc(cfg.title || 'Partai') + '</span>'
                + '<span class="match-frame-meta">' + meta.join('') + '</span>'
                + '</div>';
        };

        window.dsFrame = function(cfg) {
            cfg = cfg || {};
            var isArt = cfg.type === 'art';
            var winner = cfg.winner && cfg.winner.name ? cfg.winner : null;
            var stateA = winner ? (winner.side === 'a' ? 'is-winner' : 'is-loser') : '';
            var stateB = winner ? (winner.side === 'b' ? 'is-winner' : 'is-loser') : '';
            var body;

            if (isArt) {
                var performers = performersHtml(cfg.performers);
                body = performers
                    ? '<div class="match-body is-art">' + centerHtml(cfg, true) + performers + '</div>'
                    : emptyHtml(cfg);
            } else if (hasName(cfg.a) || hasName(cfg.b)) {
                body = '<div class="match-body">'
                    + sideHtml(cfg.a, 'red', 'Merah', stateA)
                    + centerHtml(cfg, false)
                    + sideHtml(cfg.b, 'blue', 'Biru', stateB)
                    + '</div>';
            } else {
                body = emptyHtml(cfg);
            }

            return '<div class="match-frame'
                + (cfg.isLive ? ' is-live' : '')
                + (winner ? ' is-done' : '')
                + '">'
                + headHtml(cfg)
                + body
                + statusHtml(cfg)
                + winnerHtml(cfg)
                + '</div>';
        };

        window.dsRenderFrame = function(elementId, cfg) {
            var el = document.getElementById(elementId);
            if (el) el.innerHTML = window.dsFrame(cfg);
        };

        window.dsEsc = esc;
        window.dsAvatarInner = avatarInner;
    })();
</script>
