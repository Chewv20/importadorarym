<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Producto extends Model
{
    public function activos(): array
    {
        return $this->db->query(
            "SELECT p.*, c.nombre AS categoria
               FROM productos p
               LEFT JOIN categorias c ON c.id = p.categoria_id
              WHERE p.activo = 1
              ORDER BY p.orden, p.nombre"
        )->fetchAll();
    }

    public function destacados(int $limit = 6): array
    {
        $st = $this->db->prepare(
            "SELECT * FROM productos WHERE activo = 1 AND destacado = 1 ORDER BY orden LIMIT ?"
        );
        $st->bindValue(1, $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function porCategoria(int $categoriaId): array
    {
        $st = $this->db->prepare(
            "SELECT * FROM productos WHERE activo = 1 AND categoria_id = ? ORDER BY orden, nombre"
        );
        $st->execute([$categoriaId]);
        return $st->fetchAll();
    }

    /** Varios productos por id en UNA sola consulta (evita N+1 en el carrito). */
    public function porIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $st = $this->db->prepare("SELECT * FROM productos WHERE id IN ($ph)");
        $st->execute($ids);

        $out = [];
        foreach ($st->fetchAll() as $row) {
            $out[(int) $row['id']] = $row;
        }
        return $out;
    }

    public function contarActivos(?int $categoriaId = null): int
    {
        if ($categoriaId !== null) {
            $st = $this->db->prepare("SELECT COUNT(*) FROM productos WHERE activo = 1 AND categoria_id = ?");
            $st->execute([$categoriaId]);
            return (int) $st->fetchColumn();
        }
        return (int) $this->db->query("SELECT COUNT(*) FROM productos WHERE activo = 1")->fetchColumn();
    }

    public function activosPaginado(int $limit, int $offset, ?int $categoriaId = null): array
    {
        $where = 'p.activo = 1' . ($categoriaId !== null ? ' AND p.categoria_id = :cat' : '');
        $sql = "SELECT p.*, c.nombre AS categoria
                  FROM productos p
                  LEFT JOIN categorias c ON c.id = p.categoria_id
                 WHERE {$where}
                 ORDER BY p.orden, p.nombre
                 LIMIT :lim OFFSET :off";
        $st = $this->db->prepare($sql);
        if ($categoriaId !== null) {
            $st->bindValue(':cat', $categoriaId, PDO::PARAM_INT);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM productos WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /* --------------------------- Búsqueda + filtro (portal) ---------- */

    /**
     * @param ?int[] $categoriaIds IDs de categoría a incluir (se usa un array,
     *        no un solo id, porque filtrar por una categoría padre debe
     *        incluir también sus subcategorías: ver Categoria::descendientesIds()).
     * @param ?int[] $productoIds Lista de productos por cliente (Fase 7.3):
     *        null = sin restricción (comportamiento de siempre); array (incluso
     *        vacío) = solo esos productos — una lista activa sin productos deja
     *        al cliente sin catálogo, a propósito (no es un error, es el estado).
     */
    private function whereActivos(?array $categoriaIds, ?string $q, ?array $productoIds, array &$params): string
    {
        $cond = ['p.activo = 1'];
        if ($categoriaIds !== null && $categoriaIds !== []) {
            $ph = [];
            foreach (array_values($categoriaIds) as $i => $id) {
                $key = ":cat{$i}";
                $ph[] = $key;
                $params[$key] = (int) $id;
            }
            $cond[] = 'p.categoria_id IN (' . implode(',', $ph) . ')';
        }
        if ($q !== null && $q !== '') {
            $cond[] = '(p.nombre LIKE :qn OR p.sku LIKE :qs)';
            $params[':qn'] = '%' . $q . '%';
            $params[':qs'] = '%' . $q . '%';
        }
        if ($productoIds !== null) {
            if ($productoIds === []) {
                $cond[] = '1 = 0';
            } else {
                $ph = [];
                foreach (array_values($productoIds) as $i => $id) {
                    $key = ":lp{$i}";
                    $ph[] = $key;
                    $params[$key] = (int) $id;
                }
                $cond[] = 'p.id IN (' . implode(',', $ph) . ')';
            }
        }
        return implode(' AND ', $cond);
    }

    public function activosFiltrado(int $limit, int $offset, ?array $categoriaIds, ?string $q, ?array $productoIds = null): array
    {
        $params = [];
        $where  = $this->whereActivos($categoriaIds, $q, $productoIds, $params);
        $sql = "SELECT p.*, c.nombre AS categoria
                  FROM productos p LEFT JOIN categorias c ON c.id = p.categoria_id
                 WHERE {$where} ORDER BY p.orden, p.nombre LIMIT :lim OFFSET :off";
        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function contarActivosFiltrado(?array $categoriaIds, ?string $q, ?array $productoIds = null): int
    {
        $params = [];
        $where  = $this->whereActivos($categoriaIds, $q, $productoIds, $params);
        $st = $this->db->prepare("SELECT COUNT(*) FROM productos p WHERE {$where}");
        $st->execute($params);
        return (int) $st->fetchColumn();
    }

    /** Búsqueda de productos activos por nombre o SKU (para el autocompletar). */
    public function buscar(string $q, int $limit = 15): array
    {
        $like = '%' . $q . '%';
        $st = $this->db->prepare(
            "SELECT id, nombre, sku, precio FROM productos
              WHERE activo = 1 AND (nombre LIKE ? OR sku LIKE ?)
              ORDER BY nombre LIMIT ?"
        );
        $st->bindValue(1, $like);
        $st->bindValue(2, $like);
        $st->bindValue(3, $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function porSku(string $sku): ?array
    {
        $st = $this->db->prepare("SELECT * FROM productos WHERE sku = ? LIMIT 1");
        $st->execute([$sku]);
        return $st->fetch() ?: null;
    }

    public function porClaveSae(string $clave): ?array
    {
        $st = $this->db->prepare("SELECT * FROM productos WHERE clave_sae = ? LIMIT 1");
        $st->execute([$clave]);
        return $st->fetch() ?: null;
    }

    /** Fija el precio por separado (el formulario del panel no lo toca). */
    public function setPrecio(int $id, ?float $precio): void
    {
        $this->db->prepare("UPDATE productos SET precio = ? WHERE id = ?")->execute([$precio, $id]);
    }

    /* --------------------------------- Admin (incluye inactivos) ----- */

    public function todosPaginado(int $limit, int $offset): array
    {
        $st = $this->db->prepare(
            "SELECT p.*, c.nombre AS categoria FROM productos p
               LEFT JOIN categorias c ON c.id = p.categoria_id
              ORDER BY p.orden, p.nombre LIMIT :lim OFFSET :off"
        );
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function contarTodos(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM productos")->fetchColumn();
    }

    public function crear(array $data): int
    {
        $sql = "INSERT INTO productos (categoria_id, nombre, slug, descripcion, sku, clave_sae,
                                      esquema_impuestos, unidad, piezas_por_presentacion, piezas_minimas,
                                      destacado, personalizable, activo, orden)
                VALUES (:categoria_id, :nombre, :slug, :descripcion, :sku, :clave_sae,
                        :esquema_impuestos, :unidad, :piezas_por_presentacion, :piezas_minimas,
                        :destacado, :personalizable, :activo, :orden)";
        $this->db->prepare($sql)->execute([
            ':categoria_id' => ($data['categoria_id'] ?? null) ?: null,
            ':nombre'       => $data['nombre'],
            ':slug'         => $this->slugUnico(($data['slug'] ?? '') ?: $data['nombre']),
            ':descripcion'  => $data['descripcion'] ?? null,
            ':sku'          => $data['sku'] ?? null,
            ':clave_sae'    => $data['clave_sae'] ?? null,
            ':esquema_impuestos' => self::esquemaImpuestos($data['esquema_impuestos'] ?? null),
            ':unidad'       => $data['unidad'] ?? null,
            ':piezas_por_presentacion' => self::piezasPorPresentacion($data['piezas_por_presentacion'] ?? null),
            ':piezas_minimas' => self::piezasMinimas($data['piezas_minimas'] ?? null),
            ':destacado'    => !empty($data['destacado']) ? 1 : 0,
            ':personalizable' => !empty($data['personalizable']) ? 1 : 0,
            ':activo'       => isset($data['activo']) ? (int) (bool) $data['activo'] : 1,
            ':orden'        => (int) ($data['orden'] ?? 0),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): void
    {
        $sql = "UPDATE productos SET categoria_id = :categoria_id, nombre = :nombre, slug = :slug,
                    descripcion = :descripcion, sku = :sku, clave_sae = :clave_sae,
                    esquema_impuestos = :esquema_impuestos, unidad = :unidad,
                    piezas_por_presentacion = :piezas_por_presentacion, piezas_minimas = :piezas_minimas,
                    destacado = :destacado, personalizable = :personalizable, activo = :activo, orden = :orden
                WHERE id = :id";
        $this->db->prepare($sql)->execute([
            ':categoria_id' => ($data['categoria_id'] ?? null) ?: null,
            ':nombre'       => $data['nombre'],
            ':slug'         => $this->slugUnico(($data['slug'] ?? '') ?: $data['nombre'], $id),
            ':descripcion'  => $data['descripcion'] ?? null,
            ':sku'          => $data['sku'] ?? null,
            ':clave_sae'    => $data['clave_sae'] ?? null,
            ':esquema_impuestos' => self::esquemaImpuestos($data['esquema_impuestos'] ?? null),
            ':unidad'       => $data['unidad'] ?? null,
            ':piezas_por_presentacion' => self::piezasPorPresentacion($data['piezas_por_presentacion'] ?? null),
            ':piezas_minimas' => self::piezasMinimas($data['piezas_minimas'] ?? null),
            ':destacado'    => !empty($data['destacado']) ? 1 : 0,
            ':personalizable' => !empty($data['personalizable']) ? 1 : 0,
            ':activo'       => isset($data['activo']) ? (int) (bool) $data['activo'] : 1,
            ':orden'        => (int) ($data['orden'] ?? 0),
            ':id'           => $id,
        ]);
    }

    /**
     * Normaliza la clave de esquema de impuestos de SAE: entero positivo o null.
     * Un 0 o un texto vacío significan «sin capturar», no el esquema cero.
     */
    public static function esquemaImpuestos($valor): ?int
    {
        $v = trim((string) ($valor ?? ''));
        if ($v === '' || !ctype_digit($v)) {
            return null;
        }
        $n = (int) $v;
        return ($n > 0 && $n <= 65535) ? $n : null;
    }

    /**
     * Normaliza las piezas por presentación (ej. 1000 para "millar"): entero
     * positivo o null. Vacío o 0 significan «sin restricción de lote».
     */
    public static function piezasPorPresentacion($valor): ?int
    {
        $v = trim((string) ($valor ?? ''));
        if ($v === '' || !ctype_digit($v)) {
            return null;
        }
        $n = (int) $v;
        return $n > 0 ? $n : null;
    }

    /**
     * Normaliza el mínimo de piezas del pedido (ej. 100, aunque la presentación
     * sea de 50 — exige 2 paquetes como mínimo): entero positivo o null. Es
     * independiente de piezas_por_presentacion: esa gobierna el INCREMENTO
     * (múltiplos válidos), esta gobierna el PISO (cantidad mínima aceptada).
     */
    public static function piezasMinimas($valor): ?int
    {
        $v = trim((string) ($valor ?? ''));
        if ($v === '' || !ctype_digit($v)) {
            return null;
        }
        $n = (int) $v;
        return $n > 0 ? $n : null;
    }

    /**
     * Extrae [piezas_por_presentacion, piezas_minimas] de una fila de producto
     * (SELECT * de la tabla), normalizados a int|null — evita repetir el
     * isset/null-check en cada controlador que captura cantidades.
     * @return array{0:?int,1:?int}
     */
    public static function loteDesdeFila(array $producto): array
    {
        $pp = $producto['piezas_por_presentacion'] ?? null;
        $pm = $producto['piezas_minimas'] ?? null;
        return [$pp !== null ? (int) $pp : null, $pm !== null ? (int) $pm : null];
    }

    /**
     * Ajusta la cantidad solicitada: primero al múltiplo de presentación más
     * cercano, redondeando SIEMPRE hacia arriba (nunca se entrega menos de lo
     * pedido); luego, si hay un mínimo de piezas, sube el resultado hasta ese
     * mínimo (el mínimo también se redondea a un múltiplo válido, por si se
     * capturó uno que no lo fuera). Sin ninguna restricción, solo aplica el
     * mínimo de 1 pieza. Ej.: presentación 50, mínimo 100 → pedir 1 pieza
     * resulta en 100 (2 paquetes), no en 50 (1 paquete).
     */
    public static function cantidadValida(int $solicitada, ?int $piezasPorPresentacion, ?int $piezasMinimas = null): int
    {
        $solicitada = max(1, $solicitada);
        $tieneLote = $piezasPorPresentacion !== null && $piezasPorPresentacion > 0;

        $redondeada = $tieneLote
            ? (int) (ceil($solicitada / $piezasPorPresentacion) * $piezasPorPresentacion)
            : $solicitada;

        if ($piezasMinimas !== null && $piezasMinimas > 0) {
            $piso = $tieneLote
                ? (int) (ceil($piezasMinimas / $piezasPorPresentacion) * $piezasPorPresentacion)
                : $piezasMinimas;
            $redondeada = max($redondeada, $piso);
        }

        return $redondeada;
    }

    public function eliminar(int $id): void
    {
        $this->db->prepare("DELETE FROM productos WHERE id = ?")->execute([$id]);
    }

    /* --------------------------------- Imágenes (galería) ------------ */

    public const MAX_IMAGENES = 5;

    /** Imágenes de un producto, ordenadas (la primera es la principal). */
    public function imagenes(int $productoId): array
    {
        $st = $this->db->prepare(
            "SELECT * FROM producto_imagenes WHERE producto_id = ? ORDER BY orden, id"
        );
        $st->execute([$productoId]);
        return $st->fetchAll();
    }

    /** Imágenes de varios productos en UNA consulta, agrupadas por producto_id (evita N+1). */
    public function imagenesPorProductos(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $st = $this->db->prepare(
            "SELECT producto_id, ruta FROM producto_imagenes
              WHERE producto_id IN ($ph) ORDER BY orden, id"
        );
        $st->execute($ids);

        $out = [];
        foreach ($st->fetchAll() as $row) {
            $out[(int) $row['producto_id']][] = $row['ruta'];
        }
        return $out;
    }

    public function contarImagenes(int $productoId): int
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM producto_imagenes WHERE producto_id = ?");
        $st->execute([$productoId]);
        return (int) $st->fetchColumn();
    }

    /** Agrega imágenes al final de la galería del producto. */
    public function agregarImagenes(int $productoId, array $rutas): void
    {
        if (!$rutas) {
            return;
        }
        $orden = $this->contarImagenes($productoId);
        $st = $this->db->prepare(
            "INSERT INTO producto_imagenes (producto_id, ruta, orden) VALUES (?, ?, ?)"
        );
        foreach ($rutas as $ruta) {
            $st->execute([$productoId, $ruta, $orden++]);
        }
    }

    /** Elimina una imagen del producto y devuelve su ruta (para borrar el archivo). */
    public function eliminarImagen(int $imagenId, int $productoId): ?string
    {
        $st = $this->db->prepare("SELECT ruta FROM producto_imagenes WHERE id = ? AND producto_id = ?");
        $st->execute([$imagenId, $productoId]);
        $ruta = $st->fetchColumn();
        if ($ruta === false) {
            return null;
        }
        $this->db->prepare("DELETE FROM producto_imagenes WHERE id = ?")->execute([$imagenId]);
        return (string) $ruta;
    }

    private function slugUnico(string $base, ?int $exceptId = null): string
    {
        $slug = slugify($base);
        $candidate = $slug;
        $i = 2;
        while (true) {
            $sql = "SELECT COUNT(*) FROM productos WHERE slug = ?" . ($exceptId ? " AND id <> ?" : "");
            $st = $this->db->prepare($sql);
            $st->execute($exceptId ? [$candidate, $exceptId] : [$candidate]);
            if ((int) $st->fetchColumn() === 0) {
                return $candidate;
            }
            $candidate = $slug . '-' . $i++;
        }
    }
}
