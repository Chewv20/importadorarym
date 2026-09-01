<?php

namespace App\Core;

use App\Models\Producto;
use App\Models\Categoria;

/**
 * Importa un catálogo de productos desde un archivo CSV.
 * Reutilizable por el comando CLI (database/import_catalogo.php) y por el panel.
 *
 * - Upsert por SKU (si viene) o por clave SAE; no duplica.
 * - Crea las categorías y subcategorías que no existan (por nombre).
 * - Las imágenes se cargan aparte desde el panel.
 *
 * Columnas (encabezados en la 1ª fila, orden libre; delimitador , o ;):
 *   nombre, categoria, subcategoria, sku, clave_sae, esquema_impuestos, unidad,
 *   descripcion, precio, destacado, personalizable, activo, orden
 * Solo "nombre" es obligatorio.
 *
 * "subcategoria" requiere "categoria" en la misma fila (jerarquía de 2 niveles:
 * ver App\Models\Categoria). Si el nombre de la subcategoría ya existe pero bajo
 * OTRO padre, se crea una nueva (dos categorías distintas pueden llamarse igual,
 * p. ej. "Cubiertos" bajo "Biodegradables" y bajo "Bambú").
 */
class CatalogoImport
{
    /**
     * Tope de filas por importación. Sin él, un CSV de 2 MB (decenas de miles de
     * filas × varias consultas cada una) agota el tiempo de ejecución a mitad
     * del proceso y deja el catálogo a medio actualizar.
     */
    public const MAX_FILAS = 5000;

