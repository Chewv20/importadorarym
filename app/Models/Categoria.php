<?php

namespace App\Models;

use App\Core\Model;

/**
 * Categorías de producto, con jerarquía de 2 niveles (categoría → subcategoría).
 * `categoria_padre_id` NULL = categoría principal (raíz); no NULL = subcategoría.
 * El límite de 2 niveles se aplica en Admin\CategoriaController, no aquí.
 */
class Categoria extends Model
{
    /** Todas las categorías activas, planas (sin distinguir nivel). */
    public function activas(): array
    {
        return $this->db
            ->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY orden, nombre")
            ->fetchAll();
    }

    /** Solo categorías principales activas (para la grilla del home). */
    public function raicesActivas(): array
    {
        return $this->db
            ->query("SELECT * FROM categorias WHERE activo = 1 AND categoria_padre_id IS NULL ORDER BY orden, nombre")
            ->fetchAll();
    }

    /**
     * Categorías principales activas, cada una con su clave 'hijos' = sus
     * subcategorías activas. Para el menú del catálogo público y los
     * selectores del portal.
     */
    public function activasArbol(): array
    {
        return $this->arbol(true);
    }

    /** Igual que activasArbol() pero incluye inactivas (para el panel). */
    public function todasArbol(): array
    {
        return $this->arbol(false);
    }

    private function arbol(bool $soloActivas): array
    {
        $condRaiz = $soloActivas ? 'activo = 1 AND categoria_padre_id IS NULL' : 'categoria_padre_id IS NULL';
        $raices = $this->db->query(
            "SELECT * FROM categorias WHERE {$condRaiz} ORDER BY orden, nombre"
        )->fetchAll();

        if (!$raices) {
            return [];
        }

        $ids = array_column($raices, 'id');
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $hijosFiltro = $soloActivas ? 'AND activo = 1' : '';
        $st = $this->db->prepare(
            "SELECT * FROM categorias WHERE categoria_padre_id IN ($ph) {$hijosFiltro} ORDER BY orden, nombre"
        );
        $st->execute($ids);

        $hijosPorPadre = [];
        foreach ($st->fetchAll() as $hijo) {
            $hijosPorPadre[(int) $hijo['categoria_padre_id']][] = $hijo;
        }

        foreach ($raices as &$r) {
            $r['hijos'] = $hijosPorPadre[(int) $r['id']] ?? [];
        }
        unset($r);

        return $raices;
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM categorias WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function porSlug(string $slug): ?array
    {
        $st = $this->db->prepare("SELECT * FROM categorias WHERE slug = ?");
        $st->execute([$slug]);
        return $st->fetch() ?: null;
    }

    /** ¿Es una categoría principal (sin padre)? */
    public function esRaiz(int $id): bool
    {
        $st = $this->db->prepare("SELECT categoria_padre_id FROM categorias WHERE id = ?");
        $st->execute([$id]);
        $padre = $st->fetchColumn();
        return $padre !== false && $padre === null;
    }

    /** ¿Tiene subcategorías? (impide asignarle un padre: rompería el límite de 2 niveles). */
    public function tieneHijos(int $id): bool
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM categorias WHERE categoria_padre_id = ?");
        $st->execute([$id]);
        return (int) $st->fetchColumn() > 0;
    }

