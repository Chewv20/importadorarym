// Interacciones de la interfaz de reparto.
(function () {
    'use strict';

    // Confirmación antes de marcar "Entregado" (no hay forma de deshacerlo).
    // Delegado (no onsubmit inline) porque la CSP no permite atributos de
    // evento inline: script-src usa nonce y no cubre atributos onXXX.
    document.addEventListener('submit', function (e) {
        var form = e.target.closest('[data-confirm]');
        if (form && !window.confirm(form.getAttribute('data-confirm'))) {
            e.preventDefault();
        }
    });

    // Confirmación sensorial al marcar "Entregado" (mismo espíritu que el sonido
    // del kiosco de visitas): vibración corta si el teléfono la soporta, y un
    // tono generado con Web Audio API (sin depender de un archivo de audio).
    // Se activa solo en la carga que sigue a marcar entregado (marcada por el
    // servidor con data-entregado-ok), nunca en una carga normal de la lista.
    if (!document.querySelector('[data-entregado-ok]')) return;

    if (navigator.vibrate) {
        navigator.vibrate([80, 40, 80]);
    }

    try {
        var Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return;
        var ctx = new Ctx();

        var notas = [
            { freq: 880,  inicio: 0,    duracion: 0.14 },
            { freq: 1318, inicio: 0.12, duracion: 0.22 },
        ];

        notas.forEach(function (n) {
            var osc  = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = n.freq;

            var t0 = ctx.currentTime + n.inicio;
            var t1 = t0 + n.duracion;
            gain.gain.setValueAtTime(0, t0);
            gain.gain.linearRampToValueAtTime(0.35, t0 + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.001, t1);

            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(t0);
            osc.stop(t1 + 0.02);
        });
    } catch (e) {
        // Silencioso a propósito: el envío ya se marcó como entregado; el
        // sonido es solo un refuerzo, nunca debe romper la pantalla.
    }
})();
