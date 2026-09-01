// Widget de chat de WhatsApp: abre/cierra el panel; cada opción abre WhatsApp.
(function () {
    'use strict';

    var widget = document.querySelector('[data-wa-widget]');
    if (!widget) { return; }

    var toggle = widget.querySelector('[data-wa-toggle]');
    var closeBtn = widget.querySelector('[data-wa-close]');

    function isOpen() { return widget.classList.contains('is-open'); }

    function open() {
        widget.classList.add('is-open');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-label', 'Cerrar chat de WhatsApp');
        }
    }

    function close() {
        widget.classList.remove('is-open');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Abrir chat de WhatsApp');
        }
    }

    if (toggle) {
        toggle.addEventListener('click', function () { isOpen() ? close() : open(); });
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', close);
    }

    // Cerrar con Escape o al hacer clic fuera del widget.
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen()) { close(); }
    });
    document.addEventListener('click', function (e) {
        if (isOpen() && !widget.contains(e.target)) { close(); }
    });
})();
