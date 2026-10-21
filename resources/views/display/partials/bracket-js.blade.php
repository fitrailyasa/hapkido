{{-- Renders the bracket of every daeryun arena with brackets-viewer.js. --}}
<script src="{{ asset('vendor/brackets/brackets-viewer.js') }}"></script>
<script>
    window.dsBracketState = window.dsBracketState || {};

    window.dsRenderBrackets = function(items) {
        const list = document.getElementById('bracket-list');

        if (!list) return;

        const esc = window.dsEsc || function(value) { return value === null || value === undefined ? '' : value; };

        items = (items || []).filter(function(item) { return item && item.bracket; });

        if (!items.length) {
            list.innerHTML = '';
            list.dataset.ids = '';
            return;
        }

        const ids = items.map(function(item) { return String(item.id); }).join(',');

        // The panels are only rebuilt when an arena is added or removed.
        if (list.dataset.ids !== ids) {
            list.dataset.ids = ids;
            list.innerHTML = items.map(function(item) {
                return `
                    <div class="panel bracket-panel">
                        <div class="panel-title">
                            <i class="bi bi-diagram-3"></i> Bracket &middot; ${esc(item.label || item.name || '')}
                            &middot; ${esc(item.bracket.category || '')}
                        </div>
                        <div class="panel-body">
                            <div class="bracket-scroll">
                                <div class="brackets-viewer" id="bracket-arena-${item.id}"></div>
                            </div>
                        </div>
                    </div>`;
            }).join('');
        }

        items.forEach(function(item) {
            const key = String(item.id);
            const signature = JSON.stringify(item.bracket.viewer);

            // Skip the re-render when nothing changed on the bracket.
            if (window.dsBracketState[key] === signature) return;
            window.dsBracketState[key] = signature;

            const selector = '#bracket-arena-' + key;
            const root = document.querySelector(selector);

            if (!root || !window.bracketsViewer) return;

            window.bracketsViewer.render(item.bracket.viewer, {
                clear: true,
                selector: selector,
                customRoundName: function(info) {
                    const labels = item.bracket.roundLabels || [];
                    return labels[info.roundNumber - 1] || ('Babak ' + info.roundNumber);
                }
            }).then(function() {
                (item.bracket.byeIds || []).forEach(function(id) {
                    const el = root.querySelector('[data-match-id="' + id + '"]');
                    if (!el) return;

                    el.classList.add('bracket-bye');

                    const badge = document.createElement('span');
                    badge.className = 'bracket-badge';
                    badge.textContent = 'BYE';
                    badge.title = 'Peserta lolos otomatis tanpa lawan (BYE)';
                    el.appendChild(badge);
                });

                (item.bracket.emptyIds || []).forEach(function(id) {
                    const el = root.querySelector('[data-match-id="' + id + '"]');
                    if (!el) return;

                    el.classList.add('bracket-empty');
                    el.title = 'Slot kosong - tidak ada peserta';
                });
            }).catch(function() {});
        });
    };
</script>
