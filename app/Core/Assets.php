<?php

namespace App\Core;

/**
 * Hojas de estilo por sección del sitio.
 *
 * Una sola definición de qué CSS compone cada pantalla, usada por el script de
 * empaquetado (build/assets.php) y por las vistas. Así no puede desincronizarse
 * lo que se empaqueta de lo que se sirve.
 *
 * En producción se emite un único archivo minificado; en desarrollo, los
 * archivos sueltos, para no tener que reconstruir tras cada cambio.
 */
class Assets
{
    /**
     * Bundle => hojas que lo componen, EN ORDEN DE CASCADA (el orden importa:
     * las últimas sobrescriben a las primeras).
     */
    public const BUNDLES = [
        'site'   => ['fonts.css', 'brand.css', 'app.css', 'components.css', 'site.css'],
        'portal' => ['fonts.css', 'brand.css', 'app.css', 'components.css', 'portal.css'],
        'admin'  => ['fonts.css', 'brand.css', 'app.css', 'components.css', 'admin.css'],
        'kiosco'  => ['fonts.css', 'brand.css', 'app.css', 'components.css', 'kiosco.css'],
        'reparto' => ['fonts.css', 'brand.css', 'app.css', 'components.css', 'reparto.css'],
    ];

    /** Nombre del archivo empaquetado de un bundle. */
    public static function archivoMin(string $nombre): string
    {
        return $nombre . '.bundle.min.css';
    }

    /**
     * Etiquetas <link> del bundle indicado.
     *
     * En producción (app.debug=false) sirve el empaquetado si existe, punto —
     * ya NO compara su fecha de modificación contra la de sus fuentes. Esa
     * comparación (histórica) buscaba evitar servir un bundle desactualizado,
     * pero resultó frágil ante despliegues por FTP: el cliente FTP puede subir
     * los archivos en cualquier orden, y si una hoja fuente terminaba con una
     * fecha más reciente que el bundle (por segundos, o por orden alfabético
     * de subida) esto forzaba las 5 hojas sueltas en vez de 1 archivo — pasó
     * en el primer despliegue real a producción (01/09/2026). El proceso
     * correcto sigue siendo: editar fuente -> `php build/assets.php` -> subir
     * fuente y bundle juntos; confiar en eso es más simple y más robusto ante
     * el orden real de subida que comparar mtimes entre archivos hermanos.
     */
    public static function css(string $nombre): string
    {
        $partes = self::BUNDLES[$nombre] ?? [];
        if (!$partes) {
            return '';
        }

        $min = PUBLIC_PATH . '/assets/css/' . self::archivoMin($nombre);
        if (!config('app.debug') && is_file($min)) {
            return '<link rel="stylesheet" href="' . e(asset('assets/css/' . self::archivoMin($nombre))) . '">';
        }

        $html = '';
        foreach ($partes as $parte) {
            $html .= '<link rel="stylesheet" href="' . e(asset('assets/css/' . $parte)) . '">' . "\n    ";
        }
        return rtrim($html);
    }
}
