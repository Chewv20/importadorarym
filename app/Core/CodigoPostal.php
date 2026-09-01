<?php

namespace App\Core;

/**
 * Autocompletar de colonia/municipio/estado a partir del código postal, vía
 * una API pública de terceros (SEPOMEX) sin llave ni registro. Es una mejora
 * de UX, nunca un requisito: si el servicio falla, tarda o no responde, el
 * formulario de dirección sigue funcionando con captura 100% manual — por
 * eso cualquier error se traga y devuelve null, nunca una excepción.
 */
class CodigoPostal
{
    private const URL = 'https://cp.terio.dev/v1/codigos-postales/';
    private const TIMEOUT_SEGUNDOS = 4;

    /**
     * @return array{colonias: string[], municipio: ?string, ciudad: ?string, estado: ?string}|null
     */
    public static function buscar(string $cp): ?array
    {
        if (!preg_match('/^\d{5}$/', $cp) || !function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init(self::URL . $cp);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT_SEGUNDOS,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_SEGUNDOS,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $crudo = curl_exec($ch);
        $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($crudo === false || $codigo !== 200) {
            return null;
        }

        $json = json_decode((string) $crudo, true);
        if (!is_array($json) || empty($json['datos']) || !is_array($json['datos'])) {
            return null;
        }

        $colonias = [];
        foreach ($json['datos'] as $fila) {
            if (is_array($fila) && !empty($fila['asentamiento'])) {
                $colonias[] = (string) $fila['asentamiento'];
            }
        }
        $primera = $json['datos'][0];

        return [
            'colonias'  => array_values(array_unique($colonias)),
            'municipio' => $primera['municipio'] ?? null,
            'ciudad'    => $primera['ciudad'] ?? null,
            'estado'    => $primera['estado'] ?? null,
        ];
    }
}
