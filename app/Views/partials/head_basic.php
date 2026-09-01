<?php
/** Head básico para las páginas del portal (privadas, noindex). */
$t = $title ?? 'Portal — Importadora RYM';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($t) ?></title>
<meta name="robots" content="noindex, nofollow">

<link rel="icon" href="<?= asset('assets/img/logos/favicon.ico') ?>" sizes="any">
<meta name="theme-color" content="#2A3A8F">
<link rel="manifest" href="<?= asset('manifest.webmanifest') ?>">
<link rel="apple-touch-icon" href="<?= asset('assets/img/icons/apple-touch-icon.png') ?>">

<?php /* Tipografía autoalojada (ver layouts/main.php): el preload va SIN ?v= para
         coincidir con la URL que resuelve fonts.css y no descargarla dos veces. */ ?>
<link rel="preload" href="<?= base_url('assets/fonts/inter-variable-latin.woff2') ?>"
      as="font" type="font/woff2" crossorigin>

<?php /* Las hojas las declara cada layout con css_bundle(): así el conjunto que
         se empaqueta y el que se sirve son siempre el mismo. */ ?>
