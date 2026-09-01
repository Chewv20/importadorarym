<?php

declare(strict_types=1);

/**
 * Datos de DEMOSTRACIÓN para montar una presentación del sistema.
 * Uso:  php database/demo_seed.php
 *
 * Añade catálogo ampliado, un vendedor, clientes, cotizaciones y pedidos en
 * varios estados, anfitriones, dispositivos de visitas y visitas de ejemplo.
 * Pensado para correrse sobre una base recién migrada + sembrada (seed.php).
 * Idempotente por correo/slug donde aplica.
 *
 * Contraseña de todos los usuarios demo (clientes y vendedor): Demo#2026
 */

require __DIR__ . '/_bootstrap.php';

use App\Core\Database;
use App\Models\Usuario;
use App\Models\Categoria;
use App\Models\Cotizacion;
use App\Models\Pedido;
use App\Models\PedidoRecurrente;
use App\Models\EncuestaPedido;
use App\Models\Vacante;
use App\Models\Postulacion;
use App\Models\Anfitrion;
use App\Models\ChecadorDispositivo;
use App\Models\Visita;

$db = Database::connection();
$PASS = 'Demo#2026';

/* ---------------------------------------------------- Subcategorías ------- */
// Ejemplo de jerarquía de 2 niveles (categoría → subcategoría): una categoría
// "Biodegradables" nueva con subcategorías "Bolsas" y "Cubiertos", más una
// subcategoría "Compostables" dentro de "Bolsas y empaques" (ya existente).
$cm = new Categoria();
$catId = function (string $slug) use ($db) {
    $st = $db->prepare("SELECT id FROM categorias WHERE slug = ?");
    $st->execute([$slug]);
    return $st->fetchColumn() ?: null;
};
$subcatId = function (string $nombre, int $padreId) use ($db, $cm) {
    $st = $db->prepare("SELECT id FROM categorias WHERE nombre = ? AND categoria_padre_id = ?");
    $st->execute([$nombre, $padreId]);
    $id = $st->fetchColumn();
    return $id ?: $cm->crear(['nombre' => $nombre, 'slug' => '', 'categoria_padre_id' => $padreId, 'activo' => 1]);
};

$idBiodegradables = $catId('biodegradables');
if (!$idBiodegradables) {
    $idBiodegradables = $cm->crear(['nombre' => 'Biodegradables', 'slug' => 'biodegradables', 'activo' => 1, 'orden' => 5]);
}
$idBolsasBio = $subcatId('Bolsas', $idBiodegradables);
$idCubiertosBio = $subcatId('Cubiertos', $idBiodegradables);
$idCompostables = $subcatId('Compostables', (int) $catId('bolsas-y-empaques'));
echo "Subcategorías demo listas (Biodegradables → Bolsas/Cubiertos; Bolsas y empaques → Compostables).\n";

