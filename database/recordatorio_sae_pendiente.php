<?php

declare(strict_types=1);

/**
 * Avisa al equipo interno de partidas que llevan pendientes de exportar a SAE
 * (recordatorio manual capturado desde el detalle del pedido tras dejar
 * partidas sin exportar). Uso:  php database/recordatorio_sae_pendiente.php
 *
 * Idempotente dentro del mismo día: solo limpia el recordatorio de los pedidos
 * cuyo aviso SÍ se envió — si el correo falla (SMTP caído) o no hay
 * MAIL_LEADS configurado, el recordatorio se deja intacto para que el cron
 * lo reintente al día siguiente en vez de perder el aviso en silencio.
 *
 * Cron sugerido (producción, diario):
 *   0 8 * * * /usr/bin/php /ruta/al/proyecto/database/recordatorio_sae_pendiente.php >> /ruta/al/proyecto/storage/logs/recordatorios.log 2>&1
 */

require __DIR__ . '/_bootstrap.php';

use App\Core\Mailer;
use App\Models\Pedido;

$inicio = date('Y-m-d H:i:s');
echo "[{$inicio}] Recordatorio de partidas pendientes de SAE iniciado.\n";

$model = new Pedido();
$pendientes = $model->pedidosConRecordatorioVencido();

$leads = config('app.mail.leads');
$enviado = false;

if ($pendientes && $leads) {
    $enviado = Mailer::enviar($leads, 'Partidas pendientes de exportar a SAE — Importadora RYM', 'sae_pendiente', [
        'pedidos' => $pendientes,
    ]);
}

if ($enviado) {
    foreach ($pendientes as $p) {
        $model->limpiarRecordatorio((int) $p['id']);
    }
} elseif ($pendientes && !$leads) {
    echo "  · AVISO: hay pedidos pendientes pero MAIL_LEADS no está configurado; recordatorios sin limpiar.\n";
} elseif ($pendientes) {
    echo "  · AVISO: el correo no se pudo enviar; recordatorios sin limpiar, se reintentará mañana.\n";
}

echo "  · Pedidos con recordatorio vencido: " . count($pendientes) . "\n";
echo "  · Correo enviado: " . ($enviado ? 'sí' : 'no') . "\n";
echo "[" . date('Y-m-d H:i:s') . "] Recordatorio completado.\n";
