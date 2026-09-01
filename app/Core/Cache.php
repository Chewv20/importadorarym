<?php

namespace App\Core;

/**
 * Caché de datos en archivo, sin dependencias.
 *
 * Pensada para lo que se consulta en CADA página del sitio público y casi nunca
 * cambia (el modal promocional y los logos de clientes). El valor se guarda como
 * un `return` de PHP, así que OPcache lo mantiene en memoria y leerlo no toca
 * disco en caliente.
 *
 * La invalidación es explícita: quien escribe el dato llama a `olvidar()`. El TTL
 * es solo una red de seguridad por si alguien edita la base a mano.
 *
 * Autocurativa: si un archivo de caché está corrupto o incompleto se trata como
 * fallo de lectura y el valor se regenera.
 */
class Cache
{
    private static function dir(): string
    {
        $dir = ROOT_PATH . '/storage/cache/data';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    /** Nombre de archivo legible (para poder inspeccionar la carpeta). */
    private static function archivo(string $clave): string
    {
        $slug = preg_replace('/[^a-z0-9._-]+/i', '_', $clave) ?? 'cache';
        return self::dir() . '/' . $slug . '.php';
    }

    /**
     * Devuelve el valor cacheado; si no hay, ejecuta $productor, guarda su
     * resultado y lo devuelve.
     *
     * @param int|null $ttl Segundos de vigencia; null = hasta que se invalide.
     */
    public static function remember(string $clave, callable $productor, ?int $ttl = null)
    {
        $hit = self::get($clave);
        if ($hit !== null) {
            return $hit['valor'];
        }
        $valor = $productor();
        self::put($clave, $valor, $ttl);
        return $valor;
    }

    /**
     * Lee una entrada válida. Devuelve null si no existe o expiró, o
     * ['valor' => mixed] si hay acierto (así un valor null cacheado se
     * distingue de la ausencia de caché).
     */
    public static function get(string $clave): ?array
    {
        $file = self::archivo($clave);
        if (!is_file($file)) {
            return null;
        }
        try {
            $datos = @include $file;
        } catch (\Throwable $e) {
            return null; // archivo corrupto: se regenera
        }
        if (!is_array($datos) || !array_key_exists('valor', $datos)) {
            return null;
        }
        $expira = $datos['expira'] ?? null;
        if ($expira !== null && (int) $expira <= time()) {
            return null;
        }
        return ['valor' => $datos['valor']];
    }

    /** Guarda un valor. Silenciosa: un fallo de escritura no rompe la petición. */
    public static function put(string $clave, $valor, ?int $ttl = null): void
    {
        $contenido = '<?php return ' . var_export([
            'expira' => $ttl !== null ? time() + $ttl : null,
            'valor'  => $valor,
        ], true) . ';';

        $file = self::archivo($clave);
        // Escritura atómica: nadie debe poder incluir un archivo a medio escribir.
        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, $contenido, LOCK_EX) === false) {
            return;
        }
        if (!@rename($tmp, $file)) {
            @unlink($file);
            if (!@rename($tmp, $file)) {
                @unlink($tmp);
                return;
            }
        }
        self::invalidarOpcache($file);
    }

    /** Invalida una entrada. Llamar al guardar el dato de origen. */
    public static function olvidar(string $clave): void
    {
        $file = self::archivo($clave);
        if (is_file($file)) {
            @unlink($file);
            self::invalidarOpcache($file);
        }
    }

    /** Vacía toda la caché de datos. Devuelve cuántas entradas borró. */
    public static function limpiar(): int
    {
        $n = 0;
        foreach (glob(self::dir() . '/*.php') ?: [] as $file) {
            if (@unlink($file)) {
                self::invalidarOpcache($file);
                $n++;
            }
        }
        return $n;
    }

    /** Sin esto, OPcache seguiría sirviendo la versión anterior del archivo. */
    private static function invalidarOpcache(string $file): void
    {
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($file, true);
        }
    }
}
