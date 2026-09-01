// Google Analytics 4: se inicializa a partir del <meta name="ga-id">.
// Mantiene toda la lógica fuera del HTML/PHP.
(function () {
    'use strict';
    var meta = document.querySelector('meta[name="ga-id"]');
    var id = meta && meta.getAttribute('content');
    if (!id) return;

    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
    document.head.appendChild(s);

    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', id);
})();
