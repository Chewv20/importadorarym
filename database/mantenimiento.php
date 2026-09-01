<?php

declare(strict_types=1);

/**
 * Mantenimiento periódico de la aplicación (línea de comandos).
 * Uso:  php database/mantenimiento.php [--dias-auditoria=180]
 *
 * Tareas idempotentes y seguras:
 *   - Poda la bitácora de auditoría más antigua que N días (por defecto 180).
 *   - Elimina los tokens de restablecimiento de contraseña ya caducados.
 *   - Recolecta los archivos de rate limiting (throttle) expirados.
 *   - Descarta la caché de datos del sitio (se regenera sola en la siguiente visita).
 *
 * Cron sugerido (producción, diario 3:30 am):
 *   30 3 * * * /usr/bin/php /ruta/al/proyecto/database/mantenimiento.php >> /ruta/al/proyecto/storage/logs/mantenimiento.log 2>&1
 */

require __DIR__ . '/_bootstrap.php';

use App\Core\Cache;
use App\Core\RateLimiter;
use App\Core\Upload;
use App\Models\Auditoria;
use App\Models\PasswordReset;
use App\Models\Visita;
use App\Models\Postulacion;

/* Parámetro opcional: días de retención de la auditoría. */
$diasAuditoria = 180;
foreach ($argv as $arg) {
    if (preg_match('/^--dias-auditoria=(\d+)$/', $arg, $m)) {
        $diasAuditoria = max(1, (int) $m[1]);
    }
}

$inicio = date('Y-m-d H:i:s');
echo "[{$inicio}] Mantenimiento iniciado.\n";

try {
    /* ---- Auditoría ------------------------------------------------------- */
    $borradosAuditoria = (new Auditoria())->purgar($diasAuditoria);
    echo "  · Auditoría: {$borradosAuditoria} registro(s) de más de {$diasAuditoria} días eliminados.\n";

    /* ---- Tokens de restablecimiento caducados ---------------------------- */
    $borradosResets = (new PasswordReset())->purgar();
    echo "  · Password resets: {$borradosResets} token(s) caducado(s) eliminados.\n";

    /* ---- Archivos de rate limiting expirados ----------------------------- */
    $borradosThrottle = RateLimiter::gc();
    echo "  · Throttle: {$borradosThrottle} archivo(s) de contador expirados eliminados.\n";

    /* ---- Libreta de visitas (datos personales; retención 1 año) ---------- */
    $borradosVisitas = (new Visita())->purgar(365);
    echo "  · Visitas: {$borradosVisitas} registro(s) de más de 365 días eliminados.\n";

    /* ---- Bolsa de trabajo (CVs/datos personales; retención 1 año) -------- */
    $cvsBorrados = (new Postulacion())->purgar(365);
    foreach ($cvsBorrados as $cv) {
        Upload::borrarDocumento('cvs', $cv);
    }
    echo "  · Postulaciones: " . count($cvsBorrados) . " CV(s) y registro(s) de más de 365 días eliminados.\n";

    /* ---- Caché de datos del sitio ---------------------------------------- */
    // El panel la invalida al guardar; esto solo garantiza que una entrada
    // huérfana (p. ej. tras editar la base a mano) no sobreviva indefinidamente.
    $cacheBorrada = Cache::limpiar();
    echo "  · Caché: {$cacheBorrada} entrada(s) de datos del sitio descartadas.\n";

    echo "[" . date('Y-m-d H:i:s') . "] Mantenimiento completado.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Error en el mantenimiento: " . $e->getMessage() . "\n");
    exit(1);
}
