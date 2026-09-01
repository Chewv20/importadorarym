<?php

declare(strict_types=1);

/**
 * Respaldo de los documentos subidos que viven fuera de la base de datos:
 * facturas (PDF+XML), logos de cotización y CVs de postulación.
 * Uso:  php database/backup_archivos.php
 *
 * Genera storage/backups/rym-archivos-YYYYMMDD-HHMM.zip con el contenido de
 * storage/facturas, storage/cotizaciones_logos y storage/cvs. Conserva los
 * últimos RETENER respaldos, igual que backup.php.
 *
 * Cron sugerido (producción, diario 3:15 am — después de backup.php):
 *   15 3 * * * /usr/bin/php /ruta/al/proyecto/database/backup_archivos.php >> /ruta/al/proyecto/storage/logs/backup.log 2>&1
 */

require __DIR__ . '/_bootstrap.php';

const RETENER = 14; // cuántos respaldos conservar

const CARPETAS = ['facturas', 'cotizaciones_logos', 'cvs'];

$dir = ROOT_PATH . '/storage/backups';
if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
    fwrite(STDERR, "No se pudo crear $dir\n");
    exit(1);
}

$archivo = $dir . '/rym-archivos-' . date('Ymd-Hi') . '.zip';
$zip = new ZipArchive();
if ($zip->open($archivo, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "No se pudo crear $archivo\n");
    exit(1);
}

try {
    $total = 0;
    foreach (CARPETAS as $carpeta) {
        $base = ROOT_PATH . '/storage/' . $carpeta;
        if (!is_dir($base)) {
            continue;
        }
        foreach (new DirectoryIterator($base) as $f) {
            if ($f->isDot() || !$f->isFile() || $f->getFilename() === '.htaccess') {
                continue;
            }
            $zip->addFile($f->getPathname(), $carpeta . '/' . $f->getFilename());
            $total++;
        }
    }
    $zip->close();

    if ($total === 0) {
        // libzip no escribe el .zip en disco si no se agregó ningún archivo.
        @unlink($archivo);
        echo "Sin documentos que respaldar (facturas/cotizaciones_logos/cvs vacíos).\n";
        exit(0);
    }

    // Rotación: conserva los últimos RETENER respaldos.
    $todos = glob($dir . '/rym-archivos-*.zip') ?: [];
    rsort($todos);
    foreach (array_slice($todos, RETENER) as $viejo) {
        @unlink($viejo);
    }

    $kb = round(filesize($archivo) / 1024, 1);
    echo "Respaldo de archivos creado: " . basename($archivo) . " ({$kb} KB). Documentos: {$total}.\n";
} catch (Throwable $e) {
    try { $zip->close(); } catch (Throwable $ignorada) { /* ya estaba cerrado */ }
    @unlink($archivo);
    fwrite(STDERR, "Error en el respaldo de archivos: " . $e->getMessage() . "\n");
    exit(1);
}
