<?php

namespace App\Core;

/**
 * Rotación de los archivos de log de la aplicación.
 *
 * Sin esto, storage/logs/php-error.log crece sin límite: un error recurrente
 * en producción lo lleva a cientos de MB en días y arrastra consigo al visor
 * del panel y al espacio en disco.
 *
 * Se conserva UNA generación anterior (.1); la más vieja se descarta.
 */
class Log
{
    /** Tamaño a partir del cual se rota un log. */
    private const MAX_BYTES = 5242880; // 5 MB

    /**
     * Rota el archivo si excede el máximo. Silencioso: un fallo al rotar nunca
     * debe interrumpir la petición del usuario.
     */
    public static function rotar(string $ruta, int $maxBytes = self::MAX_BYTES): bool
    {
        // El log lo escribe PHP por su cuenta durante la petición: sin limpiar la
        // caché de stat, filesize() puede devolver un tamaño ya obsoleto.
        clearstatcache(true, $ruta);

        if (!is_file($ruta) || (int) @filesize($ruta) <= $maxBytes) {
            return false;
        }
        $previo = $ruta . '.1';
        if (is_file($previo)) {
            @unlink($previo);
        }
        return @rename($ruta, $previo);
    }

    /**
     * Rota los logs de la aplicación. Pensado para el arranque: se llama con
     * muestreo (ver public/index.php) para no pagar un stat en cada petición.
     */
    public static function mantenimiento(): void
    {
        foreach (['php-error.log', 'mail.log'] as $nombre) {
            self::rotar(ROOT_PATH . '/storage/logs/' . $nombre);
        }
    }
}