    /**
     * @return array{creados:int, actualizados:int, omitidos:int, categorias:int, errores:string[], error:?string}
     */
    public static function procesar(string $rutaCsv, bool $dryRun = false): array
    {
        $r = ['creados' => 0, 'actualizados' => 0, 'omitidos' => 0, 'categorias' => 0, 'errores' => [], 'error' => null];

        if (!is_file($rutaCsv)) {
            $r['error'] = 'No se encontró el archivo.';
            return $r;
        }
        $fh = fopen($rutaCsv, 'r');
        if (!$fh) {
            $r['error'] = 'No se pudo abrir el archivo.';
            return $r;
        }

        // Delimitador por la primera línea.
        $primera = fgets($fh);
        $primera = $primera === false ? '' : ltrim($primera, "\xEF\xBB\xBF");
        $delim = (substr_count($primera, ';') > substr_count($primera, ',')) ? ';' : ',';
        rewind($fh);

        $headers = fgetcsv($fh, 0, $delim);
        if (!$headers) {
            fclose($fh);
            $r['error'] = 'El archivo no tiene encabezados.';
            return $r;
        }
        $headers[0] = ltrim((string) $headers[0], "\xEF\xBB\xBF");
        $norm = array_map([self::class, 'normalizarEncabezado'], $headers);

        if (!in_array('nombre', $norm, true)) {
            fclose($fh);
            $r['error'] = "Falta la columna obligatoria 'nombre'.";
            return $r;
        }

        // ¿El archivo trae la columna del esquema de impuestos? Si no, los
        // productos existentes conservan el que ya tuvieran (ver más abajo).
        $traeEsquema = in_array('esquema_impuestos', $norm, true);

        $productoModel  = new Producto();
        $categoriaModel = new Categoria();

        // Mapa de categorías RAÍZ por nombre, y de subcategorías por
        // "id_del_padre||nombre" (así dos subcategorías con el mismo nombre
        // bajo padres distintos no colisionan, ej. "Cubiertos").
        $catMap = [];
        $subMap = [];
        foreach ($categoriaModel->todas() as $c) {
            if ($c['nivel'] === 0) {
                $catMap[mb_strtolower(trim($c['nombre']))] = (int) $c['id'];
            } else {
                $subMap[$c['categoria_padre_id'] . '||' . mb_strtolower(trim($c['nombre']))] = (int) $c['id'];
            }
        }

        // Todo el lote se aplica o no se aplica nada: si el proceso muere a la
        // mitad, el catálogo no queda en un estado intermedio.
        $db = \App\Core\Database::connection();
        if (!$dryRun) {
            $db->beginTransaction();
        }

        try {
            $fila = 1;
            $procesadas = 0;
            while (($cols = fgetcsv($fh, 0, $delim)) !== false) {
                $fila++;
                if ($procesadas >= self::MAX_FILAS) {
                    $r['errores'][] = 'Se alcanzó el máximo de ' . self::MAX_FILAS
                        . ' filas por importación; el resto del archivo no se procesó. Divídelo en partes.';
                    break;
                }
                $procesadas++;
                if (count(array_filter($cols, static fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }

                $row = self::rowAssoc($norm, $cols);
                $nombre = trim((string) ($row['nombre'] ?? ''));
                if ($nombre === '') {
                    $r['errores'][] = "Fila {$fila}: sin nombre, se omite.";
                    $r['omitidos']++;
                    continue;
                }

                // Categoría y subcategoría (buscar o crear).
                $categoriaId = null;
                $catNombre = trim((string) ($row['categoria'] ?? ''));
                $subNombre = trim((string) ($row['subcategoria'] ?? ''));

                if ($subNombre !== '' && $catNombre === '') {
                    $r['errores'][] = "Fila {$fila}: «{$subNombre}» necesita una categoría en la columna 'categoria'; el producto se importó sin categoría.";
                    $subNombre = '';
                }

                if ($catNombre !== '') {
                    $key = mb_strtolower($catNombre);
                    if (!isset($catMap[$key])) {
                        // En dry-run se usa un sentinel (string) en vez del id real: no
                        // se persiste nada, pero evita recontar la misma categoría nueva
                        // si aparece en varias filas del mismo archivo.
                        $catMap[$key] = $dryRun
                            ? 'nueva:' . $key
                            : $categoriaModel->crear(['nombre' => $catNombre, 'slug' => '']);
                        $r['categorias']++;
                    }
                    $padreRef = $catMap[$key];
                    $categoriaId = is_int($padreRef) ? $padreRef : null;

                    if ($subNombre !== '') {
                        $subKey = $padreRef . '||' . mb_strtolower($subNombre);
                        if (!isset($subMap[$subKey])) {
                            $subMap[$subKey] = $dryRun
                                ? 'nueva:' . $subKey
                                : $categoriaModel->crear([
                                    'nombre' => $subNombre, 'slug' => '', 'categoria_padre_id' => $padreRef,
                                ]);
                            $r['categorias']++;
                        }
                        $hijoRef = $subMap[$subKey];
                        $categoriaId = is_int($hijoRef) ? $hijoRef : null;
                    }
                }

                $sku      = trim((string) ($row['sku'] ?? ''));
                $claveSae = trim((string) ($row['clave_sae'] ?? ''));
                $precio   = self::parsePrecio($row['precio'] ?? '');

                $data = [
                    'categoria_id' => $categoriaId,
                    'nombre'       => mb_substr($nombre, 0, 150),
                    'slug'         => '',
                    'descripcion'  => trim((string) ($row['descripcion'] ?? '')) ?: null,
                    'sku'          => $sku ?: null,
                    'clave_sae'    => $claveSae ?: null,
                    'esquema_impuestos' => Producto::esquemaImpuestos($row['esquema_impuestos'] ?? null),
                    'unidad'       => trim((string) ($row['unidad'] ?? '')) ?: null,
                    'destacado'    => self::parseBool($row['destacado'] ?? '', false),
                    'personalizable' => self::parseBool($row['personalizable'] ?? '', false),
                    'activo'       => self::parseBool($row['activo'] ?? '', true),
                    'orden'        => (int) ($row['orden'] ?? 0),
                ];

                $existente = null;
                if ($sku !== '') {
                    $existente = $productoModel->porSku($sku);
                }
                if (!$existente && $claveSae !== '') {
                    $existente = $productoModel->porClaveSae($claveSae);
                }

                if ($dryRun) {
                    $existente ? $r['actualizados']++ : $r['creados']++;
                    continue;
                }

                // Si el archivo NO trae la columna del esquema de impuestos (los
                // CSV anteriores a que existiera el campo), se conserva el valor
                // ya capturado en el panel en vez de borrarlo.
                if (!$traeEsquema && $existente) {
                    $data['esquema_impuestos'] = $existente['esquema_impuestos'] ?? null;
                }

                if ($existente) {
                    $id = (int) $existente['id'];
                    $productoModel->actualizar($id, $data);
                    $r['actualizados']++;
                } else {
                    $id = $productoModel->crear($data);
                    $r['creados']++;
                }
                if ($precio !== null) {
                    $productoModel->setPrecio($id, $precio);
                }
            }
        } catch (\Throwable $e) {
            if (!$dryRun && $db->inTransaction()) {
                $db->rollBack();
            }
            fclose($fh);
            error_log('CatalogoImport: ' . $e->getMessage());
            return ['creados' => 0, 'actualizados' => 0, 'omitidos' => 0, 'categorias' => 0,
                    'errores' => [], 'error' => 'La importación falló y no se guardó ningún cambio: ' . $e->getMessage()];
        }

        if (!$dryRun && $db->inTransaction()) {
            $db->commit();
        }
        fclose($fh);

        return $r;
    }

    /* ------------------------------------------------ Utilidades ------ */

    private static function normalizarEncabezado(string $h): string
    {
        $h = trim($h);
        if (function_exists('iconv')) {
            $conv = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $h);
            if ($conv !== false) {
                $h = $conv;
            }
        }
        $h = strtolower($h);
        $h = preg_replace('/[^a-z0-9]+/', '_', $h) ?? $h;
        $h = trim($h, '_');
        $map = [
            'presentacion'       => 'unidad',
            'clave'              => 'clave_sae',
            'clave_del_articulo' => 'clave_sae',
            'codigo'             => 'sku',
            // Subcategoría: requiere la columna 'categoria' en la misma fila.
            'sub_categoria'      => 'subcategoria',
            'subcategoria'       => 'subcategoria',
            // Esquema de impuestos: se admiten las formas con que suele venir
            // exportado desde SAE.
            'esquema'                        => 'esquema_impuestos',
            'esquema_de_impuestos'           => 'esquema_impuestos',
            'clave_de_esquema_de_impuestos'  => 'esquema_impuestos',
            'esquema_impuesto'               => 'esquema_impuestos',
            'impuestos'                      => 'esquema_impuestos',
        ];
        return $map[$h] ?? $h;
    }

    private static function rowAssoc(array $headers, array $cols): array
    {
        $out = [];
        foreach ($headers as $i => $h) {
            $out[$h] = $cols[$i] ?? '';
        }
        return $out;
    }

    private static function parseBool($v, bool $default): int
    {
        $v = mb_strtolower(trim((string) $v));
        if ($v === '') {
            return $default ? 1 : 0;
        }
        return in_array($v, ['1', 'si', 'sí', 'yes', 'y', 'true', 'x', 'verdadero'], true) ? 1 : 0;
    }

    private static function parsePrecio($v): ?float
    {
        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }
        $v = str_replace([',', '$', ' '], ['.', '', ''], $v);
        return is_numeric($v) ? (float) $v : null;
    }
}
