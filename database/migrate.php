<?php

/**
 * Ejecuta las migraciones pendientes.
 * Uso:  php database/migrate.php
 *
 * - Crea la base de datos si no existe.
 * - Aplica en orden los .sql de database/migrations que aún no se hayan corrido.
 * - Registra cada migración en la tabla de control `migrations`.
 */

require __DIR__ . '/_bootstrap.php';

use App\Core\Config;

$c = Config::get('database');

try {
    // 1) Conexión sin base de datos, para poder crearla.
    $dsn = sprintf('%s:host=%s;port=%s;charset=%s', $c['driver'], $c['host'], $c['port'], $c['charset']);
    $pdo = new PDO($dsn, $c['username'], $c['password'], $c['options']);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$c['database']}`
                CHARACTER SET {$c['charset']} COLLATE {$c['collation']}");
    echo "Base de datos `{$c['database']}` lista.\n";

    $pdo->exec("USE `{$c['database']}`");

    // 2) Tabla de control de migraciones.
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        migration VARCHAR(191) NOT NULL,
        ejecutada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_migrations (migration)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $done = $pdo->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_COLUMN);

    // 3) Aplicar pendientes.
    $files = glob(__DIR__ . '/migrations/*.sql');
    sort($files);

    $applied = 0;
    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, $done, true)) {
            echo "  · {$name} (ya aplicada)\n";
            continue;
        }
        $pdo->exec(file_get_contents($file));
        $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)")->execute([$name]);
        echo "  ✔ {$name}\n";
        $applied++;
    }

    echo $applied > 0
        ? "\n{$applied} migración(es) aplicada(s).\n"
        : "\nSin migraciones pendientes.\n";
} catch (PDOException $e) {
    fwrite(STDERR, "\nError de migración: " . $e->getMessage() . "\n");
    exit(1);
}
