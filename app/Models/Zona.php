<?php

namespace App\Models;

use App\Core\Model;

/**
 * Zona de reparto (Fase 7.7). Por ahora es solo un catálogo informativo que se
 * puede asociar a un envío — sin ruteo automático ni límites geográficos, eso
 * queda fuera de esta fase (ver nota de diseño en el roadmap).
 */
class Zona extends Model
{
    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM zonas WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /** Todas las zonas (incluye inactivas), con el número de envíos asociados. */
    public function todas(): array
    {
        return $this->db->query(
            "SELECT z.*, (SELECT COUNT(*) FROM pedido_envios WHERE zona_id = z.id) AS num_envios
               FROM zonas z ORDER BY z.nombre"
        )->fetchAll();
    }

    /** Solo zonas activas (para el <select> de asignar a un envío). */
    public function activas(): array
    {
        return $this->db->query(
            "SELECT id, nombre FROM zonas WHERE activa = 1 ORDER BY nombre"
        )->fetchAll();
    }

    public function crear(array $data): int
    {
        $this->db->prepare(
            "INSERT INTO zonas (nombre, activa) VALUES (?, ?)"
        )->execute([
            $data['nombre'],
            isset($data['activa']) ? (int) (bool) $data['activa'] : 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): void
    {
        $this->db->prepare(
            "UPDATE zonas SET nombre = ?, activa = ? WHERE id = ?"
        )->execute([
            $data['nombre'],
            isset($data['activa']) ? (int) (bool) $data['activa'] : 1,
            $id,
        ]);
    }

    /** Al eliminar, los envíos asociados quedan sin zona (FK ON DELETE SET NULL). */
    public function eliminar(int $id): void
    {
        $this->db->prepare("DELETE FROM zonas WHERE id = ?")->execute([$id]);
    }
}
