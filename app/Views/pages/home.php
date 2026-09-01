<?php
/** @var array $categorias */
/** @var array $destacados */
$categorias = $categorias ?? [];
$destacados = $destacados ?? [];
$okMsg  = flash('cotizacion_ok');
$errMsg = flash('cotizacion_error');
$old    = $_SESSION['_old'] ?? [];
unset($_SESSION['_old']);
?>

<!-- HERO -->
<section class="hero">
    <div class="container hero__inner">
        <div>
            <span class="hero__eyebrow">Insumos y empaques para empresas</span>
            <h1 class="hero__title">Todo para tu empresa,<br><span class="accent">en un solo lugar.</span></h1>
            <p class="hero__text">
                Fabricamos, imprimimos y distribuimos envases, empaques e insumos para la
                industria alimentaria. Calidad, precio accesible y entrega a la puerta de tu negocio.
            </p>
            <div class="hero__actions">
                <a class="btn btn--accent btn--lg" href="#cotiza">Cotiza ahora</a>
                <a class="btn btn--ghost btn--lg" href="<?= url('/productos') ?>">Ver productos</a>
            </div>
        </div>
        <aside class="hero__card">
            <h2>¿Por qué Importadora RYM?</h2>
            <ul class="hero__list">
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg> 35 años de experiencia en el ramo</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg> Más de 2,000 clientes satisfechos</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg> Impresión personalizada desde 1,000 pzas</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg> Entrega en la megalópolis y toda la República</li>
            </ul>
        </aside>
    </div>
</section>

<!-- MÉTRICAS -->
<section class="stats">
    <div class="container stats__grid">
        <div class="reveal"><div class="stat__num" data-count="35">0</div><div class="stat__label">Años de experiencia</div></div>
        <div class="reveal"><div class="stat__num" data-count="2000" data-suffix="+">0</div><div class="stat__label">Clientes satisfechos</div></div>
        <div class="reveal"><div class="stat__num" data-count="1000">0</div><div class="stat__label">Piezas mínimo para imprimir</div></div>
        <div class="reveal"><div class="stat__num" data-count="11">0</div><div class="stat__label">Marcas líderes que distribuimos</div></div>
    </div>
</section>

<!-- SERVICIOS -->
<section class="section" id="servicios">
    <div class="container">
        <div class="section__head reveal">
            <span class="section__eyebrow">Lo que hacemos</span>
            <h2 class="section__title">Una solución integral para tu negocio</h2>
            <p class="section__subtitle">Fabricación, personalización y distribución bajo un mismo techo.</p>
        </div>
        <div class="grid grid--4">
            <article class="card reveal">
                <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 20h20M4 20V8l6-4 6 4v12M9 20v-6h2v6"/></svg></div>
                <h3 class="card__title">Fabricación</h3>
                <p class="card__text">Manufacturamos envases e insumos con materiales resistentes y grado alimenticio.</p>
            </article>
            <article class="card reveal">
                <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z"/></svg></div>
                <h3 class="card__title">Impresión personalizada</h3>
                <p class="card__text">Imprime tu logo y marca en vasos y empaques. Tintas libres de plomo, grado alimenticio.</p>
            </article>
            <article class="card reveal">
                <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><path d="M16 8h4l3 3v5h-7V8zM5.5 21a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5zM18.5 21a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/></svg></div>
                <h3 class="card__title">Distribución</h3>
                <p class="card__text">Entregamos a la puerta de tu negocio en la megalópolis y por envío a todo México.</p>
            </article>
            <article class="card reveal">
                <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10M12 2v10l7-7"/><path d="M8 13a4 4 0 0 0 8 0"/></svg></div>
                <h3 class="card__title">Ecológico</h3>
                <p class="card__text">Opciones amigables con el medio ambiente para empresas responsables.</p>
            </article>
        </div>
    </div>
</section>

