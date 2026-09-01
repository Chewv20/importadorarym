<?php
$_title  = $title ?? 'Importadora RYM — Insumos y empaques para empresas';
$_desc   = $metaDescription ?? 'Fabricación, impresión personalizada y distribución de insumos y empaques para la industria alimentaria. 35 años de experiencia.';
$_ogImg  = asset_url($ogImage ?? 'assets/img/og-image.jpg');
$_robots = $robots ?? 'index, follow';
$_keywords = $keywords ?? 'empaques, insumos, vasos, contenedores, servilletas, cubiertos, impresión personalizada, industria alimentaria, cafeterías, restaurantes';
$_canonical = canonical_url();
$_locale = config('app.locale', 'es_MX');
?>
<!DOCTYPE html>
<html lang="es" data-base="<?= e(base_url('/')) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php require APP_PATH . '/Views/partials/analytics.php'; ?>

    <title><?= e($_title) ?></title>
    <meta name="description" content="<?= e($_desc) ?>">
    <meta name="keywords" content="<?= e($_keywords) ?>">
    <meta name="author" content="Importadora RYM S.A. de C.V.">
    <meta name="robots" content="<?= e($_robots) ?>">
    <link rel="canonical" href="<?= e($_canonical) ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Importadora RYM">
    <meta property="og:locale" content="<?= e($_locale) ?>">
    <meta property="og:title" content="<?= e($_title) ?>">
    <meta property="og:description" content="<?= e($_desc) ?>">
    <meta property="og:url" content="<?= e($_canonical) ?>">
    <meta property="og:image" content="<?= e($_ogImg) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($_title) ?>">
    <meta name="twitter:description" content="<?= e($_desc) ?>">
    <meta name="twitter:image" content="<?= e($_ogImg) ?>">

    <!-- Geo (negocio local) -->
    <meta name="geo.region" content="MX-CMX">
    <meta name="geo.placename" content="Iztapalapa, Ciudad de México">
    <meta name="geo.position" content="19.3293018;-99.0802377">
    <meta name="ICBM" content="19.3293018, -99.0802377">

    <link rel="icon" href="<?= asset('assets/img/logos/favicon.ico') ?>" sizes="any">

    <!-- PWA -->
    <link rel="manifest" href="<?= asset('manifest.webmanifest') ?>">
    <meta name="theme-color" content="#2A3A8F">
    <link rel="apple-touch-icon" href="<?= asset('assets/img/icons/apple-touch-icon.png') ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="RYM">

    <?php require APP_PATH . '/Views/partials/schema.php'; ?>

    <?php /* Tipografía autoalojada: sin conexiones a Google en la ruta crítica.
             El preload va SIN ?v= para que coincida con la URL que resuelve
             fonts.css; si difiriera, el navegador descargaría el archivo dos veces. */ ?>
    <link rel="preload" href="<?= base_url('assets/fonts/inter-variable-latin.woff2') ?>"
          as="font" type="font/woff2" crossorigin>
    <?= css_bundle('site') ?>
</head>
<body>
    <?php require APP_PATH . '/Views/partials/topbar.php'; ?>
    <?php require APP_PATH . '/Views/partials/header.php'; ?>

    <main><?= $content ?? '' ?></main>

    <?php require APP_PATH . '/Views/partials/footer.php'; ?>
    <?php require APP_PATH . '/Views/partials/modal_promo.php'; ?>
    <?php require APP_PATH . '/Views/partials/whatsapp_float.php'; ?>
    <?php require APP_PATH . '/Views/partials/lightbox.php'; ?>

    <script src="<?= asset('assets/js/site.js') ?>" defer></script>
    <script src="<?= asset('assets/js/whatsapp.js') ?>" defer></script>
    <script src="<?= asset('assets/js/logo-simulator.js') ?>" defer></script>
    <script src="<?= asset('assets/js/pwa.js') ?>" defer></script>
</body>
</html>
