// Interacciones del portal de clientes.
(function () {
    'use strict';

    // Confirmación antes de enviar formularios destructivos (eliminar, rechazar, etc.).
    // Delegado (no onsubmit inline) porque la CSP del sitio no permite atributos
    // de evento inline: script-src usa nonce y no cubre atributos onXXX.
    document.addEventListener('submit', function (e) {
        var form = e.target.closest('[data-confirm]');
        if (form && !window.confirm(form.getAttribute('data-confirm'))) {
            e.preventDefault();
        }
    });

    /* ---- Menú móvil del portal (mismo patrón que el sitio público) ------- */
    var toggle = document.getElementById('portalNavToggle');
    var nav = document.getElementById('portalNav');
    var backdrop = document.getElementById('portalNavBackdrop');

    if (toggle && nav) {
        var setOpen = function (open) {
            nav.classList.toggle('is-open', open);
            if (backdrop) backdrop.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        toggle.addEventListener('click', function () {
            setOpen(!nav.classList.contains('is-open'));
        });
        nav.addEventListener('click', function (e) {
            if (e.target.closest('a')) setOpen(false);
        });
        if (backdrop) {
            backdrop.addEventListener('click', function () { setOpen(false); });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && nav.classList.contains('is-open')) {
                setOpen(false);
                toggle.focus();
            }
        });
    }
})();
