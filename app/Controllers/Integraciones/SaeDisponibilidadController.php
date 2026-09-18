<?php

namespace App\Controllers\Integraciones;

use App\Core\Controller;
use App\Core\DisponibilidadSync;
use App\Core\RateLimiter;

/**
 * Recibe la sincronización de existencias desde el script de la oficina
 * (Firebird/Aspel SAE, tabla INVE03) — ver integraciones/sae/README.md.
 *
 * Primer endpoint del proyecto con autenticación máquina-a-máquina (token
 * fijo por header Bearer, no sesión/CSRF): documentado como precedente en
 * docs/SEGURIDAD.md.
 */
class SaeDisponibilidadController extends Controller
{
    public function sincronizar(): void
    {
        // Antes de validar el token: así un token equivocado repetido (típico de
        // un script mal configurado, o alguien probando suerte) también queda
        // acotado, no solo el abuso con un token válido filtrado.
        if (!RateLimiter::attempt('sync-disponibilidad:' . client_ip(), 20, 600)) {
            $this->json(['ok' => false, 'error' => 'Demasiadas peticiones.'], 429);
        }

        $token = (string) config('app.integraciones.sae_sync_token', '');
        $recibido = self::tokenDeCabecera();

        // Vacío = sin configurar: el endpoint queda cerrado por defecto, no
        // "abierto sin auth" — mismo criterio seguro-por-defecto que TRUSTED_PROXY_CIDR.
        if ($token === '' || $recibido === null || !hash_equals($token, $recibido)) {
            $this->json(['ok' => false, 'error' => 'No autorizado.'], 401);
        }

        $payload = json_decode((string) file_get_contents('php://input'), true);
        $filas = is_array($payload['filas'] ?? null) ? $payload['filas'] : null;
        if ($filas === null) {
            $this->json(['ok' => false, 'error' => 'Cuerpo inválido: se espera {"filas": [...]}.'], 422);
        }

        $resumen = DisponibilidadSync::procesar($filas);
        self::log($resumen);

        $this->json(['ok' => true] + $resumen);
    }

    private static function tokenDeCabecera(): ?string
    {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if ($auth === '' && function_exists('apache_request_headers')) {
            // Algunos SAPI (mod_php/CGI según configuración) no exponen el
            // header Authorization en $_SERVER; con esto se cubre ese caso.
            $auth = apache_request_headers()['Authorization'] ?? '';
        }
        return str_starts_with($auth, 'Bearer ') ? substr($auth, 7) : null;
    }

    private static function log(array $resumen): void
    {
        $linea = '[' . date('Y-m-d H:i:s') . '] ' . json_encode($resumen, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        @file_put_contents(ROOT_PATH . '/storage/logs/sync-disponibilidad.log', $linea, FILE_APPEND);
    }
}
