<?php

namespace App\Core;

/**
 * Limitador de peticiones simple basado en archivos (sin dependencias).
 * Útil para frenar fuerza bruta y spam en formularios públicos.
 */
class RateLimiter
{
    private static function dir(): string
    {
        $dir = ROOT_PATH . '/storage/cache/throttle';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private static function file(string $key): string
    {
        return self::dir() . '/' . sha1($key) . '.json';
    }

    /**
     * Registra un intento para $key. Devuelve true si AÚN está permitido
     * (no ha superado $max en la ventana de $window segundos).
     */
    public static function attempt(string $key, int $max, int $window): bool
    {
        $file = self::file($key);
        $now  = time();
        $data = ['count' => 0, 'reset' => $now + $window];

        if (is_file($file)) {
            $raw = json_decode((string) @file_get_contents($file), true);
            if (is_array($raw) && (int) ($raw['reset'] ?? 0) > $now) {
                $data = $raw;
            }
        }

        $data['count'] = (int) ($data['count'] ?? 0) + 1;
        @file_put_contents($file, json_encode($data), LOCK_EX);

        return $data['count'] <= $max;
    }

    /** Segundos restantes hasta que se reinicie el contador de $key. */
    public static function retryAfter(string $key): int
    {
        $file = self::file($key);
        if (is_file($file)) {
            $raw = json_decode((string) @file_get_contents($file), true);
            if (is_array($raw)) {
                return max(0, (int) ($raw['reset'] ?? 0) - time());
            }
        }
        return 0;
    }

    /** Limpia el contador (p. ej. tras un login exitoso). */
    public static function clear(string $key): void
    {
        @unlink(self::file($key));
    }

    /**
     * Recolector de basura: borra los archivos de contador ya expirados.
     * Para invocar desde el cron de mantenimiento (evita que la carpeta de
     * throttle crezca sin límite). Devuelve cuántos archivos eliminó.
     */
    public static function gc(): int
    {
        $now = time();
        $borrados = 0;
        foreach (glob(self::dir() . '/*.json') ?: [] as $file) {
            $raw = json_decode((string) @file_get_contents($file), true);
            // Sin fecha de reinicio válida o ya vencido: se elimina.
            if (!is_array($raw) || (int) ($raw['reset'] ?? 0) <= $now) {
                if (@unlink($file)) {
                    $borrados++;
                }
            }
        }
        return $borrados;
    }
}
