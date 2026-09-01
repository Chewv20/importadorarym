<?php
require APP_PATH . '/Views/partials/page_hero.php';
$okMsg  = flash('cotizacion_ok');
$errMsg = flash('cotizacion_error');
$old    = $_SESSION['_old'] ?? [];
unset($_SESSION['_old']);
$prodInteres = $old['producto_interes'] ?? trim($_GET['producto'] ?? '');
?>

<section class="section">
    <div class="container contact-grid">

        <!-- Formulario -->
        <form class="form" id="form" method="post" action="<?= url('/cotizar') ?>" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <?= honeypot_field() ?>
            <input type="hidden" name="origen" value="contacto">

            <h2 class="section__title text-left">Solicita tu cotización</h2>

            <?php if ($okMsg): ?>
                <div class="alert alert--ok"><?= e($okMsg) ?></div>
            <?php endif; ?>
            <?php if ($errMsg): ?>
                <div class="alert alert--error"><?= e($errMsg) ?></div>
            <?php endif; ?>

            <div class="form__row">
                <div class="field">
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" value="<?= e($old['nombre'] ?? '') ?>" required>
                </div>
                <div class="field">
                    <label for="empresa">Empresa</label>
                    <input type="text" id="empresa" name="empresa" value="<?= e($old['empresa'] ?? '') ?>">
                </div>
            </div>
            <div class="form__row">
                <div class="field">
                    <label for="email">Correo *</label>
                    <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" required>
                </div>
                <div class="field">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" value="<?= e($old['telefono'] ?? '') ?>">
                </div>
            </div>
            <div class="field">
                <label for="producto_interes">Producto de interés</label>
                <input type="text" id="producto_interes" name="producto_interes" value="<?= e($prodInteres) ?>">
            </div>
            <div class="field">
                <label for="mensaje">Mensaje</label>
                <textarea id="mensaje" name="mensaje" placeholder="Productos, cantidades, si requieres impresión..."><?= e($old['mensaje'] ?? '') ?></textarea>
            </div>
            <div class="field">
                <label class="check-inline">
                    <input type="checkbox" name="requiere_impresion" value="1" data-imprime-check <?= !empty($old['requiere_impresion']) ? 'checked' : '' ?>>
                    ¿Requiere impresión de tu logo?
                </label>
            </div>
            <div class="field field--reveal" data-imprime-logo hidden>
                <label for="logo">Tu logo (opcional)</label>
                <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
                <p class="field__note">PNG, JPG o WEBP · máx. 3 MB. Es solo para tu cotización, no se publica en el sitio.</p>
            </div>
            <?= captcha_field() ?>
            <button type="submit" class="btn btn--accent btn--lg btn--block">Enviar solicitud</button>
        </form>

        <!-- Información + mapa -->
        <div>
            <div class="info-list mb-8">
                <div class="info-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <div>
                        <div class="info-item__label">Dirección</div>
                        <div class="info-item__value">Av. San Lorenzo N° 279, Nave 27, Col. San Nicolás Tolentino, Iztapalapa, CDMX, C.P. 09850</div>
                    </div>
                </div>
                <div class="info-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <div>
                        <div class="info-item__label">Teléfono</div>
                        <div class="info-item__value"><a href="tel:5556121612">(55) 5612 1612</a> · con 10 líneas</div>
                    </div>
                </div>
                <div class="info-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    <div>
                        <div class="info-item__label">WhatsApp</div>
                        <div class="info-item__value"><a href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener">55 5297 9776</a></div>
                    </div>
                </div>
                <div class="info-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/></svg>
                    <div>
                        <div class="info-item__label">Correo</div>
                        <div class="info-item__value"><a href="mailto:cotizaciones@importadorarym.com">cotizaciones@importadorarym.com</a></div>
                    </div>
                </div>
                <div class="info-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    <div>
                        <div class="info-item__label">Horario</div>
                        <div class="info-item__value">Lunes a viernes, 9:00 – 18:00</div>
                    </div>
                </div>
            </div>

            <div class="map-embed">
                <iframe
                    src="https://www.google.com/maps?q=19.3293018,-99.0802377&z=16&output=embed"
                    loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    title="Ubicación de Importadora RYM en Google Maps"></iframe>
            </div>
        </div>

    </div>
</section>
