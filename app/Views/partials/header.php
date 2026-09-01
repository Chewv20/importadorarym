<?php $active = $active ?? ''; ?>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="site-logo" href="<?= url('/') ?>" aria-label="Importadora RYM - Inicio">
            <img src="<?= asset('assets/img/logos/importadorarym.jpg') ?>" alt="Importadora RYM" width="284" height="92">
        </a>

        <nav class="nav" id="nav">
            <a class="nav__link <?= nav_active('inicio', $active) ?>" href="<?= url('/') ?>">Inicio</a>
            <a class="nav__link <?= nav_active('nosotros', $active) ?>" href="<?= url('/nosotros') ?>">Nosotros</a>
            <a class="nav__link <?= nav_active('productos', $active) ?>" href="<?= url('/productos') ?>">Productos</a>
            <a class="nav__link <?= nav_active('personalizacion', $active) ?>" href="<?= url('/personalizacion') ?>">Personalización</a>
            <a class="nav__link <?= nav_active('reciclaje', $active) ?>" href="<?= url('/reciclaje') ?>">Reciclaje</a>
            <a class="nav__link <?= nav_active('contacto', $active) ?>" href="<?= url('/contacto') ?>">Contacto</a>
            <a class="btn btn--outline" href="<?= url('/portal/login') ?>">Portal clientes</a>
        </nav>

        <div class="nav__actions">
            <button type="button" id="installBtn" class="btn btn--outline is-hidden">Instalar app</button>
            <a class="btn btn--accent" href="<?= url('/#cotiza') ?>">Cotiza ahora</a>
            <button class="nav-toggle" id="navToggle" aria-label="Abrir menú" aria-controls="nav" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
        </div>
    </div>
    <div class="nav-backdrop" id="navBackdrop"></div>
</header>
