<style>
    /* ==================================================================
       Display publik — token, skala jarak, blok match (frame)
       Skala jarak: 4 / 8 / 12 / 16 / 20 / 24 / 28
       page-pad 16–24 · panel-pad 20–28 · stack 12–16 · card-gap 16
       ================================================================== */

    :root,
    [data-bs-theme="light"] {
        color-scheme: light;

        --ds-bg: #eef2fa;
        --ds-panel: #ffffff;
        --ds-line: #dce4f3;
        --ds-text: #243356;
        --ds-heading: #1b2a4a;
        --ds-muted: #5f7098;
        --ds-title: #2f6bdd;
        --ds-shadow: 0 8px 22px rgba(36, 51, 86, .08);
        --ds-hover: 0 12px 26px rgba(13, 110, 253, .18);

        /* emas gelap untuk tema terang (kontras di atas putih) */
        --ds-gold: #9a7b00;
        --ds-gold-soft: rgba(154, 123, 0, .14);

        --ds-red: #dc3545;
        --ds-red-deep: #b02a35;
        --ds-blue: #0d6efd;
        --ds-blue-deep: #0a3d91;
        --ds-ok: #198754;
        --ds-ok-deep: #146c43;
        --ds-info-bg: rgba(13, 202, 240, .20);
        --ds-info-text: #055160;
        --ds-warn-bg: rgba(255, 193, 7, .32);
        --ds-warn-text: #664d03;
        --ds-muted-bg: rgba(95, 112, 152, .16);

        /* skala jarak */
        --ds-space-1: 4px;
        --ds-space-2: 8px;
        --ds-space-3: 12px;
        --ds-space-4: 16px;
        --ds-space-5: 20px;
        --ds-space-6: 24px;
        --ds-space-7: 28px;
        --ds-page-pad: clamp(16px, 1.4vw, 24px);
        --ds-panel-pad: clamp(20px, 1.6vw, 28px);
        --ds-stack: clamp(12px, 1vw, 16px);
        --ds-card-gap: 16px;
    }

    [data-bs-theme="dark"] {
        color-scheme: dark;

        --ds-bg: #0b1220;
        --ds-panel: #131c2e;
        --ds-line: #22304a;
        --ds-text: #d7e1f5;
        --ds-heading: #eef3fc;
        --ds-muted: #94a6c6;
        --ds-title: #8fb4ff;
        --ds-shadow: 0 8px 22px rgba(0, 0, 0, .35);
        --ds-hover: 0 12px 28px rgba(13, 110, 253, .30);

        /* emas terang untuk tema gelap */
        --ds-gold: #ffd166;
        --ds-gold-soft: rgba(255, 209, 102, .16);

        --ds-info-bg: rgba(13, 202, 240, .22);
        --ds-info-text: #9eeef9;
        --ds-warn-bg: rgba(255, 193, 7, .26);
        --ds-warn-text: #ffe69c;
        --ds-muted-bg: rgba(148, 166, 198, .18);
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        background: var(--ds-bg);
        color: var(--ds-text);
        font-family: "Source Sans 3", "Segoe UI", system-ui, sans-serif;
    }

    .muted {
        color: var(--ds-muted);
    }

    /* ------------------------------------------------------------------
       Kerangka halaman
       ------------------------------------------------------------------ */

    .ds-shell {
        padding: var(--ds-page-pad);
        display: flex;
        flex-direction: column;
        gap: var(--ds-stack);
        min-width: 0;
    }

    .ds-columns {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(300px, 370px);
        gap: var(--ds-stack);
        align-items: stretch;
        min-width: 0;
    }

    .ds-col {
        display: flex;
        flex-direction: column;
        gap: var(--ds-stack);
        min-width: 0;
    }

    .ds-col>:last-child {
        flex: 1 1 auto;
        min-height: 0;
    }

    .ds-row-2 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: var(--ds-stack);
        min-width: 0;
    }

    /* ------------------------------------------------------------------
       Panel
       ------------------------------------------------------------------ */

    .panel {
        background: var(--ds-panel);
        border: 1px solid var(--ds-line);
        border-radius: 14px;
        box-shadow: var(--ds-shadow);
        display: flex;
        flex-direction: column;
        min-width: 0;
        overflow: hidden;
    }

    .panel-title {
        display: flex;
        align-items: center;
        gap: var(--ds-space-2);
        padding: var(--ds-space-3) var(--ds-space-5);
        font-size: 14px;
        font-weight: 800;
        letter-spacing: 2.5px;
        text-transform: uppercase;
        color: var(--ds-title);
        border-bottom: 1px solid var(--ds-line);
    }

    .panel-body {
        padding: var(--ds-panel-pad);
        min-width: 0;
    }

    /* ------------------------------------------------------------------
       Topbar
       ------------------------------------------------------------------ */

    .topbar {
        background: linear-gradient(90deg, #0d6efd, #0a3d91);
        padding: var(--ds-space-3) var(--ds-space-6);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: var(--ds-space-4);
        color: #fff;
        flex-wrap: wrap;
    }

    .topbar-left,
    .topbar-right {
        display: flex;
        align-items: center;
        gap: var(--ds-space-4);
        min-width: 0;
    }

    .topbar-title {
        font-size: clamp(18px, 1.5vw, 26px);
        font-weight: 800;
        letter-spacing: 1.5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .arena-heading {
        min-width: 0;
    }

    .arena-heading .arena-code {
        font-size: clamp(26px, 2.4vw, 40px);
        font-weight: 900;
        letter-spacing: 2px;
        line-height: 1.1;
    }

    .arena-heading .arena-title {
        font-size: 15px;
        font-weight: 600;
        opacity: .92;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: var(--ds-space-2);
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .35);
        color: #fff;
        border-radius: 8px;
        padding: var(--ds-space-2) var(--ds-space-4);
        font-size: 15px;
        font-weight: 700;
        text-decoration: none;
        white-space: nowrap;
        transition: background .15s ease;
    }

    .back-btn:hover,
    .back-btn:focus {
        background: rgba(255, 255, 255, .3);
        color: #fff;
    }

    .clock {
        font-size: clamp(18px, 1.5vw, 24px);
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .last-update {
        font-size: 14px;
        font-weight: 600;
        opacity: .9;
        white-space: nowrap;
    }

    .live-pill {
        background: var(--ds-red);
        color: #fff;
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 1.5px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    .live-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: #fff;
        display: inline-block;
        animation: dsPulse 1.2s ease-in-out infinite;
    }

    @keyframes dsPulse {

        0%,
        100% {
            opacity: 1;
            transform: scale(1);
        }

        50% {
            opacity: .35;
            transform: scale(.7);
        }
    }

    .theme-btn {
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .35);
        color: #fff;
        border-radius: 8px;
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 19px;
        cursor: pointer;
        transition: background .15s ease;
        flex: none;
    }

    .theme-btn:hover {
        background: rgba(255, 255, 255, .3);
    }

    /* ------------------------------------------------------------------
       Pindah arena
       ------------------------------------------------------------------ */

    .arena-switch {
        display: flex;
        flex-wrap: wrap;
        gap: var(--ds-space-3);
        padding: var(--ds-space-4) var(--ds-space-6);
        background: var(--ds-panel);
        border-bottom: 1px solid var(--ds-line);
    }

    .arena-chip {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        border: 1px solid var(--ds-line);
        background: var(--ds-bg);
        color: var(--ds-text);
        border-radius: 20px;
        padding: 7px 16px;
        font-size: 15px;
        font-weight: 700;
        text-decoration: none;
        transition: all .15s ease;
    }

    .arena-chip:hover {
        border-color: var(--ds-blue);
        box-shadow: var(--ds-hover);
        color: var(--ds-text);
    }

    .arena-chip.active {
        background: linear-gradient(135deg, var(--ds-blue), var(--ds-blue-deep));
        border-color: var(--ds-blue);
        color: #fff;
    }

    /* ------------------------------------------------------------------
       Badge
       ------------------------------------------------------------------ */

    .badge {
        font-weight: 800;
        letter-spacing: 1px;
        white-space: nowrap;
    }

    .badge.ds-badge-live {
        background: var(--ds-red);
        color: #fff;
    }

    .badge.ds-badge-ok {
        background: var(--ds-ok);
        color: #fff;
    }

    .badge.ds-badge-muted {
        background: var(--ds-muted-bg);
        color: var(--ds-text);
    }

    .badge.ds-badge-info {
        background: var(--ds-info-bg);
        color: var(--ds-info-text);
    }

    .badge.ds-badge-warn {
        background: var(--ds-warn-bg);
        color: var(--ds-warn-text);
    }

    /* ------------------------------------------------------------------
       Blok match (frame): [merah] [info] [biru] + status + pemenang
       ------------------------------------------------------------------ */

    .match-frame {
        background: var(--ds-panel);
        border: 2px solid var(--ds-line);
        border-radius: 18px;
        box-shadow: var(--ds-shadow);
        padding: var(--ds-panel-pad);
        display: flex;
        flex-direction: column;
        gap: var(--ds-stack);
        min-width: 0;
    }

    .match-frame.is-live {
        border-color: rgba(220, 53, 69, .70);
        box-shadow: 0 0 0 4px rgba(220, 53, 69, .14), var(--ds-shadow);
    }

    /* ---------------------------------------------------------------
       Tampilan khusus saat partai sudah selesai / ada pemenang
       --------------------------------------------------------------- */
    .match-frame.is-done {
        border-color: rgba(255, 193, 7, .60);
        box-shadow: 0 0 0 4px rgba(255, 193, 7, .16), var(--ds-shadow);
    }

    .match-frame.is-done .match-frame-title {
        color: var(--ds-gold);
    }

    .match-side.is-winner {
        outline: 3px solid var(--ds-gold);
        outline-offset: -3px;
        box-shadow: 0 0 0 6px rgba(255, 193, 7, .22), 0 18px 36px rgba(255, 193, 7, .32);
        z-index: 1;
    }

    .match-side.is-loser {
        filter: grayscale(.85) brightness(.72);
        opacity: .62;
    }

    .match-side-badge {
        position: absolute;
        top: 10px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 3;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, var(--ds-gold), #e0a800);
        color: #241a00;
        border-radius: 999px;
        padding: 4px 14px;
        font-size: clamp(10px, .75vw, 13px);
        font-weight: 900;
        letter-spacing: 2px;
        text-transform: uppercase;
        white-space: nowrap;
        box-shadow: 0 8px 18px rgba(0, 0, 0, .35);
    }

    .match-center-vs.is-done {
        color: var(--ds-gold);
        font-size: clamp(40px, 4vw, 88px);
        line-height: 1;
    }

    .match-status.is-done {
        background: linear-gradient(90deg, #b8860b, var(--ds-gold));
        color: #241a00;
    }

    .match-status.is-done .match-status-dot {
        background: #241a00;
    }

    .match-frame.is-done .match-winner {
        border-color: rgba(255, 193, 7, .60);
        background: linear-gradient(180deg, rgba(255, 193, 7, .16), transparent 70%), var(--ds-panel);
    }

    .match-winner-score {
        font-size: clamp(15px, 1.4vw, 24px);
        font-weight: 900;
        letter-spacing: 4px;
        color: var(--ds-title);
        font-variant-numeric: tabular-nums;
    }

    .match-frame-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: var(--ds-space-3);
        padding-bottom: var(--ds-space-3);
        border-bottom: 1px solid var(--ds-line);
    }

    .match-frame-title {
        display: inline-flex;
        align-items: center;
        gap: var(--ds-space-2);
        font-size: clamp(13px, .9vw, 16px);
        font-weight: 800;
        letter-spacing: 3px;
        text-transform: uppercase;
        color: var(--ds-muted);
    }

    .match-frame.is-live .match-frame-title {
        color: var(--ds-red);
    }

    .match-frame-meta {
        display: flex;
        align-items: center;
        gap: var(--ds-space-3);
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: var(--ds-muted);
        min-width: 0;
    }

    .match-live-tag {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: var(--ds-red);
        color: #fff;
        border-radius: 999px;
        padding: 4px 12px;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: 2px;
    }

    .match-body {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(240px, 34%) minmax(0, 1fr);
        gap: var(--ds-card-gap);
        align-items: stretch;
    }

    .match-body.is-art,
    .match-body.is-empty {
        grid-template-columns: minmax(0, 1fr);
    }

    .match-side {
        position: relative;
        overflow: hidden;
        border-radius: 16px;
        padding: clamp(16px, 1.6vw, 28px) clamp(14px, 1.4vw, 24px);
        color: #fff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--ds-space-3);
        text-align: center;
        min-width: 0;
    }

    .match-side::after {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(120% 80% at 50% 0%, rgba(255, 255, 255, .20), transparent 62%);
        pointer-events: none;
    }

    .match-side--red {
        background: linear-gradient(160deg, var(--ds-red) 0%, var(--ds-red-deep) 100%);
        box-shadow: 0 14px 30px rgba(220, 53, 69, .30);
    }

    .match-side--blue {
        background: linear-gradient(160deg, var(--ds-blue) 0%, var(--ds-blue-deep) 100%);
        box-shadow: 0 14px 30px rgba(13, 110, 253, .30);
    }

    .ds-avatar {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        overflow: hidden;
        flex: none;
        background: rgba(255, 255, 255, .22);
    }

    .ds-avatar img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .ds-initials {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        letter-spacing: 1px;
        color: #fff;
    }

    .match-avatar {
        width: clamp(64px, 6vw, 112px);
        height: clamp(64px, 6vw, 112px);
        border: 3px solid rgba(255, 255, 255, .55);
        font-size: clamp(22px, 2vw, 38px);
    }

    .match-avatar--sm {
        width: clamp(40px, 3vw, 54px);
        height: clamp(40px, 3vw, 54px);
        border-width: 2px;
        font-size: clamp(15px, 1.2vw, 20px);
        background: var(--ds-muted-bg);
    }

    .match-avatar--sm .ds-initials {
        color: var(--ds-title);
    }

    .match-side-tag {
        font-size: clamp(11px, .8vw, 13px);
        font-weight: 800;
        letter-spacing: 4px;
        text-transform: uppercase;
        opacity: .88;
    }

    .match-side-name {
        font-size: clamp(20px, 1.9vw, 38px);
        font-weight: 900;
        line-height: 1.12;
        max-width: 100%;
        overflow-wrap: anywhere;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .match-side-code {
        max-width: 100%;
        padding: 4px 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .22);
        font-size: clamp(13px, 1vw, 18px);
        font-weight: 800;
        letter-spacing: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .match-center {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--ds-space-2);
        text-align: center;
        min-width: 0;
        padding: var(--ds-space-2) 0;
    }

    .match-center-arena {
        max-width: 100%;
        font-size: clamp(12px, .9vw, 16px);
        font-weight: 800;
        letter-spacing: 4px;
        text-transform: uppercase;
        color: var(--ds-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .match-center-category {
        max-width: 100%;
        font-size: clamp(20px, 1.9vw, 36px);
        font-weight: 900;
        line-height: 1.15;
        color: var(--ds-heading);
        overflow-wrap: anywhere;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .match-center-no {
        font-size: clamp(14px, 1.1vw, 20px);
        font-weight: 800;
        letter-spacing: 3px;
        text-transform: uppercase;
        color: var(--ds-title);
    }

    .match-center-vs {
        margin-top: var(--ds-space-2);
        font-size: clamp(56px, 5.6vw, 124px);
        font-weight: 900;
        line-height: .9;
        letter-spacing: 2px;
        color: var(--ds-text);
    }

    .match-center-vs.is-type {
        font-size: clamp(34px, 3.2vw, 64px);
        letter-spacing: 10px;
        color: var(--ds-title);
    }

    .match-center-score {
        font-size: clamp(24px, 2.2vw, 44px);
        font-weight: 900;
        font-variant-numeric: tabular-nums;
        color: var(--ds-title);
        line-height: 1;
    }

    .match-status {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--ds-space-3);
        width: 100%;
        background: linear-gradient(90deg, var(--ds-ok-deep), var(--ds-ok));
        color: #fff;
        border-radius: 12px;
        padding: clamp(10px, .9vw, 16px) var(--ds-space-5);
        font-size: clamp(15px, 1.3vw, 22px);
        font-weight: 800;
        letter-spacing: 3px;
        text-align: center;
        text-transform: uppercase;
    }

    .match-status-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #fff;
        flex: none;
    }

    .match-frame.is-live .match-status-dot {
        animation: dsPulse 1.2s ease-in-out infinite;
    }

    .match-winner {
        border: 2px solid var(--ds-line);
        border-radius: 16px;
        background: linear-gradient(180deg, var(--ds-gold-soft), transparent 65%), var(--ds-panel);
        padding: var(--ds-space-5);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--ds-space-3);
        min-width: 0;
    }

    .match-winner-head {
        display: inline-flex;
        align-items: center;
        gap: var(--ds-space-2);
        font-size: clamp(13px, 1vw, 17px);
        font-weight: 900;
        letter-spacing: 5px;
        text-transform: uppercase;
        color: var(--ds-gold);
    }

    .match-winner-name {
        width: 100%;
        text-align: center;
        color: #fff;
        border-radius: 14px;
        padding: clamp(12px, 1.2vw, 20px) var(--ds-space-5);
        font-size: clamp(24px, 2.6vw, 48px);
        font-weight: 900;
        line-height: 1.1;
        overflow-wrap: anywhere;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .match-winner-name.side-red {
        background: linear-gradient(135deg, var(--ds-red), var(--ds-red-deep));
        box-shadow: 0 12px 26px rgba(220, 53, 69, .30);
    }

    .match-winner-name.side-blue {
        background: linear-gradient(135deg, var(--ds-blue), var(--ds-blue-deep));
        box-shadow: 0 12px 26px rgba(13, 110, 253, .30);
    }

    .match-winner-code {
        display: block;
        margin-top: 6px;
        font-size: clamp(13px, 1vw, 17px);
        font-weight: 800;
        letter-spacing: 3px;
        opacity: .88;
    }

    .match-winner-caption {
        font-size: clamp(12px, .95vw, 15px);
        font-weight: 800;
        letter-spacing: 3px;
        text-transform: uppercase;
        text-align: center;
        color: var(--ds-muted);
    }

    .match-performers {
        grid-column: 1 / -1;
        display: flex;
        flex-direction: column;
        gap: var(--ds-space-3);
        min-width: 0;
    }

    .match-performer {
        display: flex;
        align-items: center;
        gap: var(--ds-space-4);
        background: var(--ds-bg);
        border: 1px solid var(--ds-line);
        border-radius: 14px;
        padding: var(--ds-space-3) var(--ds-space-4);
        min-width: 0;
    }

    .match-performer.is-performing {
        border-color: var(--ds-blue);
        background: rgba(13, 110, 253, .10);
    }

    .match-performer-order {
        min-width: 42px;
        text-align: center;
        font-size: clamp(18px, 1.5vw, 26px);
        font-weight: 900;
        font-variant-numeric: tabular-nums;
        color: var(--ds-title);
    }

    .match-performer-main {
        flex: 1 1 auto;
        display: flex;
        align-items: center;
        gap: var(--ds-space-3);
        min-width: 0;
    }

    .match-performer-info {
        min-width: 0;
    }

    .match-performer-name {
        font-size: clamp(17px, 1.4vw, 26px);
        font-weight: 800;
        line-height: 1.2;
        overflow-wrap: anywhere;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .match-performer-code {
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 2px;
        color: var(--ds-muted);
    }

    .match-performer-status {
        font-size: clamp(12px, .95vw, 15px);
        font-weight: 800;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: var(--ds-muted);
        white-space: nowrap;
    }

    .match-empty {
        grid-column: 1 / -1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--ds-space-3);
        padding: clamp(24px, 3vw, 52px) var(--ds-space-4);
        text-align: center;
        color: var(--ds-muted);
    }

    .match-empty i {
        font-size: clamp(34px, 3vw, 54px);
    }

    .match-empty-title {
        font-size: clamp(18px, 1.7vw, 30px);
        font-weight: 900;
        letter-spacing: 3px;
        color: var(--ds-text);
    }

    .match-empty-sub {
        font-size: clamp(14px, 1.1vw, 18px);
        font-weight: 700;
        letter-spacing: 1px;
    }

    /* ------------------------------------------------------------------
       Kartu arena (ringkas)
       ------------------------------------------------------------------ */

    .arena-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: var(--ds-card-gap);
        min-width: 0;
    }

    .arena-card {
        display: flex;
        flex-direction: column;
        gap: var(--ds-space-3);
        padding: var(--ds-space-4);
        background: var(--ds-panel);
        border: 1px solid var(--ds-line);
        border-radius: 14px;
        box-shadow: var(--ds-shadow);
        text-decoration: none;
        color: inherit;
        min-width: 0;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }

    .arena-card:hover,
    .arena-card:focus {
        transform: translateY(-4px);
        box-shadow: var(--ds-hover);
        border-color: var(--ds-blue);
        color: inherit;
    }

    .arena-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--ds-space-3);
        min-width: 0;
    }

    .arena-card-name {
        font-size: clamp(18px, 1.3vw, 24px);
        font-weight: 900;
        letter-spacing: 1px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .arena-card-meta {
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: var(--ds-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .arena-card-foot {
        margin-top: auto;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: var(--ds-title);
    }

    .mini-vs {
        display: flex;
        flex-direction: column;
        gap: var(--ds-space-2);
        min-width: 0;
    }

    .mini-side {
        display: flex;
        align-items: center;
        gap: var(--ds-space-3);
        min-width: 0;
        padding: var(--ds-space-2) var(--ds-space-3);
        border-radius: 10px;
    }

    .mini-side.red {
        background: rgba(220, 53, 69, .12);
    }

    .mini-side.blue {
        background: rgba(13, 110, 253, .12);
    }

    .mini-side.neutral {
        background: var(--ds-muted-bg);
    }

    .mini-avatar {
        width: 36px;
        height: 36px;
        font-size: 14px;
    }

    .mini-avatar.red {
        background: linear-gradient(135deg, var(--ds-red), var(--ds-red-deep));
    }

    .mini-avatar.blue {
        background: linear-gradient(135deg, var(--ds-blue), var(--ds-blue-deep));
    }

    .mini-avatar.neutral {
        background: var(--ds-muted-bg);
    }

    .mini-avatar.neutral .ds-initials {
        color: var(--ds-title);
    }

    .mini-order {
        min-width: 24px;
        text-align: center;
        font-weight: 900;
        font-variant-numeric: tabular-nums;
        color: var(--ds-title);
        flex: none;
    }

    .mini-name {
        flex: 1 1 auto;
        min-width: 0;
        font-size: clamp(15px, 1.05vw, 18px);
        font-weight: 800;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .mini-code {
        flex: none;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 1px;
        color: var(--ds-muted);
    }

    .mini-divider {
        display: flex;
        align-items: center;
        gap: var(--ds-space-3);
        font-size: 12px;
        font-weight: 900;
        letter-spacing: 3px;
        color: var(--ds-muted);
    }

    .mini-divider::before,
    .mini-divider::after {
        content: '';
        flex: 1 1 auto;
        height: 1px;
        background: var(--ds-line);
    }

    /* ------------------------------------------------------------------
       Baris daftar
       ------------------------------------------------------------------ */

    .list-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--ds-space-3);
        padding: var(--ds-space-3) var(--ds-space-5);
        border-bottom: 1px solid var(--ds-line);
        min-width: 0;
    }

    .list-row:last-child {
        border-bottom: 0;
    }

    .list-row.highlight {
        background: rgba(13, 110, 253, .12);
    }

    .list-group-label {
        padding: var(--ds-space-3) var(--ds-space-5) var(--ds-space-2);
        background: var(--ds-muted-bg);
        border-bottom: 1px solid var(--ds-line);
        font-size: clamp(11px, .8vw, 13px);
        font-weight: 900;
        letter-spacing: 2.5px;
        text-transform: uppercase;
        color: var(--ds-title);
    }

    .list-main {
        min-width: 0;
    }

    .list-title {
        font-size: clamp(15px, 1.05vw, 18px);
        font-weight: 800;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }

    .list-sub {
        font-size: 13px;
        font-weight: 600;
        color: var(--ds-muted);
        overflow-wrap: anywhere;
    }

    .list-aside {
        flex: none;
        text-align: right;
        min-width: 0;
    }

    .call-big {
        font-size: clamp(20px, 1.7vw, 30px);
        font-weight: 800;
        color: var(--ds-gold);
        overflow-wrap: anywhere;
    }

    .code {
        display: inline-block;
        background: var(--ds-blue);
        color: #fff;
        border-radius: 4px;
        padding: 1px 7px;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 1px;
        vertical-align: middle;
    }

    .code.red {
        background: var(--ds-red);
    }

    .score {
        font-size: clamp(20px, 1.7vw, 28px);
        font-weight: 900;
        font-variant-numeric: tabular-nums;
        color: var(--ds-text);
    }

    .perf-name {
        font-size: clamp(16px, 1.2vw, 20px);
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .perf-score {
        font-size: clamp(20px, 1.7vw, 28px);
        font-weight: 900;
        font-variant-numeric: tabular-nums;
        color: var(--ds-title);
    }

    /* ------------------------------------------------------------------
       Juara 1-2-3
       ------------------------------------------------------------------ */

    .juara-groups {
        display: flex;
        flex-direction: column;
        gap: var(--ds-space-5);
    }

    .juara-group-title {
        font-size: 14px;
        font-weight: 800;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: var(--ds-title);
        margin-bottom: var(--ds-space-3);
    }

    .juara-row {
        display: flex;
        flex-wrap: wrap;
        gap: var(--ds-space-4);
    }

    .juara-card {
        flex: 1 1 210px;
        max-width: 320px;
        background: var(--ds-panel);
        border: 1px solid var(--ds-line);
        border-top: 5px solid #adb5bd;
        border-radius: 12px;
        padding: var(--ds-space-4) var(--ds-space-3);
        text-align: center;
        box-shadow: var(--ds-shadow);
        min-width: 0;
    }

    .juara-card.rank-1 {
        border-top-color: var(--ds-gold);
        background: linear-gradient(180deg, var(--ds-gold-soft), transparent 60%), var(--ds-panel);
    }

    .juara-card.rank-2 {
        border-top-color: #c9ced6;
        background: linear-gradient(180deg, rgba(201, 206, 214, .22), transparent 60%), var(--ds-panel);
    }

    .juara-card.rank-3 {
        border-top-color: #cd7f32;
        background: linear-gradient(180deg, rgba(205, 127, 50, .18), transparent 60%), var(--ds-panel);
    }

    .juara-medal {
        font-size: 36px;
        line-height: 1;
    }

    .juara-rank {
        margin-top: var(--ds-space-2);
        font-size: 15px;
        font-weight: 800;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: var(--ds-muted);
    }

    .juara-name {
        margin-top: var(--ds-space-1);
        font-size: 21px;
        font-weight: 800;
        line-height: 1.2;
        overflow-wrap: anywhere;
    }

    .juara-cont {
        margin-top: var(--ds-space-1);
        font-size: 14px;
        color: var(--ds-muted);
        overflow-wrap: anywhere;
    }

    .juara-score {
        margin-top: var(--ds-space-2);
        font-size: 17px;
        font-weight: 800;
        color: var(--ds-title);
    }

    .juara-total-row {
        display: flex;
        flex-wrap: wrap;
        gap: var(--ds-space-4);
    }

    .total-card {
        flex: 1 1 190px;
        max-width: 280px;
        background: var(--ds-panel);
        border: 1px solid var(--ds-line);
        border-radius: 12px;
        padding: var(--ds-space-4);
        box-shadow: var(--ds-shadow);
        min-width: 0;
    }

    .total-card.top {
        border-color: var(--ds-blue);
    }

    .total-rank {
        font-size: 14px;
        font-weight: 800;
        letter-spacing: 1px;
        color: var(--ds-muted);
    }

    .total-name {
        margin-bottom: var(--ds-space-2);
        font-size: 19px;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .total-medals {
        display: flex;
        flex-wrap: wrap;
        gap: var(--ds-space-3);
        font-size: 16px;
        font-weight: 700;
    }

    .total-sum {
        margin-top: var(--ds-space-2);
        font-size: 13px;
        color: var(--ds-muted);
    }

    /* ------------------------------------------------------------------
       Responsif
       ------------------------------------------------------------------ */

    @media (max-width: 1180px) {
        .ds-columns {
            grid-template-columns: minmax(0, 1fr);
        }

        .ds-col>:last-child {
            flex: 0 0 auto;
        }
    }

    @media (max-width: 1000px) {
        .match-body {
            grid-template-columns: minmax(0, 1fr);
        }

        .match-center {
            order: -1;
        }
    }
</style>