    /**
     * IDs de la categoría más los de sus subcategorías (solo 2 niveles, así
     * que basta un nivel de hijos). Si $id es una subcategoría, devuelve solo
     * [$id]. Úsalo para que filtrar por una categoría padre incluya lo que
     * hay en sus subcategorías.
     */
    public function descendientesIds(int $id): array
    {
        $st = $this->db->prepare("SELECT id FROM categorias WHERE categoria_padre_id = ?");
        $st->execute([$id]);
        $hijos = array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN));
        return array_merge([$id], $hijos);
    }

    /* --------------------------------- Admin (incluye inactivas) ----- */

    /**
     * Todas las categorías, planas pero ORDENADAS por jerarquía (cada
     * subcategoría aparece justo después de su padre) para pintar un listado
     * indentado sin tener que anidar en la vista. Incluye 'nivel' (0 o 1) y
     * 'num_productos'.
     */
    public function todas(): array
    {
        $raices = $this->db->query(
            "SELECT c.*, (SELECT COUNT(*) FROM productos WHERE categoria_id = c.id) AS num_productos
               FROM categorias c WHERE c.categoria_padre_id IS NULL ORDER BY c.orden, c.nombre"
        )->fetchAll();

        $hijos = $this->db->query(
            "SELECT c.*, (SELECT COUNT(*) FROM productos WHERE categoria_id = c.id) AS num_productos
               FROM categorias c WHERE c.categoria_padre_id IS NOT NULL ORDER BY c.orden, c.nombre"
        )->fetchAll();

        $hijosPorPadre = [];
        foreach ($hijos as $h) {
            $hijosPorPadre[(int) $h['categoria_padre_id']][] = $h;
        }

        $out = [];
        foreach ($raices as $r) {
            $r['nivel'] = 0;
            $out[] = $r;
            foreach ($hijosPorPadre[(int) $r['id']] ?? [] as $h) {
                $h['nivel'] = 1;
                $out[] = $h;
            }
        }
        return $out;
    }

    public function crear(array $data): int
    {
        $padreId = $this->normalizarPadre($data['categoria_padre_id'] ?? null);
        $sql = "INSERT INTO categorias (categoria_padre_id, nombre, slug, descripcion, activo, orden)
                VALUES (:padre, :nombre, :slug, :descripcion, :activo, :orden)";
        $this->db->prepare($sql)->execute([
            ':padre'       => $padreId,
            ':nombre'      => $data['nombre'],
            ':slug'        => $this->slugUnico($data['nombre'], (string) ($data['slug'] ?? ''), $padreId, null),
            ':descripcion' => $data['descripcion'] ?? null,
            ':activo'      => isset($data['activo']) ? (int) (bool) $data['activo'] : 1,
            ':orden'       => (int) ($data['orden'] ?? 0),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): void
    {
        $padreId = $this->normalizarPadre($data['categoria_padre_id'] ?? null);
        $sql = "UPDATE categorias SET categoria_padre_id = :padre, nombre = :nombre, slug = :slug,
                    descripcion = :descripcion, activo = :activo, orden = :orden WHERE id = :id";
        $this->db->prepare($sql)->execute([
            ':padre'       => $padreId,
            ':nombre'      => $data['nombre'],
            ':slug'        => $this->slugUnico($data['nombre'], (string) ($data['slug'] ?? ''), $padreId, $id),
            ':descripcion' => $data['descripcion'] ?? null,
            ':activo'      => isset($data['activo']) ? (int) (bool) $data['activo'] : 1,
            ':orden'       => (int) ($data['orden'] ?? 0),
            ':id'          => $id,
        ]);
    }

    public function eliminar(int $id): void
    {
        // Los productos quedan sin categoría y las subcategorías se vuelven
        // principales (FK ON DELETE SET NULL en ambos casos).
        $this->db->prepare("DELETE FROM categorias WHERE id = ?")->execute([$id]);
    }

    /** Entero positivo o null; nunca 0 (el select manda "" para "sin padre"). */
    private function normalizarPadre($valor): ?int
    {
        $v = (int) $valor;
        return $v > 0 ? $v : null;
    }

    /**
     * Slug único.
     * - Si el usuario capturó uno manualmente ($slugManual no vacío), se
     *   respeta tal cual (solo se resuelve la colisión).
     * - Si no, se genera del nombre; cuando la categoría tiene padre, se
     *   antepone el nombre del padre ("bambu-cubiertos") en vez de depender
     *   de un sufijo "-2" cuando dos subcategorías distintas comparten
     *   nombre (p. ej. "Cubiertos" bajo dos padres distintos).
     */
    private function slugUnico(string $nombre, string $slugManual, ?int $padreId, ?int $exceptId): string
    {
        if (trim($slugManual) !== '') {
            $slug = slugify($slugManual);
        } elseif ($padreId !== null && ($padre = $this->find($padreId))) {
            $slug = slugify($padre['nombre'] . '-' . $nombre);
        } else {
            $slug = slugify($nombre);
        }

        $candidate = $slug;
        $i = 2;
        while (true) {
            $sql = "SELECT COUNT(*) FROM categorias WHERE slug = ?" . ($exceptId ? " AND id <> ?" : "");
            $st = $this->db->prepare($sql);
            $st->execute($exceptId ? [$candidate, $exceptId] : [$candidate]);
            if ((int) $st->fetchColumn() === 0) {
                return $candidate;
            }
            $candidate = $slug . '-' . $i++;
        }
    }
}