<!-- CATÁLOGO DESTACADO -->
<section class="section section--soft" id="catalogo">
    <div class="container">
        <div class="section__head reveal">
            <span class="section__eyebrow">Catálogo</span>
            <h2 class="section__title">Productos para la industria alimentaria</h2>
            <p class="section__subtitle">Vasos, contenedores, servilletas, cubiertos y más.</p>
        </div>

        <?php if ($categorias): ?>
        <div class="grid grid--4 mb-12">
            <?php foreach ($categorias as $cat): ?>
                <a class="cat-card reveal" href="<?= url('/productos/' . e($cat['slug'])) ?>">
                    <span class="cat-card__name"><?= e($cat['nombre']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($destacados): ?>
        <div class="grid grid--4">
            <?php foreach ($destacados as $p): ?>
                <article class="prod-card reveal">
                    <div class="prod-card__media">
                        <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2h12l-1 20H7L6 2zM6 7h12"/></svg>
                    </div>
                    <div class="prod-card__body">
                        <span class="badge-destacado">Destacado</span>
                        <div class="prod-card__name"><?= e($p['nombre']) ?></div>
                        <div class="prod-card__meta"><?= e($p['unidad'] ?? '') ?></div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="text-center mt-12">
            <a class="btn btn--primary btn--lg" href="<?= url('/productos') ?>">Ver catálogo completo</a>
        </div>
    </div>
</section>

<!-- LABORATORIO DE IMPRESIÓN -->
<section class="section" id="impresion">
    <div class="container">
        <div class="section__head reveal">
            <span class="section__eyebrow">Laboratorio de impresión</span>
            <h2 class="section__title">Lleva tu marca en cada empaque</h2>
            <p class="section__subtitle">
                Personaliza vasos, contenedores y servilletas con el diseño de tu negocio.
                Sube tu logo y pruébalo tú mismo antes de cotizar.
            </p>
        </div>
        <div class="reveal">
            <?php require APP_PATH . '/Views/partials/logo_simulator.php'; ?>
        </div>
        <ul class="check-list check-list--center mt-8">
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg> Desde 1,000 piezas</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg> Tintas grado alimenticio, libres de plomo</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg> Impresión detallada sobre cualquier superficie</li>
        </ul>
        <div class="text-center mt-8">
            <a class="btn btn--accent" href="#cotiza">Cotiza tu diseño</a>
        </div>
    </div>
</section>

<!-- MARCAS -->
<section class="section section--soft">
    <div class="container">
        <div class="section__head reveal">
            <span class="section__eyebrow">Respaldo</span>
            <h2 class="section__title">Trabajamos con los mejores proveedores</h2>
        </div>
        <?php require APP_PATH . '/Views/partials/proveedores.php'; ?>
    </div>
</section>

<!-- SECTORES -->
<section class="section">
    <div class="container">
        <div class="section__head reveal">
            <span class="section__eyebrow">A quién servimos</span>
            <h2 class="section__title">Sectores que atendemos</h2>
        </div>
        <div class="sectors">
            <?php
            $sectores = ['Cafeterías','Restaurantes','Hoteles','Hospitales','Panaderías','Heladerías','Comedores industriales','Eventos empresariales'];
            foreach ($sectores as $s): ?>
                <div class="sector reveal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                    <span><?= e($s) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--soft">
    <div class="container">
        <div class="section__head reveal">
            <span class="section__eyebrow">Clientes</span>
            <h2 class="section__title">Empresas que confían en nosotros</h2>
            <p class="section__subtitle">Negocios y marcas que día a día trabajan con Importadora RYM.</p>
        </div>
        <?php require APP_PATH . '/Views/partials/clientes.php'; ?>
    </div>
</section>

<!-- CTA + FORMULARIO DE COTIZACIÓN -->
<section class="section cta" id="cotiza">
    <div class="container cta__grid">
        <div>
            <span class="section__eyebrow">Solicita tu cotización</span>
            <h2 class="section__title text-left">Cerremos el trato hoy mismo</h2>
            <p class="cta__text">
                Déjanos tus datos y un asesor te contactará con una propuesta a la medida de tu negocio.
            </p>
            <p class="cta__note">
                O escríbenos directo por WhatsApp:
                <a href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener" class="link-on-dark">55 5297 9776</a>
            </p>
        </div>

        <form class="form" method="post" action="<?= url('/cotizar') ?>" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <?= honeypot_field() ?>

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
                <label for="mensaje">¿Qué necesitas cotizar?</label>
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
    </div>
</section>
