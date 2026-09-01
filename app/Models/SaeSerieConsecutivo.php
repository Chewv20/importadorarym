<?php

namespace App\Models;

use App\Core\Model;

/**
 * Consecutivo de SAE por serie (Fase 7.4). Cada serie (L, M, X, J, V — un
 * día de la semana) lleva su propio contador independiente, que se avanza
 * automáticamente al exportar y puede corregirse a mano desde el panel.
 */
class SaeSerieConsecutivo extends Model
{
    public const SERIES = ['L', 'M', 'X', 'J', 'V'];

    /** Todas las series con su último consecutivo (para la pantalla de reconciliación). */
    public function todas(): array
    {
        return $this->db->query(
            "SELECT * FROM sae_series_consecutivos ORDER BY FIELD(serie, 'L', 'M', 'X', 'J', 'V')"
        )->fetchAll();
    }

    /** Avanza el consecutivo de una serie y devuelve el nuevo valor (atómico). */
    public function siguienteConsecutivo(string $serie): int
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                "INSERT INTO sae_series_consecutivos (serie, ultimo_consecutivo) VALUES (?, 1)
                    ON DUPLICATE KEY UPDATE ultimo_consecutivo = ultimo_consecutivo + 1"
            )->execute([$serie]);

            $st = $this->db->prepare("SELECT ultimo_consecutivo FROM sae_series_consecutivos WHERE serie = ?");
            $st->execute([$serie]);
            $valor = (int) $st->fetchColumn();

            $this->db->commit();
            return $valor;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Corrección manual del consecutivo de una serie. */
    public function establecer(string $serie, int $valor): void
    {
        $this->db->prepare(
            "INSERT INTO sae_series_consecutivos (serie, ultimo_consecutivo) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE ultimo_consecutivo = ?"
        )->execute([$serie, $valor, $valor]);
    }
}
