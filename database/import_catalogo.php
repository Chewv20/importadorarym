<?php

declare(strict_types=1);

/**
 * Importador de catálogo desde CSV (línea de comandos).
 *
 * Uso:
 *   php database/import_catalogo.php ruta/al/catalogo.csv [--dry-run]
 *
 * La lógica vive en App\Core\CatalogoImport (compartida con el panel).
 * Plantilla de ejemplo: database/plantilla_catalogo.csv
 */

require __DIR__ . '/_bootstrap.php';

use App\Core\CatalogoImport;

$archivo = $argv[1] ?? '';
$dryRun  = in_array('--dry-run', $argv, true);

if ($archivo === '') {
    fwrite(STDERR, "Uso: php database/import_catalogo.php <archivo.csv> [--dry-run]\n");
    exit(1);
}

$r = CatalogoImport::procesar($archivo, $dryRun);

if ($r['error'] !== null) {
    fwrite(STDERR, "Error: {$r['error']}\n");
    exit(1);
}

$modo = $dryRun ? " (SIMULACIÓN, no se escribió nada)" : '';
echo "Importación de catálogo{$modo}\n";
echo str_repeat('-', 40) . "\n";
echo "Creados:           {$r['creados']}\n";
echo "Actualizados:      {$r['actualizados']}\n";
echo "Omitidos:          {$r['omitidos']}\n";
echo "Categorías nuevas: {$r['categorias']}\n";
if ($r['errores']) {
    echo "\nAvisos:\n - " . implode("\n - ", $r['errores']) . "\n";
}
exit(0);
