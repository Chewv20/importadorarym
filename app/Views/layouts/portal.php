<?php
$active  = $active ?? '';
$usuario = $usuario ?? [];
?>
<!DOCTYPE html>
<html lang="es" data-base="<?= e(base_url('/')) ?>">
<head>
    <?php require APP_PATH . '/Views/partials/head_basic.php'; ?>
    <?= css_bundle('portal') ?>
</head>
<body>
    <header class="portal-header">
        <div class="container portal-header__inner">
            <a class="portal-brand" href="<?= url('/portal') ?>">
                <img src="<?= asset('assets/img/logos/importadorarym.jpg') ?>" alt="Importadora RYM" width="284" height="92">
                Portal de clientes
            </a>

            <nav class="portal-nav" id="portalNav" aria-label="Navegación del portal">
                <a class="portal-nav__link <?= $active === 'inicio' ? 'is-active' : '' ?>" href="<?= url('/portal') ?>">Panel</a>
                <a class="portal-nav__link <?= $active === 'cotizar' ? 'is-active' : '' ?>" href="<?= url('/portal/cotizaciones') ?>">Cotizaciones</a>
                <?php if ((int) ($usuario['aprobado'] ?? 0) === 1): ?>
                    <a class="portal-nav__link <?= $active === 'nuevo' ? 'is-active' : '' ?>" href="<?= url('/portal/pedidos/nuevo') ?>">Nuevo pedido</a>
                    <a class="portal-nav__link <?= $active === 'pedidos' ? 'is-active' : '' ?>" href="<?= url('/portal/pedidos') ?>">Mis pedidos</a>
                    <a class="portal-nav__link <?= $active === 'recurrentes' ? 'is-active' : '' ?>" href="<?= url('/portal/recurrentes') ?>">Recurrentes</a>
                <?php endif; ?>
                <a class="portal-nav__link <?= $active === 'perfil' ? 'is-active' : '' ?>" href="<?= url('/portal/perfil') ?>">Perfil</a>
            </nav>

            <div class="portal-header__actions">
                <div class="portal-user">
                    <span class="portal-user__name">Hola, <?= e($usuario['nombre'] ?? '') ?></span>
                    <form method="post" action="<?= url('/portal/logout') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--ghost btn--sm">Salir</button>
                    </form>
                </div>
                <button class="portal-nav-toggle" id="portalNavToggle" aria-label="Abrir menú" aria-controls="portalNav" aria-expanded="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
            </div>
        </div>
        <div class="portal-nav-backdrop" id="portalNavBackdrop"></div>
    </header>

    <main class="portal-main">
        <div class="container">
            <?php require APP_PATH . '/Views/partials/flash.php'; ?>
            <?= $content ?? '' ?>
        </div>
    </main>

    <script src="<?= asset('assets/js/portal.js') ?>" defer></script>
    <script src="<?= asset('assets/js/direccion.js') ?>" defer></script>
    <script src="<?= asset('assets/js/pwa.js') ?>" defer></script>
</body>
</html>
