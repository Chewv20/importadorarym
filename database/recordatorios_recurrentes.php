<?php

declare(strict_types=1);

/**
 * Envía los recordatorios de pedidos recurrentes que ya tocan (línea de comandos).
 * Uso:  php database/recordatorios_recurrentes.php
 *
 * Por cada plantilla activa cuyo `proximo_recordatorio_en` ya llegó:
 *   - envía el correo con los productos programados y un enlace al portal;
 *   - reprograma la siguiente fecha (proximo_recordatorio_en += frecuencia_dias).
 *
 * Idempotente dentro del mismo día: si se ejecuta dos veces, la segunda no
 * encuentra nada pendiente (la reprogramación ya movió la fecha al futuro).
 *
 * Cron sugerido (producción, diario, después del respaldo y el mantenimiento):
 *   0 7 * * * /usr/bin/php /ruta/al/proyecto/database/recordatorios_recurrentes.php >> /ruta/al/proyecto/storage/logs/recordatorios.log 2>&1
 */

require __DIR__ . '/_bootstrap.php';

use App\Core\Mailer;
use App\Models\PedidoRecurrente;

$inicio = date('Y-m-d H:i:s');
echo "[{$inicio}] Recordatorios de pedidos recurrentes iniciado.\n";

$model = new PedidoRecurrente();
$pendientes = $model->pendientesDeAviso();

$enviados  = 0;
$omitidos  = 0;

foreach ($pendientes as $p) {
    // Cuenta desactivada entre tanto: no se le notifica, pero SÍ se reprograma
    // (si no, seguiría intentando cada vez que corra el cron).
    if ((int) $p['cliente_activo'] !== 1) {
        $model->marcarAvisado((int) $p['id']);
        $omitidos++;
        continue;
    }

    $items = $model->items((int) $p['id']);
    if (!$items) {
        // Plantilla sin productos (los quitaron todos): se reprograma igual,
        // no tiene sentido reintentar cada día con la misma nada que ofrecer.
        $model->marcarAvisado((int) $p['id']);
        $omitidos++;
        continue;
    }

    $ok = Mailer::enviar($p['cliente_email'], 'Es hora de repetir tu pedido — Importadora RYM', 'pedido_recordatorio', [
        'nombre'          => $p['cliente_nombre'],
        'etiqueta'        => $p['nombre'] ?: ('tu pedido recurrente #' . $p['id']),
        'items'           => $items,
        'frecuenciaDias'  => (int) $p['frecuencia_dias'],
        'verUrl'          => rtrim((string) config('app.url'), '/') . '/portal/recurrentes/' . (int) $p['id'],
    ]);

    $model->marcarAvisado((int) $p['id']);
    $ok ? $enviados++ : $omitidos++;
}

echo "  · Pendientes encontrados: " . count($pendientes) . "\n";
echo "  · Correos enviados: {$enviados}\n";
echo "  · Omitidos (sin correo, pero reprogramados): {$omitidos}\n";
echo "[" . date('Y-m-d H:i:s') . "] Recordatorios completado.\n";
