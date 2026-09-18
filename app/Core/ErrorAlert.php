<?php

namespace App\Core;

/**
 * Aviso por correo cuando el sitio genera un error 500 en producción, para no
 * depender de que alguien revise storage/logs/php-error.log manualmente.
 *
 * Desactivado por defecto (MAIL_ERRORES vacío). Con throttle por firma de
 * error (mismo archivo+línea+mensaje): un error recurrente manda UN correo
 * cada 30 min, no uno por petición — si un error revienta en cada visita,
 * no debe inundar el buzón ni sumar latencia de correo a cada respuesta 500.
 */
class ErrorAlert
{
    private const VENTANA_SEGS = 1800; // 30 min por firma de error

    /**
     * Datos normalizados del error para el correo/throttle — sin efectos
     * secundarios, fácil de cubrir con pruebas puras.
     */
    public static function datosDe(\Throwable $e): array
    {
        $trace = $e->getTraceAsString();
        return [
            'clase'   => get_class($e),
            'mensaje' => $e->getMessage(),
            'archivo' => $e->getFile(),
            'linea'   => $e->getLine(),
            // Un stack trace completo puede pesar varios KB; de sobra para
            // ubicar la causa sin volver el correo inmanejable.
            'trace'   => mb_substr($trace, 0, 3000),
        ];
    }

    /** Firma estable del error, usada como clave de throttle. */
    public static function firma(array $datos): string
    {
        return sha1($datos['clase'] . ':' . $datos['archivo'] . ':' . $datos['linea'] . ':' . $datos['mensaje']);
    }

    /**
     * Envía el aviso si hay buzón configurado y esta firma de error no se
     * avisó ya en los últimos 30 min. Nunca lanza: un fallo aquí no debe
     * impedir que se muestre la página 500 al visitante.
     */
    public static function notificar(\Throwable $e): void
    {
        $destino = (string) config('app.mail.errores', '');
        if ($destino === '') {
            return;
        }

        try {
            $datos = self::datosDe($e);
            if (!RateLimiter::attempt('error-alert:' . self::firma($datos), 1, self::VENTANA_SEGS)) {
                return;
            }

            Mailer::enviar($destino, 'Error 500 en el sitio — ' . config('app.name', 'Importadora RYM'), 'error_500', [
                'mensaje' => $datos['mensaje'],
                'archivo' => $datos['archivo'],
                'linea'   => $datos['linea'],
                'trace'   => $datos['trace'],
                'url'     => ($_SERVER['REQUEST_METHOD'] ?? '') . ' ' . ($_SERVER['REQUEST_URI'] ?? ''),
                'ip'      => function_exists('client_ip') ? client_ip() : ($_SERVER['REMOTE_ADDR'] ?? ''),
                'cuando'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $ignorado) {
            // Ya quedó registrado en php-error.log por el propio manejador de
            // errores; no hay nada más seguro que hacer con este fallo aquí.
        }
    }
}
