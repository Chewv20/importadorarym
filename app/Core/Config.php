<?php

namespace App\Core;

/**
 * Cargador de configuración. Cada archivo de /config se expone por su nombre,
 * con acceso por notación de punto: config('app.name'), config('database.host').
 */
class Config
{
    protected static array $items = [];

    public static function load(string $dir): void
    {
        foreach (glob($dir . '/*.php') as $file) {
            $name = basename($file, '.php');
            static::$items[$name] = require $file;
        }
    }

    public static function get(string $key, $default = null)
    {
        $value = static::$items;

        foreach (explode('.', $key) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }
}
