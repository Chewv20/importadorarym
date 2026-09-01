<?php

namespace App\Core;

/**
 * Cargador mínimo de variables de entorno desde un archivo .env.
 * Sin dependencias externas (PHP puro).
 */
class Env
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignorar comentarios y líneas sin asignación.
            // strpos() en vez de str_contains() (PHP 8.0+): este archivo es de los
            // primeros en ejecutarse en public/index.php, antes de la redirección a
            // HTTPS — debe poder correr incluso si algún vhost del hosting quedó en
            // una versión de PHP más vieja que la del resto del sitio.
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            // Quitar comillas envolventes (" o ').
            if (strlen($value) >= 2) {
                $quote = $value[0];
                if (($quote === '"' || $quote === "'") && substr($value, -1) === $quote) {
                    $value = substr($value, 1, -1);
                }
            }

            if ($key === '') {
                continue;
            }

            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}
