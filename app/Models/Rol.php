<?php

namespace App\Models;

use App\Core\Model;

class Rol extends Model
{
    public function todos(): array
    {
        return $this->db->query("SELECT * FROM roles ORDER BY id")->fetchAll();
    }

    public function porSlug(string $slug): ?array
    {
        $st = $this->db->prepare("SELECT * FROM roles WHERE slug = ?");
        $st->execute([$slug]);
        return $st->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM roles WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /** Claves de permiso asignadas a un rol. */
    public function permisos(int $rolId): array
    {
        $st = $this->db->prepare(
            "SELECT p.clave FROM rol_permiso rp JOIN permisos p ON p.id = rp.permiso_id WHERE rp.rol_id = ?"
        );
        $st->execute([$rolId]);
        return $st->fetchAll(\PDO::FETCH_COLUMN);
    }

    /** IDs de permiso asignados a un rol. */
    public function permisoIds(int $rolId): array
    {
        $st = $this->db->prepare("SELECT permiso_id FROM rol_permiso WHERE rol_id = ?");
        $st->execute([$rolId]);
        return array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function sincronizarPermisos(int $rolId, array $permisoIds): void
    {
        $this->db->prepare("DELETE FROM rol_permiso WHERE rol_id = ?")->execute([$rolId]);
        $st = $this->db->prepare("INSERT INTO rol_permiso (rol_id, permiso_id) VALUES (?, ?)");
        foreach (array_unique(array_map('intval', $permisoIds)) as $pid) {
            $st->execute([$rolId, $pid]);
        }
    }

    public function conteoUsuarios(int $rolId): int
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE rol_id = ?");
        $st->execute([$rolId]);
        return (int) $st->fetchColumn();
    }
}
