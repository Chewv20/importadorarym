<?php

namespace App\Models;

use App\Core\Model;

/**
 * Anfitriones: empleados que pueden recibir visitas (no necesariamente
 * usuarios del sistema). El kiosco los ofrece en un selector, acotado a la
 * oficina del dispositivo.
 */
class Anfitrion extends Model
{
    /** Anfitriones activos, para el selector del kiosco. */
    public function activos(): array
    {
        return $this->db->query(
            "SELECT id, nombre, email, area FROM anfitriones WHERE activo = 1 ORDER BY nombre"
        )->fetchAll();
    }

    /**
     * Anfitriones activos de una oficina, para el kiosco de ese dispositivo.
     * Si el dispositivo no tiene oficina asignada ($oficinaId = null) se
     * ofrecen todos los activos (compatibilidad con instalaciones sin oficinas).
     */
    public function activosPorOficina(?int $oficinaId): array
    {
        if ($oficinaId === null) {
            return $this->activos();
        }
        $st = $this->db->prepare(
            "SELECT id, nombre, email, area FROM anfitriones
              WHERE activo = 1 AND oficina_id = ? ORDER BY area, nombre"
        );
        $st->execute([$oficinaId]);
        return $st->fetchAll();
    }

    public function todos(): array
    {
        return $this->db->query(
            "SELECT a.*, o.nombre AS oficina_nombre
               FROM anfitriones a
               LEFT JOIN oficinas o ON o.id = a.oficina_id
              ORDER BY a.nombre"
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM anfitriones WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function crear(array $d): int
    {
        $this->db->prepare(
            "INSERT INTO anfitriones (nombre, email, area, oficina_id, activo) VALUES (?, ?, ?, ?, ?)"
        )->execute([
            $d['nombre'],
            $d['email'],
            ($d['area'] ?? '') ?: null,
            $d['oficina_id'] ?? null,
            !empty($d['activo']) ? 1 : 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $d): void
    {
        $this->db->prepare(
            "UPDATE anfitriones SET nombre = ?, email = ?, area = ?, oficina_id = ?, activo = ? WHERE id = ?"
        )->execute([
            $d['nombre'],
            $d['email'],
            ($d['area'] ?? '') ?: null,
            $d['oficina_id'] ?? null,
            !empty($d['activo']) ? 1 : 0,
            $id,
        ]);
    }

    public function eliminar(int $id): void
    {
        $this->db->prepare("DELETE FROM anfitriones WHERE id = ?")->execute([$id]);
    }
}
