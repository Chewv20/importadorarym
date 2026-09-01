// Interacciones del panel de administración.
(function () {
    'use strict';

    // Menú lateral en móvil.
    var burger = document.getElementById('adminBurger');
    var side = document.getElementById('adminSide');
    if (burger && side) {
        burger.addEventListener('click', function () {
            side.classList.toggle('is-open');
        });
    }

    // Formulario de usuario interno: la clave de vendedor y la comisión
    // solo se habilitan para el rol Ventas.
    var rol = document.getElementById('rol_id');
    var wrap = document.getElementById('vendedorFields');
    if (rol && wrap) {
        var cv = document.getElementById('clave_vendedor');
        var com = document.getElementById('comision');
        var apply = function () {
            var opt = rol.options[rol.selectedIndex];
            var esVentas = opt && opt.getAttribute('data-slug') === 'ventas';
            if (cv) cv.disabled = !esVentas;
            if (com) com.disabled = !esVentas;
            wrap.classList.toggle('is-disabled', !esVentas);
            if (!esVentas) {
                if (cv) cv.value = '';
                if (com) com.value = '';
            }
        };
        rol.addEventListener('change', apply);
        apply();
    }

    // Autocompletar de productos (armar cotizaciones): solo pide coincidencias.
    var base = (document.documentElement.getAttribute('data-base') || '/').replace(/\/$/, '');
    document.querySelectorAll('[data-prodpicker]').forEach(function (form) {
        var input   = form.querySelector('.prodpicker__input');
        var hidden  = form.querySelector('input[name="producto_id"]');
        var results = form.querySelector('.prodpicker__results');
        if (!input || !hidden || !results) return;
        var timer = null;

        function cerrar() { results.hidden = true; results.innerHTML = ''; }

        input.addEventListener('input', function () {
            hidden.value = '';
            var q = input.value.trim();
            clearTimeout(timer);
            if (q.length < 2) { cerrar(); return; }
            timer = setTimeout(function () {
                fetch(base + '/admin/productos/buscar?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json' }
                })
                    .then(function (r) { return r.ok ? r.json() : []; })
                    .then(function (items) {
                        results.innerHTML = '';
                        if (!items.length) {
                            results.innerHTML = '<div class="prodpicker__empty">Sin resultados</div>';
                            results.hidden = false;
                            return;
                        }
                        items.forEach(function (it) {
                            var b = document.createElement('button');
                            b.type = 'button';
                            b.className = 'prodpicker__item';
                            var sku = it.sku ? ' · ' + it.sku : '';
                            var precio = it.precio != null ? ' · $' + Number(it.precio).toFixed(2) : '';
                            b.textContent = it.nombre + sku + precio;
                            b.addEventListener('click', function () {
                                hidden.value = it.id;
                                input.value = it.nombre;
                                cerrar();
                            });
                            results.appendChild(b);
                        });
                        results.hidden = false;
                    })
                    .catch(cerrar);
            }, 250);
        });

        document.addEventListener('click', function (e) { if (!form.contains(e.target)) cerrar(); });
        form.addEventListener('submit', function (e) {
            if (!hidden.value) { e.preventDefault(); input.focus(); }
        });
    });

    // Autocompletar de clientes (crear pedido a nombre de un cliente): mismo
    // patrón que el buscador de productos de arriba, distinto endpoint/campos.
    document.querySelectorAll('[data-clientpicker]').forEach(function (form) {
        var input   = form.querySelector('.prodpicker__input');
        var hidden  = form.querySelector('input[name="cliente_id"]');
        var results = form.querySelector('.prodpicker__results');
        if (!input || !hidden || !results) return;
        var timer = null;

        function cerrar() { results.hidden = true; results.innerHTML = ''; }

        input.addEventListener('input', function () {
            hidden.value = '';
            var q = input.value.trim();
            clearTimeout(timer);
            if (q.length < 2) { cerrar(); return; }
            timer = setTimeout(function () {
                fetch(base + '/admin/pedidos/nuevo/clientes/buscar?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json' }
                })
                    .then(function (r) { return r.ok ? r.json() : []; })
                    .then(function (items) {
                        results.innerHTML = '';
                        if (!items.length) {
                            results.innerHTML = '<div class="prodpicker__empty">Sin resultados</div>';
                            results.hidden = false;
                            return;
                        }
                        items.forEach(function (it) {
                            var b = document.createElement('button');
                            b.type = 'button';
                            b.className = 'prodpicker__item';
                            var empresa = it.empresa ? ' · ' + it.empresa : '';
                            var pendiente = it.aprobado ? '' : ' (pendiente de aprobación)';
                            b.textContent = it.nombre + empresa + pendiente;
                            b.addEventListener('click', function () {
                                hidden.value = it.id;
                                input.value = it.nombre;
                                cerrar();
                            });
                            results.appendChild(b);
                        });
                        results.hidden = false;
                    })
                    .catch(cerrar);
            }, 250);
        });

        document.addEventListener('click', function (e) { if (!form.contains(e.target)) cerrar(); });
        form.addEventListener('submit', function (e) {
            if (!hidden.value) { e.preventDefault(); input.focus(); }
        });
    });

    // Confirmación antes de enviar formularios destructivos (eliminar, revocar, etc.).
    // Delegado (no onsubmit inline) porque la CSP del sitio no permite atributos
    // de evento inline: script-src usa nonce y no cubre atributos onXXX.
    document.addEventListener('submit', function (e) {
        var form = e.target.closest('[data-confirm]');
        if (form && !window.confirm(form.getAttribute('data-confirm'))) {
            e.preventDefault();
        }
    });

    // Selección de partidas al exportar a SAE (7.5): al desmarcar una partida su
    // precio deja de ser obligatorio (si no, el navegador bloquea el envío del
    // formulario por el campo required vacío y da la impresión de que la casilla
    // no hace nada) y la fila se atenúa para que se note que quedó fuera.
    document.addEventListener('change', function (e) {
        var cb = e.target.closest('[data-sae-item]');
        if (cb) {
            var row = cb.closest('tr');
            var precio = row ? row.querySelector('input[name^="precio["]') : null;
            if (precio) {
                precio.required = cb.checked;
                precio.disabled = !cb.checked;
            }
            if (row) {
                row.classList.toggle('row-unselected', !cb.checked);
            }
            return;
        }
        var master = e.target.closest('[data-sae-check-all]');
        if (master) {
            var scope = master.closest('table') || document;
            scope.querySelectorAll('[data-sae-item]:not(:disabled)').forEach(function (item) {
                if (item.checked !== master.checked) {
                    item.checked = master.checked;
                    item.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }
    });

    // Fecha de entrega → serie de SAE (7.4): solo aviso visual, la validación
    // real (rechazar sábado/domingo) siempre ocurre en el servidor.
    var SERIE_POR_DIA = { 1: 'L (lunes)', 2: 'M (martes)', 3: 'X (miércoles)', 4: 'J (jueves)', 5: 'V (viernes)' };
    function pintarSerieDeFecha(input) {
        var out = document.querySelector(input.getAttribute('data-fecha-serie-out') || '');
        if (!out) return;
        var partes = (input.value || '').split('-');
        if (partes.length !== 3) { out.textContent = ''; return; }
        var dia = new Date(Number(partes[0]), Number(partes[1]) - 1, Number(partes[2])).getDay();
        var isoDay = dia === 0 ? 7 : dia;
        out.textContent = SERIE_POR_DIA[isoDay] || 'No se permite sábado ni domingo — elige otro día.';
    }
    document.addEventListener('input', function (e) {
        var input = e.target.closest('[data-fecha-serie]');
        if (input) pintarSerieDeFecha(input);
    });
    document.querySelectorAll('[data-fecha-serie]').forEach(pintarSerieDeFecha);

    // Pista de scroll horizontal en tablas (móvil): sombra a la derecha mientras
    // quede contenido por ver, para que no pase desapercibido que se puede deslizar.
    var wraps = document.querySelectorAll('.table-wrap');
    function actualizarSombra(el) {
        var falta = el.scrollWidth - el.clientWidth - el.scrollLeft > 4;
        el.classList.toggle('has-more-right', falta);
    }
    wraps.forEach(function (el) {
        actualizarSombra(el);
        el.addEventListener('scroll', function () { actualizarSombra(el); }, { passive: true });
    });
    if (wraps.length) {
        window.addEventListener('resize', function () { wraps.forEach(actualizarSombra); });
    }
})();
