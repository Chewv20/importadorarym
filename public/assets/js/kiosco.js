/* ==========================================================================
   Kiosco de visitas — sonido de confirmación.
   Se activa SOLO en la pantalla de "registro recibido" (marcada con
   data-checador-ok en la vista), nunca en el formulario ni en "no autorizado".
   Generado con Web Audio API (dos tonos ascendentes tipo "acceso concedido"):
   así no depende de un archivo de audio ni de una conexión externa.
   ========================================================================== */
(function () {
    if (!document.querySelector('[data-checador-ok]')) return;

    try {
        var Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return;
        var ctx = new Ctx();

        // Dos notas ascendentes (do agudo -> mi agudo), con fade out para que
        // no truene: envolvente simple de volumen por nota.
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
        // Silencioso a propósito: si el navegador bloquea el audio (o no
        // soporta Web Audio API), el registro ya se guardó; el sonido es
        // solo un refuerzo, nunca debe romper la pantalla de confirmación.
    }
})();
