<?php

namespace App\Models;

use App\Core\Model;

/**
 * Oficina / sucursal. Separa la bitácora de visitas: cada dispositivo de
 * recepción pertenece a una oficina y cada visita guarda la suya al registrarse.
 */
class Oficina extends Model
{
    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM oficinas WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /** Todas (incluye inactivas) con el número de dispositivos y visitas asociados. */
    public function todas(): array
    {
        return $this->db->query(
            "SELECT o.*,
                    (SELECT COUNT(*) FROM checador_dispositivos WHERE oficina_id = o.id) AS num_dispositivos,
                    (SELECT COUNT(*) FROM visitas WHERE oficina_id = o.id) AS num_visitas
               FROM oficinas o ORDER BY o.nombre"
        )->fetchAll();
    }

    /** Solo oficinas activas (para los <select>). */
    public function activas(): array
    {
        return $this->db->query(
            "SELECT id, nombre FROM oficinas WHERE activa = 1 ORDER BY nombre"
        )->fetchAll();
    }

    public function crear(array $d): int
    {
        $this->db->prepare(
            "INSERT INTO oficinas (nombre, activa) VALUES (?, ?)"
        )->execute([
            $d['nombre'],
            !empty($d['activa']) ? 1 : 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $d): void
    {
        $this->db->prepare(
            "UPDATE oficinas SET nombre = ?, activa = ? WHERE id = ?"
        )->execute([
            $d['nombre'],
            !empty($d['activa']) ? 1 : 0,
            $id,
        ]);
    }

    /** Al eliminar, sus dispositivos, visitas y usuarios quedan sin oficina (FK ON DELETE SET NULL). */
    public function eliminar(int $id): void
    {
        $this->db->prepare("DELETE FROM oficinas WHERE id = ?")->execute([$id]);
    }
}
