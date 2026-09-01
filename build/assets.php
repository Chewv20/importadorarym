<?php

declare(strict_types=1);

/**
 * Empaquetado de CSS para producción (línea de comandos).
 * Uso:  php build/assets.php [--check]
 *
 * Concatena y minifica los bundles declarados en BUNDLES, escribiendo
 * public/assets/css/<nombre>.min.css. El helper asset_bundle() sirve el
 * minificado cuando existe y APP_DEBUG=false; en desarrollo se siguen sirviendo
 * los archivos sueltos, para no tener que reconstruir en cada cambio.
 *
 * --check  no escribe nada; devuelve código 1 si algún bundle está desactualizado
 *          (útil para verificar antes de publicar).
 *
 * No minifica JS: los archivos del proyecto son pequeños y un minificador de JS
 * casero es una fuente de errores sutiles. gzip ya hace el grueso del trabajo.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root   = dirname(__DIR__);
$cssDir = $root . '/public/assets/css';

/* La composición de cada bundle vive en App\Core\Assets, que es también lo que
   usan las vistas: una sola fuente de verdad para lo que se empaqueta y lo que
   se sirve. El minificador está en Core para que las pruebas lo cubran. */
require_once $root . '/app/Core/CssMin.php';
require_once $root . '/app/Core/Assets.php';

use App\Core\Assets;
use App\Core\CssMin;

$check = in_array('--check', $argv, true);

$errores = 0;
$desactualizados = [];

echo "Empaquetado de CSS" . ($check ? " (solo comprobación)" : '') . "\n";
echo str_repeat('-', 62) . "\n";

foreach (Assets::BUNDLES as $nombre => $partes) {
    $fuente = '';
    $mtimeMax = 0;
    $faltan = [];

    foreach ($partes as $parte) {
        $ruta = $cssDir . '/' . $parte;
        if (!is_file($ruta)) {
            $faltan[] = $parte;
            continue;
        }
        $fuente .= "/* --- {$parte} --- */\n" . file_get_contents($ruta) . "\n";
        $mtimeMax = max($mtimeMax, (int) filemtime($ruta));
    }

    if ($faltan) {
        fwrite(STDERR, "  ! {$nombre}: faltan " . implode(', ', $faltan) . "\n");
        $errores++;
        continue;
    }

    $destino = $cssDir . '/' . Assets::archivoMin($nombre);
    $vigente = is_file($destino) && (int) filemtime($destino) >= $mtimeMax;

    if ($check) {
        if (!$vigente) {
            $desactualizados[] = $nombre;
        }
        printf("  %-16s %s\n", $nombre, $vigente ? 'al día' : 'DESACTUALIZADO');
        continue;
    }

    $min = CssMin::minificar($fuente);
    file_put_contents($destino, $min);
    // El mtime debe quedar por delante del de sus fuentes para que --check
    // no lo dé por desactualizado nada más generarlo.
    touch($destino, max(time(), $mtimeMax + 1));

    printf(
        "  %-16s %6.1f KB -> %5.1f KB  (%2.0f%% menos, %4.1f KB con gzip)\n",
        $nombre,
        strlen($fuente) / 1024,
        strlen($min) / 1024,
        (1 - strlen($min) / strlen($fuente)) * 100,
        strlen(gzencode($min, 6)) / 1024
    );
}

if ($check && $desactualizados) {
    fwrite(STDERR, "\nHay bundles desactualizados: " . implode(', ', $desactualizados)
        . "\nEjecuta: php build/assets.php\n");
    exit(1);
}

if ($errores) {
    exit(1);
}

echo str_repeat('-', 62) . "\n";
echo $check ? "Todos los bundles están al día.\n" : "Listo.\n";
