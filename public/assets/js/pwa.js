// Registro del Service Worker y control del botón "Instalar app".
// El base path llega desde el servidor en window.RYM_BASE (inyectado en el layout).
(function () {
    'use strict';

    var base = document.documentElement.getAttribute('data-base') || '/';

    // --- Registro del Service Worker ---
    // Requiere contexto seguro: HTTPS o localhost. En producción sin SSL el
    // navegador lo ignora silenciosamente; se activará al instalar el certificado.
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(base + 'sw.js', { scope: base })
                .catch(function (err) {
                    console.warn('Service Worker no registrado:', err && err.message);
                });
        });

        // Recarga automática cuando hay una versión nueva del sitio.
        // El propio sw.js llama a skipWaiting()+clients.claim(), así que en cuanto
        // detecta un cambio de VERSION el worker nuevo toma control de inmediato —
        // pero la pestaña ya abierta sigue mostrando el HTML/CSS/JS con los que
        // cargó hasta que se recarga. Sin esto, un cliente que deja la pestaña
        // abierta (o no hace un refresh manual) puede quedarse viendo contenido
        // viejo indefinidamente tras un despliegue.
        // "yaControlado" evita recargar en el primer registro de un visitante nuevo
        // (ahí controllerchange también se dispara, por el mismo clients.claim(),
        // pero no es una actualización real — no hay nada que refrescar).
        var refrescando = false;
        var yaControlado = !!navigator.serviceWorker.controller;
        navigator.serviceWorker.addEventListener('controllerchange', function () {
            if (!yaControlado) {
                yaControlado = true;
                return;
            }
            if (refrescando) return;
            refrescando = true;
            window.location.reload();
        });
    }

    // --- Botón de instalación (aparece cuando el navegador lo permite) ---
    var deferredPrompt = null;
    var installBtn = document.getElementById('installBtn');

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
        if (installBtn) installBtn.classList.remove('is-hidden');
    });

    if (installBtn) {
        installBtn.addEventListener('click', function () {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            deferredPrompt.userChoice.finally(function () {
                deferredPrompt = null;
                installBtn.classList.add('is-hidden');
            });
        });
    }

    window.addEventListener('appinstalled', function () {
        if (installBtn) installBtn.classList.add('is-hidden');
        deferredPrompt = null;
    });
})();
