<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Vacante extends Model
{
    public const TIPOS = [
        'tiempo_completo' => 'Tiempo completo',
        'medio_tiempo'    => 'Medio tiempo',
        'temporal'        => 'Temporal',
        'practicas'       => 'Prácticas',
    ];

    /** Vacantes abiertas (sitio público). */
    public function abiertas(): array
    {
        return $this->db->query(
            "SELECT * FROM vacantes WHERE estado = 'abierta' ORDER BY created_at DESC"
        )->fetchAll();
    }

    /** Vacante abierta por slug (detalle público). */
    public function abiertaPorSlug(string $slug): ?array
    {
        $st = $this->db->prepare("SELECT * FROM vacantes WHERE slug = ? AND estado = 'abierta'");
        $st->execute([$slug]);
        return $st->fetch() ?: null;
    }

    public function todas(): array
    {
        return $this->db->query(
            "SELECT v.*, (SELECT COUNT(*) FROM postulaciones WHERE vacante_id = v.id) AS num_postulaciones
               FROM vacantes v ORDER BY v.created_at DESC"
        )->fetchAll();
    }

    /** Listado del panel, paginado (todas() sigue completo para el filtro de postulaciones). */
    public function paginadas(int $limit, int $offset): array
    {
        $st = $this->db->prepare(
            "SELECT v.*, (SELECT COUNT(*) FROM postulaciones WHERE vacante_id = v.id) AS num_postulaciones
               FROM vacantes v ORDER BY v.created_at DESC LIMIT :lim OFFSET :off"
        );
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function contar(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM vacantes")->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM vacantes WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function crear(array $d): int
    {
        $slug = $this->slugUnico(slugify($d['titulo']));
        $this->db->prepare(
            "INSERT INTO vacantes (titulo, slug, area, ubicacion, tipo, descripcion, requisitos, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            $d['titulo'], $slug, ($d['area'] ?? '') ?: null, ($d['ubicacion'] ?? '') ?: null,
            $d['tipo'], ($d['descripcion'] ?? '') ?: null, ($d['requisitos'] ?? '') ?: null,
            $d['estado'] ?? 'abierta',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $d): void
    {
        $this->db->prepare(
            "UPDATE vacantes SET titulo = ?, area = ?, ubicacion = ?, tipo = ?, descripcion = ?, requisitos = ?, estado = ? WHERE id = ?"
        )->execute([
            $d['titulo'], ($d['area'] ?? '') ?: null, ($d['ubicacion'] ?? '') ?: null,
            $d['tipo'], ($d['descripcion'] ?? '') ?: null, ($d['requisitos'] ?? '') ?: null,
            $d['estado'] ?? 'abierta', $id,
        ]);
    }

    public function eliminar(int $id): void
    {
        $this->db->prepare("DELETE FROM vacantes WHERE id = ?")->execute([$id]);
    }

    /** Genera un slug único agregando sufijo numérico si ya existe. */
    private function slugUnico(string $base, ?int $exceptoId = null): string
    {
        $base = $base ?: 'vacante';
        $slug = $base;
        $i = 2;
        while (true) {
            $sql = "SELECT id FROM vacantes WHERE slug = ?" . ($exceptoId ? " AND id <> ?" : "");
            $st = $this->db->prepare($sql);
            $st->execute($exceptoId ? [$slug, $exceptoId] : [$slug]);
            if (!$st->fetch()) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }
}
