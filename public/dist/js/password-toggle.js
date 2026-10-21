/**
 * Show/hide toggle for every password field.
 *
 * The script wraps each password input in a relative container and appends an
 * eye button next to it. It runs once per page (all forms are static) and can
 * be called again with window.enhancePasswordFields() when a field is added
 * later on. Works in the guest, profile and admin layouts, so the icons are
 * plain inline SVG (no icon font is available on every layout).
 */
(function () {
    'use strict';

    var EYE =
        '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" ' +
        'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' +
        '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>' +
        '<circle cx="12" cy="12" r="3"></circle>' +
        '</svg>';

    var EYE_OFF =
        '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" ' +
        'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' +
        '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94' +
        'M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19' +
        'm-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>' +
        '<line x1="1" y1="1" x2="23" y2="23"></line>' +
        '</svg>';

    var SHOW_LABEL = 'Tampilkan password';
    var HIDE_LABEL = 'Sembunyikan password';

    function injectStyles() {
        if (document.getElementById('password-toggle-styles')) {
            return;
        }

        var style = document.createElement('style');
        style.id = 'password-toggle-styles';
        style.textContent = [
            '.password-field{position:relative;}',
            '.password-field>input[type="password"],',
            '.password-field>input[type="text"]{padding-right:2.75rem;}',
            '.password-field>input.is-invalid{background-image:none;}',
            '.password-field-btn{',
            'position:absolute;top:50%;right:.35rem;transform:translateY(-50%);',
            'display:inline-flex;align-items:center;justify-content:center;',
            'width:2.25rem;height:2.25rem;padding:0;border:0;border-radius:.375rem;',
            'background:transparent;color:inherit;opacity:.65;cursor:pointer;line-height:1;',
            '}',
            '.password-field-btn:hover{opacity:1;background:rgba(127,127,127,.15);}',
            '.password-field-btn:focus-visible{outline:2px solid currentColor;outline-offset:1px;opacity:1;}',
        ].join('');

        document.head.appendChild(style);
    }

    function enhance(input) {
        if (input.getAttribute('data-password-toggle') === '1') {
            return;
        }

        input.setAttribute('data-password-toggle', '1');

        var wrapper = document.createElement('div');
        wrapper.className = 'password-field';

        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'password-field-btn';
        button.innerHTML = EYE;
        button.setAttribute('aria-label', SHOW_LABEL);
        button.setAttribute('title', SHOW_LABEL);
        button.setAttribute('aria-controls', input.id || '');
        button.setAttribute('aria-pressed', 'false');

        button.addEventListener('click', function () {
            var visible = input.type === 'text';

            input.type = visible ? 'password' : 'text';
            button.innerHTML = visible ? EYE : EYE_OFF;
            button.setAttribute('aria-label', visible ? SHOW_LABEL : HIDE_LABEL);
            button.setAttribute('title', visible ? SHOW_LABEL : HIDE_LABEL);
            button.setAttribute('aria-pressed', visible ? 'false' : 'true');

            input.focus();
        });

        wrapper.appendChild(button);
    }

    function enhanceAll() {
        injectStyles();

        var inputs = document.querySelectorAll('input[type="password"]');

        for (var i = 0; i < inputs.length; i++) {
            enhance(inputs[i]);
        }
    }

    window.enhancePasswordFields = enhanceAll;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', enhanceAll);
    } else {
        enhanceAll();
    }
})();
