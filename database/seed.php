<?php

/**
 * Puebla la base con datos iniciales.
 * Uso:  php database/seed.php ["ContraseñaAdmin"] [--solo-admin]
 *
 * - Crea un usuario administrador (si no existe).
 * - Inserta categorías y productos de ejemplo (idempotente por slug), salvo
 *   que se pase --solo-admin (producción: el catálogo real se carga aparte
 *   con el importador CSV, sin datos ficticios de por medio).
 */

require __DIR__ . '/_bootstrap.php';

use App\Core\Database;

$args = array_slice($argv, 1);
$soloAdmin = in_array('--solo-admin', $args, true);
$args = array_values(array_filter($args, static fn ($a) => $a !== '--solo-admin'));

try {
    $db = Database::connection();

    /* ---- Usuario administrador ------------------------------------------ */
    $adminEmail = 'admin@importadorarym.com.mx';
    $adminPass  = $args[0] ?? 'Rym#Admin2026';

    $st = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $st->execute([$adminEmail]);

    if (!$st->fetch()) {
        $adminRolId = $db->query("SELECT id FROM roles WHERE slug = 'admin'")->fetchColumn();
        $db->prepare(
            "INSERT INTO usuarios (nombre, email, password, rol_id, aprobado)
             VALUES (?, ?, ?, ?, 1)"
        )->execute(['Administrador', $adminEmail, password_hash($adminPass, PASSWORD_DEFAULT), $adminRolId ?: null]);
        echo "Admin creado -> {$adminEmail} / {$adminPass}\n";
    } else {
        echo "Admin ya existe -> {$adminEmail}\n";
    }

    if ($soloAdmin) {
        echo "\n--solo-admin: se omiten las categorías y productos de ejemplo.\n";
        echo "\nSeed completado.\n";
        exit(0);
    }

    /* ---- Categorías ----------------------------------------------------- */
    $categorias = [
        ['Vasos y contenedores', 'vasos-y-contenedores', 'Vasos de papel, plástico y contenedores para alimentos'],
        ['Cubiertos', 'cubiertos', 'Cubiertos de plástico y biodegradables'],
        ['Servilletas y papel', 'servilletas-y-papel', 'Servilletas, toallas y papel para higiene'],
        ['Bolsas y empaques', 'bolsas-y-empaques', 'Bolsas, film y empaques para tu negocio'],
    ];

    $catIds = [];
    foreach ($categorias as $i => [$nombre, $slug, $desc]) {
        $st = $db->prepare("SELECT id FROM categorias WHERE slug = ?");
        $st->execute([$slug]);
        $id = $st->fetchColumn();

        if (!$id) {
            $db->prepare("INSERT INTO categorias (nombre, slug, descripcion, orden) VALUES (?, ?, ?, ?)")
               ->execute([$nombre, $slug, $desc, $i]);
            $id = $db->lastInsertId();
        }
        $catIds[$slug] = $id;
    }
    echo count($categorias) . " categorías listas.\n";

    /* ---- Productos de ejemplo ------------------------------------------- */
    $productos = [
        // [categoria_slug, nombre, slug, descripcion, unidad, destacado]
        ['vasos-y-contenedores', 'Vaso de papel 12 oz', 'vaso-papel-12oz', 'Vaso de papel para bebidas calientes.', 'Paquete 50 pzas', 1],
        ['cubiertos', 'Tenedor biodegradable', 'tenedor-biodegradable', 'Tenedor a base de almidón de maíz.', 'Caja 1000 pzas', 1],
        ['servilletas-y-papel', 'Servilleta blanca doble hoja', 'servilleta-blanca', 'Servilleta de papel de alta absorción.', 'Paquete 500 pzas', 0],
        ['bolsas-y-empaques', 'Bolsa kraft mediana', 'bolsa-kraft-mediana', 'Bolsa de papel kraft para llevar.', 'Paquete 100 pzas', 1],
    ];

    foreach ($productos as $i => [$catSlug, $nombre, $slug, $desc, $unidad, $destacado]) {
        $st = $db->prepare("SELECT id FROM productos WHERE slug = ?");
        $st->execute([$slug]);
        if ($st->fetch()) {
            continue;
        }
        $db->prepare(
            "INSERT INTO productos (categoria_id, nombre, slug, descripcion, unidad, destacado, orden)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        )->execute([$catIds[$catSlug] ?? null, $nombre, $slug, $desc, $unidad, $destacado, $i]);
    }
    echo count($productos) . " productos de ejemplo listos.\n";

    echo "\nSeed completado.\n";
} catch (PDOException $e) {
    fwrite(STDERR, "\nError en seed: " . $e->getMessage() . "\n");
    exit(1);
}
