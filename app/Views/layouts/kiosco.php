<?php /** Layout del kiosco de visitas: pantalla completa, táctil, sin navegación. */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require APP_PATH . '/Views/partials/head_basic.php'; ?>
    <?= css_bundle('kiosco') ?>
</head>
<body class="kiosco">
    <main class="kiosco__wrap">
        <?= $content ?? '' ?>
    </main>
    <script src="<?= asset('assets/js/kiosco.js') ?>" defer></script>
</body>
</html>
