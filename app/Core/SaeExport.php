<?php

namespace App\Core;

/**
 * Construye el archivo de exportación de pedidos con el layout del importador
 * de Aspel SAE. Una fila por partida (artículo); las columnas de encabezado
 * se repiten en cada partida y "Clave" agrupa las partidas del mismo pedido.
 *
 * El orden de columnas debe coincidir EXACTAMENTE con la plantilla de SAE.
 */
class SaeExport
{
    /** Encabezados en el orden que pide el importador de Aspel SAE. */
    public const HEADERS = [
        'Clave', 'Cliente', 'Fecha de elaboración', 'Su pedido', 'Clave del artículo',
        'Cantidad', 'Precio', 'Desc. 1', 'Desc. 2', 'Desc. 3', 'Clave de vendedor',
        'Comisión', 'Clave de esquema de impuestos',
        'Impuesto 1', 'Impuesto 2', 'Impuesto 3', 'Impuesto 4',
        'Impuesto 5', 'Impuesto 6', 'Impuesto 7', 'Impuesto 8',
        'Observaciones', 'Observaciones a partida', 'Fecha de entrega', 'Fecha de vencimiento',
    ];

    /**
     * Normaliza una clave de esquema de impuestos: entero positivo o null.
     * Acepta lo que venga del producto (int) o de la configuración (string).
     */
    private static function esquema($valor): ?int
    {
        $v = trim((string) ($valor ?? ''));
        if ($v === '' || !ctype_digit($v)) {
            return null;
        }
        return (int) $v > 0 ? (int) $v : null;
    }

    /** Día de la semana (L, M, X, J, V) → letra de serie de SAE (Fase 7.4). */
    private const SERIES_POR_DIA = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V'];

    /**
     * Serie de SAE que corresponde al día de la semana de una fecha de entrega.
     * Devuelve null para sábado/domingo: esas fechas no se permiten (Fase 7.4).
     */
    public static function serieDelDia(string $fecha): ?string
    {
        $ts = strtotime($fecha);
        if ($ts === false) {
            return null;
        }
        return self::SERIES_POR_DIA[(int) date('N', $ts)] ?? null;
    }

    /**
     * Genera la clave de documento con la convención de SAE:
     * serie + consecutivo, longitud fija, número alineado a la derecha con blancos.
     * Ej. serie "PA", longitud 10, consecutivo 101 => "PA     101".
     *
     * @param ?string $serie Serie a usar (Fase 7.4: L/M/X/J/V según el día de
     *                       entrega). Si no se indica, cae a la serie fija de
     *                       configuración (comportamiento previo a 7.4).
     */
    public static function claveDocumento(int $consecutivo, ?string $serie = null): string
    {
        $erp   = config('app.erp', []);
        $serie = $serie ?? (string) ($erp['serie_pedidos'] ?? 'PA');
        $long  = (int) ($erp['longitud_clave'] ?? 10);
        $ancho = max(0, $long - mb_strlen($serie));
        return $serie . str_pad((string) $consecutivo, $ancho, ' ', STR_PAD_LEFT);
    }

    /**
     * Valida y genera las filas de un pedido.
     *
     * @param string $claveDocumento Clave de SAE (serie+consecutivo) para la columna "Clave".
     * @param string $fechaEntrega   Fecha de entrega (Y-m-d) para la columna "Fecha de entrega" (Fase 7.4).
     * @return array{filas: array, errores: array}
     */
    public static function filasDePedido(array $pedido, array $items, string $claveDocumento = '', string $fechaEntrega = ''): array
    {
        $erp = config('app.erp', []);
        $errores = [];
        $filas = [];

        $claveCliente = trim((string) ($pedido['cliente_clave_sae'] ?? ''));
        if ($claveCliente === '') {
            $errores[] = 'El cliente «' . ($pedido['cliente_nombre'] ?? '') . '» no tiene clave de SAE.';
        }

        $fecha = date('d/m/Y', strtotime($pedido['created_at']));

        // Datos del vendedor asignado al cliente (fallback a la config si no tiene).
        $claveVendedor = trim((string) ($pedido['vendedor_clave'] ?? '')) !== ''
            ? (string) $pedido['vendedor_clave']
            : (string) ($erp['clave_vendedor'] ?? '');
        $comisionVal = $pedido['vendedor_comision'] ?? null;
        $comision = ($comisionVal !== null && (float) $comisionVal > 0) ? (float) $comisionVal : '';

        // Esquema de impuestos general: respaldo para los artículos que no traigan
        // el suyo (config ERP_ESQUEMA_IMPUESTOS).
        $esquemaGeneral = self::esquema($erp['esquema_impuestos'] ?? null);

        $fechaEntregaFmt = '';
        if ($fechaEntrega !== '') {
            $tsEntrega = strtotime($fechaEntrega);
            $fechaEntregaFmt = $tsEntrega !== false ? date('d/m/Y', $tsEntrega) : '';
        }

        foreach ($items as $it) {
            $claveArt = trim((string) ($it['clave_sae'] ?? ''));
            if ($claveArt === '') {
                $errores[] = 'El producto «' . $it['nombre'] . '» no tiene clave de SAE.';
            }

            // Cada artículo puede tener su esquema; si no, se usa el general.
            // Sin ninguno de los dos, SAE importaría la partida sin impuestos.
            $esquema = self::esquema($it['esquema_impuestos'] ?? null) ?? $esquemaGeneral;
            if ($esquema === null) {
                $errores[] = 'El producto «' . $it['nombre'] . '» no tiene esquema de impuestos.';
            }

            $precio = round((float) ($it['precio_unitario'] ?? 0), 2);

            $filas[] = [
                $claveDocumento,                  // Clave (serie SAE + consecutivo; agrupa las partidas)
                $claveCliente,                    // Cliente
                $fecha,                           // Fecha de elaboración
                (string) ($pedido['referencia_cliente'] ?? ''), // Su pedido (referencia del cliente)
                $claveArt,                        // Clave del artículo
                (int) $it['cantidad'],            // Cantidad
                $precio > 0 ? $precio : '',       // Precio (capturado al exportar)
                '', '', '',                       // Desc. 1/2/3
                $claveVendedor,                   // Clave de vendedor (del vendedor asignado)
                $comision,                        // Comisión (% del vendedor)
                $esquema ?? '',                   // Clave de esquema de impuestos (del artículo)
                '', '', '', '', '', '', '', '',   // Impuesto 1..8
                (string) ($pedido['notas'] ?? ''),// Observaciones
                '',                               // Observaciones a partida
                $fechaEntregaFmt,                 // Fecha de entrega (capturada al exportar, Fase 7.4)
                '',                               // Fecha de vencimiento
            ];
        }

        return ['filas' => $filas, 'errores' => $errores];
    }
}
