<?php
/** Layout de la interfaz de reparto: pensada para el celular del repartidor, poco JS, botones grandes. */
$usuario = $usuario ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require APP_PATH . '/Views/partials/head_basic.php'; ?>
    <?= css_bundle('reparto') ?>
</head>
<body class="reparto">
    <header class="reparto-top">
        <span class="reparto-top__left">
            <?php if (can('admin.acceder')): ?>
                <a class="reparto-top__panel" href="<?= url('/admin') ?>">← Panel</a>
            <?php endif; ?>
            <span class="reparto-top__brand">Reparto RYM</span>
        </span>
        <span class="reparto-top__user">
            <?= e($usuario['nombre'] ?? '') ?>
            <form method="post" action="<?= url('/portal/logout') ?>" class="reparto-top__logout">
                <?= csrf_field() ?>
                <button type="submit">Salir</button>
            </form>
        </span>
    </header>
    <main class="reparto-wrap">
        <?php require APP_PATH . '/Views/partials/flash.php'; ?>
        <?= $content ?? '' ?>
    </main>
    <script src="<?= asset('assets/js/reparto.js') ?>" defer></script>
</body>
</html>
