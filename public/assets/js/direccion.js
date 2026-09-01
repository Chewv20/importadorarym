// Autocompletar de colonia/delegación-municipio/estado por código postal, y
// vista previa del mapa antes de guardar. Todo es progresivo: si el marcado
// no está en la página, no hace nada; si la API externa falla, la captura
// manual sigue funcionando igual.
(function () {
    'use strict';
    var base = (document.documentElement.getAttribute('data-base') || '/').replace(/\/$/, '');

    // Asignar .value por JS no dispara 'input': lo hacemos explícito para que
    // otros listeners (p. ej. el botón de "mostrar mapa") se enteren del cambio.
    function fijarValor(el, valor) {
        el.value = valor;
        el.dispatchEvent(new Event('input', { bubbles: true }));
    }

    // ---- Autocompletar por código postal (delegación/municipio, estado, colonias) --
    // Delegación/municipio y estado se bloquean (readonly) mientras reflejen el CP
    // vigente — así no se puede escribir encima un dato que no le corresponda; para
    // corregirlos hay que cambiar el CP, que es la fuente real del dato. La colonia
    // no se bloquea (puede escribirse para filtrar), pero se valida al perder el
    // foco: ver el picker de colonia más abajo.
    document.querySelectorAll('[data-cp-form]').forEach(function (form) {
        var cp = form.querySelector('[name="codigo_postal"]');
        var municipio = form.querySelector('[name="delegacion_municipio"]');
        var estado = form.querySelector('[name="estado_direccion"]');
        var coloniaPicker = form.querySelector('[data-colonia-picker]');
        var coloniaInput = coloniaPicker ? coloniaPicker.querySelector('input[name="colonia"]') : null;
        if (!cp) return;
        var timer = null;
        var ultimoConsultado = '';

        // El CP dejó de coincidir con lo ya resuelto: los datos derivados de un CP
        // anterior ya no son confiables — se limpian y se desbloquean, en vez de
        // dejar mezclada la dirección vieja con el CP nuevo.
        function limpiarDerivados() {
            if (municipio) { municipio.readOnly = false; if (municipio.value) fijarValor(municipio, ''); }
            if (estado) { estado.readOnly = false; if (estado.value) fijarValor(estado, ''); }
            if (coloniaPicker) coloniaPicker.__colonias = [];
            if (coloniaInput && coloniaInput.value) fijarValor(coloniaInput, '');
        }

        cp.addEventListener('input', function () {
            var v = cp.value.trim();
            if (v !== ultimoConsultado) {
                ultimoConsultado = '';
                limpiarDerivados();
            }
            clearTimeout(timer);
            if (!/^\d{5}$/.test(v)) return;
            timer = setTimeout(function () {
                fetch(base + '/codigo-postal/' + v + '/buscar', { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (data) {
                        // El CP pudo volver a cambiar mientras esta consulta viajaba.
                        if (cp.value.trim() !== v || !data || !data.found) return;
                        ultimoConsultado = v;
                        // "municipio" es la delegación/municipio real (ej. "Iztapalapa");
                        // "ciudad" en la API es una agrupación más amplia, no lo que va aquí.
                        if (municipio && data.municipio) { fijarValor(municipio, data.municipio); municipio.readOnly = true; }
                        if (estado && data.estado) { fijarValor(estado, data.estado); estado.readOnly = true; }
                        if (coloniaPicker && Array.isArray(data.colonias)) {
                            coloniaPicker.__colonias = data.colonias;
                        }
                    })
                    .catch(function () { /* la captura manual sigue disponible */ });
            }, 400);
        });
    });

    // ---- Selector de colonia (sugerencias con estilo propio, no <datalist>) -------
    document.querySelectorAll('[data-colonia-picker]').forEach(function (picker) {
        var input   = picker.querySelector('input[name="colonia"]');
        var results = picker.querySelector('[data-colonia-resultados]');
        if (!input || !results) return;
        picker.__colonias = picker.__colonias || [];

        function cerrar() { results.hidden = true; results.innerHTML = ''; }

        function mostrar() {
            var colonias = picker.__colonias || [];
            if (!colonias.length) { cerrar(); return; }
            var q = input.value.trim().toLowerCase();
            var filtradas = q === '' ? colonias : colonias.filter(function (c) {
                return c.toLowerCase().indexOf(q) !== -1;
            });
            results.innerHTML = '';
            if (!filtradas.length) { cerrar(); return; }
            filtradas.forEach(function (nombre) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'colonia-picker__item';
                b.textContent = nombre;
                b.addEventListener('click', function () {
                    fijarValor(input, nombre);
                    cerrar();
                });
                results.appendChild(b);
            });
            results.hidden = false;
        }

        input.addEventListener('input', mostrar);
        input.addEventListener('focus', mostrar);
        document.addEventListener('click', function (e) {
            if (!picker.contains(e.target)) cerrar();
        });

        // Si ya tenemos la lista de colonias válidas para el CP capturado, no se
        // acepta cualquier texto: al salir del campo, si lo escrito no es una de
        // esas colonias exactas, se borra — evita guardar una colonia inventada
        // que no corresponde al código postal.
        input.addEventListener('blur', function () {
            setTimeout(function () {
                var colonias = picker.__colonias || [];
                if (colonias.length && colonias.indexOf(input.value.trim()) === -1) {
                    fijarValor(input, '');
                }
            }, 150); // deja tiempo a que el clic en una opción se registre antes del blur
        });
    });

    // ---- Mostrar mapa: vista previa antes de guardar -------------------------------
    var ETIQUETAS = {
        calle: 'calle',
        numero_ext: 'número exterior',
        colonia: 'colonia',
        codigo_postal: 'código postal',
        delegacion_municipio: 'delegación o municipio',
        estado_direccion: 'estado'
    };

    document.querySelectorAll('[data-mapa-preview]').forEach(function (wrap) {
        var form  = wrap.closest('form');
        var btn   = wrap.querySelector('[data-mapa-btn]');
        var cont  = wrap.querySelector('[data-mapa-contenedor]');
        var error = wrap.querySelector('[data-mapa-error]');
        if (!form || !btn || !cont) return;

        var camposRequeridos = ['calle', 'numero_ext', 'colonia', 'codigo_postal', 'delegacion_municipio', 'estado_direccion'];

        function valor(nombre) {
            var el = form.querySelector('[name="' + nombre + '"]');
            return el ? el.value.trim() : '';
        }

        btn.addEventListener('click', function () {
            var faltantes = camposRequeridos.filter(function (n) { return valor(n) === ''; });

            if (faltantes.length) {
                cont.hidden = true;
                cont.innerHTML = '';
                if (error) {
                    var nombres = faltantes.map(function (n) { return ETIQUETAS[n] || n; });
                    error.textContent = 'Completa tu dirección (' + nombres.join(', ') + ') para poder ver el mapa.';
                    error.hidden = false;
                }
                return;
            }

            if (error) error.hidden = true;

            var partes = [(valor('calle') + ' ' + valor('numero_ext')).trim()];
            if (valor('numero_int'))            partes.push('Int. ' + valor('numero_int'));
            partes.push(valor('colonia'));
            partes.push('C.P. ' + valor('codigo_postal'));
            partes.push(valor('delegacion_municipio'));
            partes.push(valor('estado_direccion'));

            var url = 'https://www.google.com/maps?q=' + encodeURIComponent(partes.join(', ')) + '&z=16&output=embed';
            cont.innerHTML = '<div class="map-embed"><iframe src="' + url
                + '" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Vista previa de tu dirección"></iframe></div>';
            cont.hidden = false;
        });
    });
})();
