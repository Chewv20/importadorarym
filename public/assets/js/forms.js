// Comportamiento común de formularios (sitio, portal, panel, kiosco y reparto).
(function () {
    'use strict';

    /* ---- Llevar al usuario al aviso de error tras una validación -------- */
    // Los errores del servidor llegan tras una redirección; sin esto la página
    // carga arriba y el aviso puede quedar fuera de vista (p. ej. el formulario
    // de cotización al pie de la home).
    var aviso = document.querySelector('.alert--error:not([hidden]), .kiosco-alert');
    if (aviso && aviso.textContent.trim() !== '') {
        aviso.setAttribute('role', 'alert');
        aviso.setAttribute('tabindex', '-1');
        // Tras el "load": si la URL trae ancla (/#cotiza, /contacto#form), el salto
        // del navegador a esa ancla ocurre al cargar y le quitaba el foco al aviso.
        var enfocar = function () {
            setTimeout(function () {
                aviso.focus({ preventScroll: true });
                // instant: con scroll-behavior:smooth el salto animado al ancla seguía en curso y lo pisaba.
                aviso.scrollIntoView({ block: 'center', behavior: 'instant' });
            }, 0);
        };
        if (document.readyState === 'complete') { enfocar(); } else { window.addEventListener('load', enfocar); }
    }

    /* ---- Selector de archivos en español --------------------------------- */
    // El control nativo muestra "Choose File / No file chosen" según el idioma del
    // NAVEGADOR, no del sitio, y no se puede traducir. Se pone un botón y un texto
    // propios; el input real queda oculto pero sigue haciendo todo (envío,
    // validación required, <label for>, change del simulador de logo). Sin JS se
    // ve el control nativo con sus estilos de siempre.
    document.querySelectorAll('input[type="file"]').forEach(function (input, n) {
        if (input.closest('.file-ui')) { return; }
        var ui = document.createElement('span');
        ui.className = 'file-ui';
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn--outline btn--sm';
        btn.textContent = input.multiple ? 'Elegir archivos' : 'Elegir archivo';
        var nombre = document.createElement('span');
        nombre.className = 'file-ui__nombre';
        nombre.id = 'file-ui-' + n;
        var vacio = 'Ningún archivo seleccionado';
        nombre.textContent = vacio;

        // Nombre accesible del botón = el de su <label> (o aria-label) del input.
        var etiqueta = input.getAttribute('aria-label')
            || (input.id && document.querySelector('label[for="' + input.id + '"]') || {}).textContent || '';
        if (etiqueta.trim()) { btn.setAttribute('aria-label', etiqueta.trim() + ': elegir archivo'); }
        btn.setAttribute('aria-describedby', nombre.id);

        input.parentNode.insertBefore(ui, input);
        ui.appendChild(input);
        ui.appendChild(btn);
        ui.appendChild(nombre);
        input.classList.add('file-ui__nativo');
        input.setAttribute('tabindex', '-1');

        btn.addEventListener('click', function () { input.click(); });
        // En el siguiente tick: otros scripts (p. ej. el simulador de logo) pueden
        // vaciar el input en su propio "change" si el archivo no es válido.
        input.addEventListener('change', function () {
            setTimeout(function () {
                var f = input.files || [];
                nombre.textContent = f.length === 0 ? vacio
                    : (f.length === 1 ? f[0].name : f.length + ' archivos seleccionados');
            }, 0);
        });
        // Si el formulario se limpia (reset) o el script del simulador vacía el input.
        var form = input.form;
        if (form) { form.addEventListener('reset', function () { setTimeout(function () { nombre.textContent = vacio; }, 0); }); }
    });

    /* ---- Evitar el doble envío ------------------------------------------ */
    // Un doble toque en "Enviar" creaba registros y correos duplicados. No se usa
    // el atributo disabled en el botón: un botón deshabilitado en el momento del
    // envío ya no manda su name/value, y algunos formularios dependen de eso.
    var LIBERAR_MS = 8000; // descargas (Excel, CV) no navegan: libera el formulario

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post') { return; }

        if (form.hasAttribute('data-enviando')) {
            e.preventDefault();
            return;
        }
        // Se marca de inmediato (un doble clic rápido llega antes de cualquier
        // setTimeout) y se revierte en el siguiente tick si otro listener canceló
        // el envío (confirmaciones con data-confirm).
        form.setAttribute('data-enviando', '');
        var btn = e.submitter || form.querySelector('[type="submit"]');
        setTimeout(function () {
            if (e.defaultPrevented) { form.removeAttribute('data-enviando'); return; }
            if (btn) { btn.classList.add('is-sending'); btn.setAttribute('aria-disabled', 'true'); }
            setTimeout(function () { liberar(form); }, LIBERAR_MS);
        }, 0);
    });

    function liberar(form) {
        form.removeAttribute('data-enviando');
        form.querySelectorAll('.is-sending').forEach(function (b) {
            b.classList.remove('is-sending');
            b.removeAttribute('aria-disabled');
        });
    }

    // Al volver con el botón "atrás" (bfcache) el formulario debe estar usable.
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) { document.querySelectorAll('form[data-enviando]').forEach(liberar); }
    });
})();
