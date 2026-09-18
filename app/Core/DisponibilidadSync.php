<?php

namespace App\Core;

use App\Models\Producto;

/**
 * Sincroniza la existencia de productos desde Aspel SAE (tabla INVE03 de
 * Firebird, columnas CVE_ART/EXIST) — ver App\Controllers\Integraciones\
 * SaeDisponibilidadController, que recibe el POST del script externo.
 *
 * Clase hermana de CatalogoImport, no una extensión: a diferencia de esa
 * clase (reescribe el producto completo desde un CSV), esta SOLO toca
 * existencia_sae — nunca crea productos ni toca el resto de sus campos, así
 * que un artículo de SAE que no está en el catálogo del sitio simplemente se
 * cuenta como "no encontrado", no es un error.
 */
class DisponibilidadSync
{
    /** Mismo tope que CatalogoImport y misma razón: no agotar el tiempo de ejecución. */
    public const MAX_FILAS = 5000;

    /**
     * Valida y normaliza una fila entrante. Pura, sin BD.
     * @return array{clave_sae:string,existencia:int}|null null si la fila no es válida.
     */
    public static function filaValida($fila): ?array
    {
        if (!is_array($fila)) {
            return null;
        }
        $clave = trim((string) ($fila['clave_sae'] ?? ''));
        if ($clave === '' || !is_numeric($fila['existencia'] ?? null)) {
            return null;
        }
        return ['clave_sae' => $clave, 'existencia' => (int) $fila['existencia']];
    }

    /**
     * @param array $filas Filas ya decodificadas del JSON entrante.
     * @return array{actualizados:int, no_encontrados:int, omitidos:int, errores:string[]}
     */
    public static function procesar(array $filas): array
    {
        $r = ['actualizados' => 0, 'no_encontrados' => 0, 'omitidos' => 0, 'errores' => []];

        $productoModel = new Producto();
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $i = 0;
            foreach ($filas as $fila) {
                $i++;
                if ($i > self::MAX_FILAS) {
                    $r['errores'][] = 'Se alcanzó el máximo de ' . self::MAX_FILAS . ' filas; el resto no se procesó.';
                    break;
                }
                $valida = self::filaValida($fila);
                if ($valida === null) {
                    $r['omitidos']++;
                    continue;
                }
                $existente = $productoModel->porClaveSae($valida['clave_sae']);
                if (!$existente) {
                    $r['no_encontrados']++;
                    continue;
                }
                $productoModel->actualizarExistencia((int) $existente['id'], $valida['existencia']);
                $r['actualizados']++;
            }
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('DisponibilidadSync: ' . $e->getMessage());
            return ['actualizados' => 0, 'no_encontrados' => 0, 'omitidos' => 0,
                    'errores' => ['La sincronización falló y no se guardó ningún cambio: ' . $e->getMessage()]];
        }

        $db->commit();
        return $r;
    }
}
