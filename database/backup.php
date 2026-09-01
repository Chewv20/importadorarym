<?php

declare(strict_types=1);

/**
 * Respaldo de la base de datos en PHP puro (sin mysqldump).
 * Uso:  php database/backup.php
 *
 * Genera storage/backups/rym-YYYYMMDD-HHMM.sql con la estructura y los datos
 * de todas las tablas. Conserva los últimos RETENER respaldos.
 *
 * Cron sugerido (producción, diario 3:00 am):
 *   0 3 * * * /usr/bin/php /ruta/al/proyecto/database/backup.php >> /ruta/al/proyecto/storage/logs/backup.log 2>&1
 */

require __DIR__ . '/_bootstrap.php';

use App\Core\Database;

const RETENER = 14; // cuántos respaldos conservar

$dir = ROOT_PATH . '/storage/backups';
if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
    fwrite(STDERR, "No se pudo crear $dir\n");
    exit(1);
}

try {
    $db   = Database::connection();
    $name = (string) config('database.database');
    $archivo = $dir . '/rym-' . date('Ymd-Hi') . '.sql';
    $fh = fopen($archivo, 'w');
    if (!$fh) {
        throw new RuntimeException("No se pudo abrir $archivo para escribir.");
    }

    fwrite($fh, "-- Respaldo de `{$name}` — " . date('Y-m-d H:i:s') . "\n");
    fwrite($fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

    $tablas = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tablas as $tabla) {
        // Estructura
        $create = $db->query("SHOW CREATE TABLE `{$tabla}`")->fetch(PDO::FETCH_ASSOC);
        $ddl = $create['Create Table'] ?? ($create['Create View'] ?? '');
        fwrite($fh, "DROP TABLE IF EXISTS `{$tabla}`;\n{$ddl};\n\n");

        // Datos
        $rows = $db->query("SELECT * FROM `{$tabla}`");
        $cols = null;
        while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
            if ($cols === null) {
                $cols = '`' . implode('`, `', array_keys($row)) . '`';
            }
            $vals = array_map(static function ($v) use ($db) {
                return $v === null ? 'NULL' : $db->quote((string) $v);
            }, array_values($row));
            fwrite($fh, "INSERT INTO `{$tabla}` ({$cols}) VALUES (" . implode(', ', $vals) . ");\n");
        }
        fwrite($fh, "\n");
    }

    fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fh);

    // Rotación: conserva los últimos RETENER respaldos.
    $todos = glob($dir . '/rym-*.sql') ?: [];
    rsort($todos);
    foreach (array_slice($todos, RETENER) as $viejo) {
        @unlink($viejo);
    }

    $kb = round(filesize($archivo) / 1024, 1);
    echo "Respaldo creado: " . basename($archivo) . " ({$kb} KB). Tablas: " . count($tablas) . ".\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Error en el respaldo: " . $e->getMessage() . "\n");
    exit(1);
}