/* ---------------------------------------------------- Catálogo ampliado --- */
$productos = [
    // [categoria_slug_o_id, nombre, slug, descripcion, sku, precio, unidad, destacado]
    ['vasos-y-contenedores', 'Vaso de papel 8 oz',        'vaso-papel-8oz',   'Vaso de papel para bebidas calientes 8 oz.',  'VP-08', 0.95, 'Paquete 50 pzas', 1],
    ['vasos-y-contenedores', 'Vaso de papel 16 oz',       'vaso-papel-16oz',  'Vaso de papel para bebidas frías/calientes.', 'VP-16', 1.35, 'Paquete 50 pzas', 0],
    ['vasos-y-contenedores', 'Contenedor kraft 750 ml',   'contenedor-kraft-750', 'Contenedor kraft con tapa para alimentos.', 'CK-750', 2.80, 'Caja 300 pzas', 1],
    ['cubiertos', 'Cuchara biodegradable',    'cuchara-biodegradable', 'Cuchara a base de almidón de maíz.',      'CB-01', 0.60, 'Caja 1000 pzas', 0],
    ['cubiertos', 'Kit cubiertos + servilleta','kit-cubiertos',   'Tenedor, cuchillo y servilleta empacados.',   'KC-01', 1.10, 'Caja 500 pzas', 1],
    ['servilletas-y-papel',   'Toalla en rollo',          'toalla-rollo',     'Toalla de papel en rollo para cocina.',       'TR-01', 18.50, 'Rollo', 0],
    ['bolsas-y-empaques',     'Film plástico 30 cm',      'film-plastico-30', 'Rollo de film para empaque de alimentos.',    'FP-30', 42.00, 'Rollo 300 m', 0],
    // Subcategorías (id directo, ya resuelto arriba).
    [$idBolsasBio,     'Bolsa biodegradable chica', 'bolsa-bio-chica',  'Bolsa compostable para llevar.',                 'BB-CH', 0.40, 'Paquete 100 pzas', 0],
    [$idCubiertosBio,  'Tenedor de bambú',          'tenedor-bambu',    'Tenedor desechable de fibra de bambú.',          'TB-BA', 0.75, 'Caja 500 pzas', 1],
    [$idCompostables,  'Bolsa compostable grande',  'bolsa-compost-grande', 'Bolsa compostable certificada, uso rudo.',   'BC-GR', 0.85, 'Paquete 50 pzas', 0],
];
foreach ($productos as $i => [$cref, $nombre, $slug, $desc, $sku, $precio, $unidad, $dest]) {
    $st = $db->prepare("SELECT id FROM productos WHERE slug = ?");
    $st->execute([$slug]);
    if ($st->fetch()) { continue; }
    $categoriaId = is_int($cref) ? $cref : $catId($cref);
    $db->prepare(
        "INSERT INTO productos (categoria_id, nombre, slug, descripcion, sku, precio, unidad, destacado, esquema_impuestos, activo, orden)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1, ?)"
    )->execute([$categoriaId, $nombre, $slug, $desc, $sku, $precio, $unidad, $dest, 10 + $i]);
}
// Pone precio/sku/esquema a los productos que sembró seed.php (para que luzcan
// en cotizaciones y se puedan exportar a SAE sin que falte el esquema de impuestos).
$db->exec("UPDATE productos SET precio = 1.20, sku = 'VP-12', esquema_impuestos = 1 WHERE slug = 'vaso-papel-12oz' AND (precio IS NULL OR precio = 0)");
$db->exec("UPDATE productos SET precio = 0.55, sku = 'TB-01', esquema_impuestos = 1 WHERE slug = 'tenedor-biodegradable' AND (precio IS NULL OR precio = 0)");
$db->exec("UPDATE productos SET precio = 0.30, sku = 'SB-01', esquema_impuestos = 1 WHERE slug = 'servilleta-blanca' AND (precio IS NULL OR precio = 0)");
$db->exec("UPDATE productos SET precio = 0.90, sku = 'BK-01', esquema_impuestos = 1 WHERE slug = 'bolsa-kraft-mediana' AND (precio IS NULL OR precio = 0)");
// Productos que típicamente se personalizan con el logo del cliente (vasos y
// contenedores kraft impresos), para que el distintivo "Personalizable" del
// catálogo tenga ejemplos reales en la demo.
$db->exec("UPDATE productos SET personalizable = 1
            WHERE slug IN ('vaso-papel-12oz', 'vaso-papel-8oz', 'vaso-papel-16oz', 'contenedor-kraft-750', 'bolsa-kraft-mediana')");
// El resto del catálogo demo también necesita esquema y clave SAE para poder
// exportarse a SAE sin bloqueos (ver App\Core\SaeExport::filasDePedido()).
$db->exec("UPDATE productos SET esquema_impuestos = 1 WHERE esquema_impuestos IS NULL");
$db->exec("UPDATE productos SET clave_sae = CONCAT('PA', LPAD(id, 7, '0')) WHERE clave_sae IS NULL");
echo "Catálogo ampliado (incluye productos en subcategorías, con esquema de impuestos y clave SAE).\n";

$prodId = function (string $slug) use ($db) {
    $st = $db->prepare("SELECT id, nombre, sku, precio FROM productos WHERE slug = ?");
    $st->execute([$slug]);
    return $st->fetch() ?: null;
};

/* ---------------------------------------------------- Vendedor ------------ */
$um = new Usuario();
$rolVentas = (int) $db->query("SELECT id FROM roles WHERE slug='ventas'")->fetchColumn();
$vendedorId = null;
if (!$um->emailExiste('vendedor@demo.rym')) {
    $vendedorId = $um->crearInterno([
        'nombre' => 'Verónica Sales', 'email' => 'vendedor@demo.rym',
        'password' => $PASS, 'rol_id' => $rolVentas, 'clave_vendedor' => 'V001', 'comision' => 5,
    ]);
    echo "Vendedor creado -> vendedor@demo.rym / {$PASS}\n";
} else {
    $vendedorId = (int) $um->buscarPorEmail('vendedor@demo.rym')['id'];
}

/* ---------------------------------------------------- Clientes ------------ */
$clientes = [
    ['Cafetería La Esquina', 'compras@laesquina.demo', 'Cafetería La Esquina', '5551002030', 'CLE010101AB1', true],
    ['Restaurante El Fogón',  'pedidos@elfogon.demo',  'Restaurante El Fogón',  '5551002031', 'REF020202CD2', true],
    ['Taquería Los Compas',   'contacto@loscompas.demo','Taquería Los Compas',  '5551002032', null,          false],
    ['Panadería Trigo de Oro','ventas@trigodeoro.demo', 'Panadería Trigo de Oro','5551002033', null,          false],
];
$clienteIds = [];
foreach ($clientes as [$nombre, $email, $empresa, $tel, $rfc, $aprobado]) {
    if ($um->emailExiste($email)) { $clienteIds[$email] = (int) $um->buscarPorEmail($email)['id']; continue; }
    $id = $um->crear([
        'nombre' => $nombre, 'email' => $email, 'password' => $PASS, 'rol_slug' => 'cliente',
        'empresa' => $empresa, 'telefono' => $tel, 'rfc' => $rfc,
    ]);
    // Correo verificado; aprobar a algunos y asignarlos al vendedor.
    $db->prepare("UPDATE usuarios SET email_verificado_en = NOW(), aprobado = ?, vendedor_id = ? WHERE id = ?")
       ->execute([$aprobado ? 1 : 0, $vendedorId, $id]);
    $clienteIds[$email] = $id;
}
echo count($clientes) . " clientes demo listos (contraseña {$PASS}).\n";

/* ---------------------------------------------------- Cotizaciones -------- */
$cm = new Cotizacion();
$iva = (float) config('app.iva', 16);
$hacerCotizacion = function (array $cli, array $items, string $estado) use ($cm, $iva, $prodId) {
    $id = $cm->crear([
        'usuario_id' => $cli['id'], 'nombre' => $cli['nombre'], 'empresa' => $cli['empresa'],
        'email' => $cli['email'], 'telefono' => $cli['tel'], 'origen' => 'portal',
        'mensaje' => 'Solicitud de cotización de demostración.',
    ]);
    $cm->generarFolio($id);
    $partidas = [];
    foreach ($items as [$slug, $cant]) {
        $p = $prodId($slug);
        if ($p) { $partidas[] = ['producto_id' => $p['id'], 'descripcion' => $p['nombre'], 'cantidad' => $cant, 'precio_unitario' => (float) $p['precio']]; }
    }
    $cm->agregarItems($id, $partidas);
    $cm->recalcular($id, $iva);
    if ($estado !== 'nueva') { $cm->actualizarEstado($id, $estado); }
    return $id;
};
$cliEsquina = ['id' => $clienteIds['compras@laesquina.demo'], 'nombre' => 'Cafetería La Esquina', 'empresa' => 'Cafetería La Esquina', 'email' => 'compras@laesquina.demo', 'tel' => '5551002030'];
$cliFogon   = ['id' => $clienteIds['pedidos@elfogon.demo'],  'nombre' => 'Restaurante El Fogón', 'empresa' => 'Restaurante El Fogón', 'email' => 'pedidos@elfogon.demo', 'tel' => '5551002031'];
$cliCompas  = ['id' => $clienteIds['contacto@loscompas.demo'],'nombre' => 'Taquería Los Compas', 'empresa' => 'Taquería Los Compas', 'email' => 'contacto@loscompas.demo', 'tel' => '5551002032'];

$hacerCotizacion($cliEsquina, [['vaso-papel-12oz', 20], ['vaso-papel-16oz', 10]], 'cotizada');
$hacerCotizacion($cliFogon,   [['contenedor-kraft-750', 15], ['kit-cubiertos', 20]], 'aprobada');
$hacerCotizacion($cliCompas,  [['bolsa-kraft-mediana', 30]], 'nueva');
// Un lead público (sin usuario) desde el sitio.
$leadId = $cm->crear(['nombre' => 'Laura Méndez', 'empresa' => 'Food Truck Sabor', 'email' => 'laura@sabor.demo', 'telefono' => '5559998877', 'origen' => 'landing', 'producto_interes' => 'Vasos personalizados', 'mensaje' => 'Quiero cotizar vasos con mi logo.']);
$cm->generarFolio($leadId);
echo "Cotizaciones demo listas (varios estados).\n";

/* ---------------------------------------------------- Pedidos ------------- */
$pm = new Pedido();
$hacerPedido = function (int $usuarioId, array $items, string $estado, ?string $referencia, bool $conPrecios, ?string $erpFolio = null) use ($pm, $db, $prodId) {
    $partidas = [];
    foreach ($items as [$slug, $cant]) {
        $p = $prodId($slug);
        if ($p) { $partidas[] = ['producto_id' => $p['id'], 'sku' => $p['sku'], 'nombre' => $p['nombre'], 'cantidad' => $cant, 'precio_unitario' => 0]; }
    }
    $id = $pm->crear($usuarioId, $partidas, 'Pedido de demostración.', $estado, $referencia);
    if ($conPrecios) {
        $its = $db->prepare("SELECT pi.id, pr.precio FROM pedido_items pi JOIN productos pr ON pr.id = pi.producto_id WHERE pi.pedido_id = ?");
        $its->execute([$id]);
        $precios = [];
        foreach ($its->fetchAll() as $r) { $precios[(int) $r['id']] = (float) $r['precio']; }
        $pm->guardarPrecios($id, $precios);
    }
    if ($erpFolio !== null) { $pm->marcarSincronizado($id, $erpFolio); }
    return $id;
};
$pedIdEnviado    = $hacerPedido($clienteIds['compras@laesquina.demo'], [['vaso-papel-12oz', 40], ['servilleta-blanca', 20]], 'enviado', 'OC-1023', true);
$pedIdEnProceso  = $hacerPedido($clienteIds['pedidos@elfogon.demo'],  [['contenedor-kraft-750', 25], ['kit-cubiertos', 30]], 'en_proceso', 'REQ-88', true);
$pedIdSincro     = $hacerPedido($clienteIds['compras@laesquina.demo'], [['toalla-rollo', 12]], 'sincronizado', 'OC-1000', true, 'SAE-4711');
$hacerPedido($clienteIds['pedidos@elfogon.demo'],  [['film-plastico-30', 5]], 'enviado', null, false);
echo "Pedidos demo listos (varios estados).\n";

/* ---------------------------------------------------- Encuestas de pedido - */
$em = new EncuestaPedido();
if ((int) $db->query("SELECT COUNT(*) FROM encuestas_pedido")->fetchColumn() === 0) {
    $em->crear([
        'pedido_id' => $pedIdSincro, 'usuario_id' => $clienteIds['compras@laesquina.demo'],
        'satisfaccion' => 5, 'facilidad' => 5, 'nps' => 9,
        'comentario' => 'Excelente atención y tiempos de entrega, seguiremos comprando.',
    ]);
    $em->crear([
        'pedido_id' => $pedIdEnProceso, 'usuario_id' => $clienteIds['pedidos@elfogon.demo'],
        'satisfaccion' => 4, 'facilidad' => 3, 'nps' => 7,
        'comentario' => 'El pedido en línea es fácil, aunque tardó un poco la confirmación.',
    ]);
    echo "2 encuestas de experiencia demo listas.\n";
}

/* ---------------------------------------------------- Pedido recurrente --- */
$prm = new PedidoRecurrente();
if ((int) $db->query("SELECT COUNT(*) FROM pedidos_recurrentes")->fetchColumn() === 0) {
    $itemsSincro = $pm->items($pedIdSincro);
    if ($itemsSincro) {
        $prm->crear($clienteIds['compras@laesquina.demo'], $pedIdSincro, $itemsSincro, 30, 'Reposición mensual de toallas');
        echo "1 pedido recurrente demo listo (recordatorio cada 30 días).\n";
    }
}

/* ---------------------------------------------------- Anfitriones --------- */
$anf = new Anfitrion();
$anfitriones = [
    ['Laura Gómez', 'laura.gomez@demo.rym', 'Dirección'],
    ['Carlos Ruiz', 'carlos.ruiz@demo.rym', 'Compras'],
    ['Ana Torres',  'ana.torres@demo.rym',  'Ventas'],
    ['Miguel Ángel Díaz', 'miguel.diaz@demo.rym', 'Almacén'],
    ['Recepción General', 'recepcion@demo.rym', 'Recepción'],
];
$anfIds = [];
foreach ($anfitriones as [$n, $e, $a]) {
    $exist = $db->prepare("SELECT id FROM anfitriones WHERE email = ?");
    $exist->execute([$e]);
    $id = $exist->fetchColumn();
    if (!$id) { $id = $anf->crear(['nombre' => $n, 'email' => $e, 'area' => $a, 'activo' => true]); }
    $anfIds[] = (int) $id;
}
echo count($anfitriones) . " anfitriones demo listos.\n";

/* ---------------------------------------------------- Dispositivos -------- */
$cd = new ChecadorDispositivo();
if ((int) $db->query("SELECT COUNT(*) FROM checador_dispositivos")->fetchColumn() === 0) {
    $cd->crear('Recepción Planta');
    $cd->crear('Recepción Oficinas');
    echo "2 dispositivos de visitas creados (pendientes de activar desde el panel).\n";
}

/* ---------------------------------------------------- Visitas ------------- */
$vm = new Visita();
if ((int) $db->query("SELECT COUNT(*) FROM visitas")->fetchColumn() === 0) {
    $visitas = [
        ['Roberto Sánchez', 'Distribuidora Norte', '5551234567', 1, 'Reunión comercial', 0],
        ['María Fernández', 'Bimbo', '5559876543', 2, 'Entrega de mercancía', 1],
        ['José Luis Ramírez', 'Coca-Cola FEMSA', '5553334444', 1, 'Revisión de contrato', 2],
        ['Patricia Núñez', 'Independiente', '', 1, 'Entrevista de trabajo', 3],
        ['Empresa de Mantenimiento', 'ServiTec', '5552221111', 3, 'Mantenimiento de equipos', 4],
        ['Andrea Vega', 'Grupo Modelo', '5557778888', 2, 'Presentación de productos', 0],
    ];
    foreach ($visitas as $k => [$nombre, $empresa, $tel, $pers, $motivo, $anfIdx]) {
        $anfId = $anfIds[$anfIdx] ?? $anfIds[0];
        $emailAnf = $db->query("SELECT email FROM anfitriones WHERE id = {$anfId}")->fetchColumn();
        $vm->crear([
            'nombre_visitante' => $nombre, 'empresa' => $empresa, 'telefono' => $tel,
            'num_personas' => $pers, 'motivo' => $motivo,
            'anfitrion_id' => $anfId, 'anfitrion_email' => $emailAnf,
            'dispositivo_id' => null, 'ip' => '127.0.0.1',
        ]);
        // Escalona las fechas hacia atrás para que el historial luzca.
        $db->exec("UPDATE visitas SET created_at = DATE_SUB(NOW(), INTERVAL {$k} DAY) ORDER BY id DESC LIMIT 1");
    }
    echo count($visitas) . " visitas demo listas.\n";
}

/* ---------------------------------------------------- Bolsa de trabajo ---- */
$vam = new Vacante();
$pom = new Postulacion();
if ((int) $db->query("SELECT COUNT(*) FROM vacantes")->fetchColumn() === 0) {
    $vacantes = [
        ['Ejecutivo de Ventas B2B', 'Ventas', 'Iztapalapa, CDMX', 'tiempo_completo',
            'Atención y seguimiento a clientes del giro alimentario, prospección de nuevas cuentas.',
            'Experiencia en ventas B2B, disponibilidad de viajar en zona metropolitana.', 'abierta'],
        ['Auxiliar de Almacén', 'Almacén', 'Iztapalapa, CDMX', 'tiempo_completo',
            'Recepción, acomodo y surtido de mercancía; apoyo en inventarios.',
            'Disponibilidad de horario, gusto por el orden.', 'abierta'],
        ['Practicante de Marketing Digital', 'Marketing', 'Remoto', 'practicas',
            'Apoyo en redes sociales, contenido y campañas del sitio web.',
            'Estudiante de mercadotecnia o afín, últimos semestres.', 'cerrada'],
    ];
    $vacIds = [];
    foreach ($vacantes as [$tit, $area, $ubi, $tipo, $desc, $req, $estado]) {
        $vacIds[] = $vam->crear(['titulo' => $tit, 'area' => $area, 'ubicacion' => $ubi, 'tipo' => $tipo,
            'descripcion' => $desc, 'requisitos' => $req, 'estado' => $estado]);
    }

    $postulaciones = [
        // [índice de vacante, nombre, email, teléfono, mensaje, estado, con cita]
        [0, 'Jorge Ramírez', 'jorge.ramirez@demo.rym', '5556781234', 'Cuento con 4 años de experiencia en ventas al sector restaurantero.', 'entrevista', true],
        [0, 'Fernanda López', 'fernanda.lopez@demo.rym', '5556781235', 'Me interesa mucho la posición, adjunto mi CV.', 'en_revision', false],
        [1, 'Iván Castro', 'ivan.castro@demo.rym', '5556781236', 'Disponibilidad inmediata, vivo cerca de la planta.', 'recibida', false],
        [2, 'Sofía Herrera', 'sofia.herrera@demo.rym', '5556781237', 'Estudiante de 8vo semestre de Mercadotecnia en la UAM.', 'contratada', false],
    ];
    foreach ($postulaciones as [$vi, $nombre, $email, $tel, $msj, $estado, $conCita]) {
        $pid = $pom->crear(['vacante_id' => $vacIds[$vi], 'nombre' => $nombre, 'email' => $email,
            'telefono' => $tel, 'mensaje' => $msj, 'ip' => '127.0.0.1']);
        if ($estado !== 'recibida') {
            $pom->cambiarEstado($pid, $estado);
        }
        if ($conCita) {
            $pom->agendarCita($pid, date('Y-m-d H:i:s', strtotime('+3 days 11:00')));
        }
    }
    echo count($vacantes) . " vacantes y " . count($postulaciones) . " postulaciones demo listas.\n";
}

echo "\nDemo seed completado.\n";
