<footer class="site-footer">
    <div class="container footer__grid">
        <div>
            <div class="footer__title">Importadora RYM S.A. de C.V.</div>
            <p class="mb-4 measure-narrow">
                Líderes en fabricación, impresión y distribución de insumos para
                transportar bebidas y alimentos para el sector horeca. 35 años de experiencia.
            </p>
            <div class="footer__contact">
                <div>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span>Av. San Lorenzo N° 279, Nave 27, Col. San Nicolás Tolentino, Iztapalapa, CDMX, C.P. 09850</span>
                </div>
                <div>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <span><a href="tel:5556121612">(55) 5612 1612</a> · con 10 líneas</span>
                </div>
                <div>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/></svg>
                    <span><a href="mailto:cotizaciones@importadorarym.com">cotizaciones@importadorarym.com</a></span>
                </div>
            </div>
        </div>

        <div>
            <div class="footer__title">Mapa de sitio</div>
            <ul class="footer__list">
                <li><a href="<?= url('/') ?>">Inicio</a></li>
                <li><a href="<?= url('/nosotros') ?>">Nosotros</a></li>
                <li><a href="<?= url('/productos') ?>">Productos</a></li>
                <li><a href="<?= url('/personalizacion') ?>">Personalización</a></li>
                <li><a href="<?= url('/reciclaje') ?>">Reciclaje</a></li>
                <li><a href="<?= url('/contacto') ?>">Contacto</a></li>
                <li><a href="<?= url('/bolsa-de-trabajo') ?>">Bolsa de trabajo</a></li>
                <li><a href="<?= url('/aviso-de-privacidad') ?>">Aviso de privacidad</a></li>
            </ul>
        </div>

        <div>
            <div class="footer__title">Estamos conectados</div>
            <ul class="footer__list">
                <li><a href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener">WhatsApp: 55 5297 9776</a></li>
                <li><a href="https://www.google.com.mx/maps/place/Importadora+Rym+S.A.+De+C.V./@19.3293018,-99.0802377,17z" target="_blank" rel="noopener">Ver en Google Maps</a></li>
                <li><a href="<?= url('/portal/login') ?>">Portal de clientes</a></li>
            </ul>
        </div>
    </div>
    <div class="footer__bottom container">
        &copy; <?= date('Y') ?> Importadora RYM S.A. de C.V. Todos los derechos reservados.
    </div>
</footer>
