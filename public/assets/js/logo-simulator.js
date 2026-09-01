// Simulador de logo sobre producto (vaso / servilleta / bolsa). Todo ocurre en el
// navegador: el archivo del visitante nunca se envía al servidor. El canvas dibuja
// el producto + el logo + una marca de agua con el nombre de la empresa.
(function () {
    'use strict';

    var roots = document.querySelectorAll('[data-logo-sim]');
    if (!roots.length || !window.HTMLCanvasElement) return;

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var PRODUCTS = {
        vaso: { draw: drawVaso, area: { x: 0.31, y: 0.20, w: 0.38, h: 0.34 } },
        servilleta: { draw: drawServilleta, area: { x: 0.24, y: 0.24, w: 0.52, h: 0.52 } },
        bolsa: { draw: drawBolsa, area: { x: 0.30, y: 0.34, w: 0.40, h: 0.36 } }
    };

    function clamp(v, min, max) { return Math.min(max, Math.max(min, v)); }

    function roundRect(ctx, x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.arcTo(x + w, y, x + w, y + h, r);
        ctx.arcTo(x + w, y + h, x, y + h, r);
        ctx.arcTo(x, y + h, x, y, r);
        ctx.arcTo(x, y, x + w, y, r);
        ctx.closePath();
    }

    function drawVaso(ctx, w, h) {
        var topW = w * 0.42, botW = w * 0.28, top = h * 0.12, cupH = h * 0.66;
        var cx = w / 2;
        ctx.save();
        ctx.beginPath();
        ctx.moveTo(cx - topW / 2, top);
        ctx.lineTo(cx + topW / 2, top);
        ctx.lineTo(cx + botW / 2, top + cupH);
        ctx.lineTo(cx - botW / 2, top + cupH);
        ctx.closePath();
        var grad = ctx.createLinearGradient(0, top, 0, top + cupH);
        grad.addColorStop(0, '#ffffff');
        grad.addColorStop(1, '#eef0f2');
        ctx.fillStyle = grad;
        ctx.fill();
        ctx.lineWidth = 1.5;
        ctx.strokeStyle = '#d5d8dd';
        ctx.stroke();
        ctx.beginPath();
        ctx.ellipse(cx, top, topW / 2, 7, 0, 0, Math.PI * 2);
        ctx.fillStyle = '#ffffff';
        ctx.fill();
        ctx.strokeStyle = '#d5d8dd';
        ctx.stroke();
        ctx.restore();
    }

    function drawServilleta(ctx, w, h) {
        var sw = w * 0.6, sh = h * 0.6, x = (w - sw) / 2, y = (h - sh) / 2;
        ctx.save();
        ctx.fillStyle = '#fdfdfc';
        ctx.strokeStyle = '#e3e3e0';
        ctx.lineWidth = 1.5;
        roundRect(ctx, x, y, sw, sh, 4);
        ctx.fill();
        ctx.stroke();
        ctx.strokeStyle = 'rgba(0,0,0,.07)';
        ctx.beginPath(); ctx.moveTo(x, y + sh / 2); ctx.lineTo(x + sw, y + sh / 2); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(x + sw / 2, y); ctx.lineTo(x + sw / 2, y + sh); ctx.stroke();
        ctx.restore();
    }

    function drawBolsa(ctx, w, h) {
        var bw = w * 0.46, bh = h * 0.5, x = (w - bw) / 2, y = h * 0.30;
        ctx.save();
        var grad = ctx.createLinearGradient(x, 0, x + bw, 0);
        grad.addColorStop(0, '#cda875');
        grad.addColorStop(1, '#e8c99a');
        ctx.fillStyle = grad;
        ctx.strokeStyle = '#b6905c';
        ctx.lineWidth = 1.5;
        roundRect(ctx, x, y, bw, bh, 6);
        ctx.fill();
        ctx.stroke();
        ctx.beginPath();
        ctx.ellipse(x + bw * 0.26, y, bw * 0.07, 14, 0, Math.PI, Math.PI * 2);
        ctx.stroke();
        ctx.beginPath();
        ctx.ellipse(x + bw * 0.74, y, bw * 0.07, 14, 0, Math.PI, Math.PI * 2);
        ctx.stroke();
        ctx.restore();
    }

    function initSim(root) {
        var canvas = root.querySelector('.logo-sim__canvas');
        var ctx = canvas.getContext('2d');
        var fileInput = root.querySelector('[data-logo-sim-file]');
        var scaleInput = root.querySelector('[data-logo-sim-scale]');
        var resetBtn = root.querySelector('[data-logo-sim-reset]');
        var tabs = root.querySelectorAll('[data-logo-sim-tab]');
        var emptyState = root.querySelector('[data-logo-sim-empty]');
        var errorBox = root.querySelector('[data-logo-sim-error]');

        var cssW = 0, cssH = 0;
        var state = {
            product: 'vaso',
            logoImg: null,
            logoNatural: { w: 0, h: 0 },
            pos: { x: 0.5, y: 0.5 },
            scale: 1,
            dragging: false,
            dragStart: null
        };

        function sizeCanvas() {
            cssW = canvas.clientWidth || 560;
            cssH = Math.round(cssW * (3 / 4));
            var dpr = window.devicePixelRatio || 1;
            canvas.width = Math.round(cssW * dpr);
            canvas.height = Math.round(cssH * dpr);
            canvas.style.height = cssH + 'px';
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        }

        function printAreaPx() {
            var a = PRODUCTS[state.product].area;
            return { x: a.x * cssW, y: a.y * cssH, w: a.w * cssW, h: a.h * cssH };
        }

        function drawLogo() {
            var area = printAreaPx();
            var ratio = Math.min(area.w / state.logoNatural.w, area.h / state.logoNatural.h);
            var logoW = state.logoNatural.w * ratio * state.scale;
            var logoH = state.logoNatural.h * ratio * state.scale;
            var cx = area.x + area.w * state.pos.x;
            var cy = area.y + area.h * state.pos.y;
            ctx.save();
            ctx.beginPath();
            ctx.rect(area.x, area.y, area.w, area.h);
            ctx.clip();
            ctx.drawImage(state.logoImg, cx - logoW / 2, cy - logoH / 2, logoW, logoH);
            ctx.restore();
        }

        function drawWatermark() {
            ctx.save();
            ctx.globalAlpha = .12;
            ctx.fillStyle = '#10069F';
            ctx.font = '700 15px Poppins, system-ui, sans-serif';
            ctx.textBaseline = 'middle';
            ctx.translate(cssW / 2, cssH / 2);
            ctx.rotate(-Math.PI / 9);
            var text = 'IMPORTADORA RYM';
            var stepX = 190, stepY = 56;
            for (var y = -cssH; y < cssH; y += stepY) {
                for (var x = -cssW; x < cssW; x += stepX) {
                    ctx.fillText(text, x, y);
                }
            }
            ctx.restore();
        }

        function render() {
            ctx.clearRect(0, 0, cssW, cssH);
            var grad = ctx.createLinearGradient(0, 0, cssW, cssH);
            grad.addColorStop(0, '#eef2ff');
            grad.addColorStop(1, '#e2e9ff');
            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, cssW, cssH);

            PRODUCTS[state.product].draw(ctx, cssW, cssH);
            if (state.logoImg) { drawLogo(); }
            drawWatermark();
        }

        function resetTransform() {
            state.pos = { x: .5, y: .5 };
            state.scale = 1;
            if (scaleInput) scaleInput.value = 100;
        }

        function showError(msg) {
            if (!errorBox) return;
            errorBox.textContent = msg;
            errorBox.hidden = !msg;
        }

        // ---- Carga del logo (nunca se envía al servidor) ----
        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0];
            if (!file) return;
            showError('');
            var okTypes = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'];
            if (okTypes.indexOf(file.type) === -1) {
                showError('Formato no válido. Usa PNG, JPG, WEBP o SVG.');
                fileInput.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showError('El archivo pesa más de 5 MB.');
                fileInput.value = '';
                return;
            }
            createImageBitmap(file).then(function (bitmap) {
                state.logoImg = bitmap;
                state.logoNatural = { w: bitmap.width, h: bitmap.height };
                resetTransform();
                if (emptyState) emptyState.hidden = true;
                canvas.setAttribute('tabindex', '0');
                render();
            }).catch(function () {
                showError('No se pudo leer esa imagen. Intenta con otro archivo.');
            });
        });

        // ---- Arrastrar el logo (mouse y táctil vía Pointer Events) ----
        canvas.addEventListener('pointerdown', function (e) {
            if (!state.logoImg) return;
            state.dragging = true;
            canvas.setPointerCapture(e.pointerId);
            state.dragStart = { x: e.offsetX, y: e.offsetY, pos: { x: state.pos.x, y: state.pos.y } };
        });
        canvas.addEventListener('pointermove', function (e) {
            if (!state.dragging) return;
            var area = printAreaPx();
            var dx = (e.offsetX - state.dragStart.x) / area.w;
            var dy = (e.offsetY - state.dragStart.y) / area.h;
            state.pos.x = clamp(state.dragStart.pos.x + dx, 0, 1);
            state.pos.y = clamp(state.dragStart.pos.y + dy, 0, 1);
            render();
        });
        function stopDrag() { state.dragging = false; }
        canvas.addEventListener('pointerup', stopDrag);
        canvas.addEventListener('pointercancel', stopDrag);
        canvas.addEventListener('pointerleave', stopDrag);

        // ---- Mover con teclado (equivalente accesible al arrastre) ----
        canvas.addEventListener('keydown', function (e) {
            if (!state.logoImg) return;
            var step = .02, moved = true;
            if (e.key === 'ArrowLeft') state.pos.x = clamp(state.pos.x - step, 0, 1);
            else if (e.key === 'ArrowRight') state.pos.x = clamp(state.pos.x + step, 0, 1);
            else if (e.key === 'ArrowUp') state.pos.y = clamp(state.pos.y - step, 0, 1);
            else if (e.key === 'ArrowDown') state.pos.y = clamp(state.pos.y + step, 0, 1);
            else moved = false;
            if (moved) { e.preventDefault(); render(); }
        });

        // ---- Tabs de producto ----
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (t) { t.classList.remove('is-active'); t.setAttribute('aria-selected', 'false'); });
                tab.classList.add('is-active');
                tab.setAttribute('aria-selected', 'true');
                state.product = tab.getAttribute('data-logo-sim-tab');
                resetTransform();
                render();
            });
        });

        if (scaleInput) {
            scaleInput.addEventListener('input', function () {
                state.scale = Number(scaleInput.value) / 100;
                render();
            });
        }
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                resetTransform();
                render();
            });
        }

        sizeCanvas();
        render();
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(render).catch(function () {});
        }
        if (!reduceMotion) {
            window.addEventListener('resize', debounce(function () { sizeCanvas(); render(); }, 200));
        }
    }

    function debounce(fn, ms) {
        var t = null;
        return function () {
            clearTimeout(t);
            var args = arguments;
            t = setTimeout(function () { fn.apply(null, args); }, ms);
        };
    }

    roots.forEach(initSim);
})();
