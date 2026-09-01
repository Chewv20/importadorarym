<?php

namespace App\Core;

/**
 * Minificador de CSS conservador.
 *
 * Quita comentarios y espacio sobrante sin reordenar ni reescribir
 * declaraciones: no intenta ser el más agresivo, sino el que nunca cambia el
 * resultado renderizado.
 *
 * Lo delicado es no tocar el interior de las cadenas ni de `url()`: ahí un
 * `;` o una `,` son datos, no estructura (por ejemplo en un data URI
 * `url(data:image/svg+xml;base64,...)`). Esos tramos se apartan antes de la
 * limpieza estructural y se reponen al final.
 *
 * Lo usa build/assets.php; vive aquí para que el autoloader lo alcance y las
 * pruebas puedan cubrirlo.
 */
class CssMin
{
    /** Marca interna para apartar tramos literales; \x00 no aparece en CSS. */
    private const MARCA = "\x00";

    public static function minificar(string $css): string
    {
        $literales = [];
        $salida = self::recorrer($css, $literales);

        // Limpieza estructural (ya sin literales que puedan confundirse).
        $salida = preg_replace('/\s*([{}:;,>~])\s*/', '$1', $salida) ?? $salida;
        $salida = str_replace([';}', ' !important'], ['}', '!important'], $salida);

        // Reponer los literales tal cual estaban.
        foreach ($literales as $i => $lit) {
            $salida = str_replace(self::MARCA . $i . self::MARCA, $lit, $salida);
        }

        return trim($salida);
    }

    /**
     * Recorre el CSS colapsando espacios y descartando comentarios, apartando
     * cadenas y url() en $literales.
     */
    private static function recorrer(string $css, array &$literales): string
    {
        $salida = '';
        $len = strlen($css);
        $i = 0;

        while ($i < $len) {
            $c = $css[$i];

            // Cadenas entre comillas: se apartan completas.
            if ($c === '"' || $c === "'") {
                $ini = $i;
                $comilla = $c;
                $i++;
                while ($i < $len) {
                    if ($css[$i] === '\\' && $i + 1 < $len) {
                        $i += 2;
                        continue;
                    }
                    if ($css[$i] === $comilla) {
                        $i++;
                        break;
                    }
                    $i++;
                }
                $salida .= self::apartar(substr($css, $ini, $i - $ini), $literales);
                continue;
            }

            // Comentarios /* ... */: se descartan (dejando un separador).
            if ($c === '/' && $i + 1 < $len && $css[$i + 1] === '*') {
                $fin = strpos($css, '*/', $i + 2);
                $i = $fin === false ? $len : $fin + 2;
                if ($salida !== '' && substr($salida, -1) !== ' ') {
                    $salida .= ' ';
                }
                continue;
            }

            // url(...) sin comillas: se aparta completo (puede llevar ; y ,).
            if (($c === 'u' || $c === 'U') && preg_match('/\Gurl\(\s*[^)"\']*\)/i', $css, $m, 0, $i)) {
                $salida .= self::apartar(preg_replace('/\s+/', '', $m[0]) ?? $m[0], $literales);
                $i += strlen($m[0]);
                continue;
            }

            // Espacio en blanco: se colapsa a uno solo.
            if (ctype_space($c)) {
                if ($salida !== '' && substr($salida, -1) !== ' ') {
                    $salida .= ' ';
                }
                $i++;
                continue;
            }

            $salida .= $c;
            $i++;
        }

        return $salida;
    }

    private static function apartar(string $literal, array &$literales): string
    {
        $literales[] = $literal;
        return self::MARCA . (count($literales) - 1) . self::MARCA;
    }
}
