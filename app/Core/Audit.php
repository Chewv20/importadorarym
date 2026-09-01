<?php

namespace App\Core;

use App\Models\Auditoria;

/**
 * Registro de auditoría. Captura contexto (usuario actual, IP, navegador) y
 * lo guarda en la tabla `auditoria`. A prueba de fallos: nunca interrumpe el
 * flujo del usuario si el registro falla.
 */
class Audit
{
    /** Registro genérico. */
    public static function log(string $tipo, string $accion, array $o = []): void
    {
        try {
            $u = Auth::check() ? Auth::user() : null;
            (new Auditoria())->registrar([
                'usuario_id'    => $o['usuario_id']    ?? ($u['id'] ?? null),
                'usuario_email' => $o['usuario_email'] ?? ($u['email'] ?? null),
                'tipo'          => $tipo,
                'accion'        => $accion,
                'entidad'       => $o['entidad']     ?? null,
                'entidad_id'    => $o['entidad_id']  ?? null,
                'descripcion'   => $o['descripcion'] ?? null,
                'ip'            => client_ip(),
                'user_agent'    => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
        } catch (\Throwable $e) {
            error_log('Audit fail: ' . $e->getMessage());
        }
    }

    /** Ingreso al sistema (login/login_fallido/logout). */
    public static function ingreso(string $accion, ?int $usuarioId, string $email, string $descripcion = ''): void
    {
        self::log('ingreso', $accion, [
            'usuario_id'    => $usuarioId,
            'usuario_email' => $email,
            'descripcion'   => $descripcion,
        ]);
    }

    /** Cambio en el panel (crear/actualizar/eliminar/aprobar...). */
    public static function cambio(string $accion, string $entidad, ?int $entidadId, string $descripcion = ''): void
    {
        self::log('cambio', $accion, [
            'entidad'     => $entidad,
            'entidad_id'  => $entidadId,
            'descripcion' => $descripcion,
        ]);
    }
}
