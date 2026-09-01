// Interacciones del sitio: menú móvil, aparición al scroll y conteo de métricas.
(function () {
    'use strict';

    /* ---- Menú móvil ------------------------------------------------------ */
    var toggle = document.getElementById('navToggle');
    var nav = document.getElementById('nav');
    var backdrop = document.getElementById('navBackdrop');

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

    /* ---- Revela el campo de logo si se marca "requiere impresión" ------- */
    document.querySelectorAll('[data-imprime-check]').forEach(function (box) {
        var campo = box.closest('form').querySelector('[data-imprime-logo]');
        if (!campo) { return; }
        var apply = function () { campo.hidden = !box.checked; };
        box.addEventListener('change', apply);
        apply();
    });

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---- Conteo de métricas --------------------------------------------- */
    function animateCount(el) {
        var target = parseFloat(el.getAttribute('data-count')) || 0;
        var suffix = el.getAttribute('data-suffix') || '';
        if (reduceMotion) {
            el.textContent = target.toLocaleString('es-MX') + suffix;
            return;
        }
        var duration = 1400, start = null;
        function step(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / duration, 1);
            var eased = 1 - Math.pow(1 - p, 3); // easeOutCubic
            el.textContent = Math.round(target * eased).toLocaleString('es-MX') + suffix;
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    /* ---- Aparición al hacer scroll -------------------------------------- */
    var revealEls = document.querySelectorAll('.reveal');
    var counters = document.querySelectorAll('[data-count]');

    if (!('IntersectionObserver' in window) || reduceMotion) {
        revealEls.forEach(function (el) { el.classList.add('is-visible'); });
        counters.forEach(animateCount);
        return;
    }

    var revealObserver = new IntersectionObserver(function (entries, obs) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                obs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    revealEls.forEach(function (el) { revealObserver.observe(el); });

    var countObserver = new IntersectionObserver(function (entries, obs) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                animateCount(entry.target);
                obs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.6 });
    counters.forEach(function (el) { countObserver.observe(el); });

    /* --- Pasarela de imágenes de producto (avanza al pasar el cursor) --- */
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.querySelectorAll('[data-gallery]').forEach(function (g) {
        var imgs = g.querySelectorAll('.prod-gallery__img');
        var dots = g.querySelectorAll('.prod-gallery__dot');
        if (imgs.length < 2) { return; }
        var i = 0, timer = null;

        function mostrar(n) {
            n = (n + imgs.length) % imgs.length;
            imgs[i].classList.remove('is-active');
            if (dots[i]) { dots[i].classList.remove('is-active'); }
            i = n;
            imgs[i].classList.add('is-active');
            if (dots[i]) { dots[i].classList.add('is-active'); }
        }
        function iniciar() {
            if (reduceMotion) { return; }
            detener();
            timer = setInterval(function () { mostrar(i + 1); }, 1000);
        }
        function detener() {
            if (timer) { clearInterval(timer); timer = null; }
        }

        g.addEventListener('mouseenter', iniciar);
        g.addEventListener('mouseleave', function () { detener(); mostrar(0); });
        dots.forEach(function (d, n) {
            d.addEventListener('mouseenter', function () { detener(); mostrar(n); });
        });
    });

    /* --- Modal promocional del sitio (una vez por versión, recordado) --- */
    var promo = document.querySelector('[data-promo]');
    if (promo) {
        var pid = promo.getAttribute('data-promo-id');
        var pv  = promo.getAttribute('data-promo-v');
        var key = 'rym_promo_' + pid;
        var seen = null;
        try { seen = localStorage.getItem(key); } catch (e) {}

        var abrir = function () { promo.hidden = false; document.body.classList.add('promo-open'); };
        var cerrar = function () {
            promo.hidden = true;
            document.body.classList.remove('promo-open');
            try { localStorage.setItem(key, pv); } catch (e) {}
        };

        if (seen !== pv) {
            setTimeout(abrir, 700);
        }
        promo.querySelectorAll('[data-promo-close]').forEach(function (el) {
            el.addEventListener('click', cerrar);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !promo.hidden) { cerrar(); }
        });
    }

    /* --- Visor ampliado de imágenes de producto (catálogo) --- */
    var lightbox = document.getElementById('lightbox');
    if (lightbox) {
        var lbImg = document.getElementById('lightboxImg');
        var lbDots = document.getElementById('lightboxDots');
        var lbPrev = lightbox.querySelector('[data-lightbox-prev]');
        var lbNext = lightbox.querySelector('[data-lightbox-next]');
        var lbImgs = [];
        var lbIndex = 0;
        var lbTrigger = null;

        var lbMostrar = function (n) {
            lbIndex = (n + lbImgs.length) % lbImgs.length;
            lbImg.src = lbImgs[lbIndex].src;
            lbImg.alt = lbImgs[lbIndex].alt;
            lbDots.querySelectorAll('.lightbox__dot').forEach(function (d, i) {
                d.classList.toggle('is-active', i === lbIndex);
            });
        };

        var lbAbrir = function (media, indiceInicial) {
            lbImgs = Array.prototype.slice.call(media.querySelectorAll('.prod-gallery__img'));
            if (!lbImgs.length) { return; }
            lbTrigger = media;
            lbDots.innerHTML = '';
            var multiple = lbImgs.length > 1;
            lbPrev.hidden = !multiple;
            lbNext.hidden = !multiple;
            if (multiple) {
                lbImgs.forEach(function (_, i) {
                    var d = document.createElement('button');
                    d.type = 'button';
                    d.className = 'lightbox__dot';
                    d.setAttribute('aria-label', 'Imagen ' + (i + 1));
                    d.addEventListener('click', function () { lbMostrar(i); });
                    lbDots.appendChild(d);
                });
            }
            lbMostrar(indiceInicial || 0);
            lightbox.hidden = false;
            document.body.classList.add('lightbox-open');
        };

        var lbCerrar = function () {
            lightbox.hidden = true;
            document.body.classList.remove('lightbox-open');
            lbImg.src = '';
            if (lbTrigger) { lbTrigger.focus(); lbTrigger = null; }
        };

        document.querySelectorAll('[data-lightbox]').forEach(function (media) {
            media.addEventListener('click', function () {
                var todas = media.querySelectorAll('.prod-gallery__img');
                var activa = media.querySelector('.prod-gallery__img.is-active');
                var indice = activa ? Array.prototype.indexOf.call(todas, activa) : 0;
                lbAbrir(media, indice);
            });
            media.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); media.click(); }
            });
        });

        lightbox.querySelectorAll('[data-lightbox-close]').forEach(function (el) {
            el.addEventListener('click', lbCerrar);
        });
        lbPrev.addEventListener('click', function () { lbMostrar(lbIndex - 1); });
        lbNext.addEventListener('click', function () { lbMostrar(lbIndex + 1); });
        document.addEventListener('keydown', function (e) {
            if (lightbox.hidden) { return; }
            if (e.key === 'Escape') { lbCerrar(); }
            else if (e.key === 'ArrowLeft') { lbMostrar(lbIndex - 1); }
            else if (e.key === 'ArrowRight') { lbMostrar(lbIndex + 1); }
        });
    }
})();
