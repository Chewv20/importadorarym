<!DOCTYPE html>
<html lang="es" data-base="<?= e(base_url('/')) ?>">
<head>
    <?php require APP_PATH . '/Views/partials/head_basic.php'; ?>
    <?= css_bundle('portal') ?>
</head>
<body>
    <main class="auth-shell">
        <?= $content ?? '' ?>
    </main>

    <script src="<?= asset('assets/js/direccion.js') ?>" defer></script>
    <script src="<?= asset('assets/js/pwa.js') ?>" defer></script>
</body>
</html>
