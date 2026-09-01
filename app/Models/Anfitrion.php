<?php

namespace App\Models;

use App\Core\Model;

/**
 * Anfitriones: empleados que pueden recibir visitas (no necesariamente
 * usuarios del sistema). El kiosco los ofrece en un selector.
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

    public function todos(): array
    {
        return $this->db->query("SELECT * FROM anfitriones ORDER BY nombre")->fetchAll();
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
            "INSERT INTO anfitriones (nombre, email, area, activo) VALUES (?, ?, ?, ?)"
        )->execute([
            $d['nombre'],
            $d['email'],
            ($d['area'] ?? '') ?: null,
            !empty($d['activo']) ? 1 : 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $d): void
    {
        $this->db->prepare(
            "UPDATE anfitriones SET nombre = ?, email = ?, area = ?, activo = ? WHERE id = ?"
        )->execute([
            $d['nombre'],
            $d['email'],
            ($d['area'] ?? '') ?: null,
            !empty($d['activo']) ? 1 : 0,
            $id,
        ]);
    }

    public function eliminar(int $id): void
    {
        $this->db->prepare("DELETE FROM anfitriones WHERE id = ?")->execute([$id]);
    }
}
