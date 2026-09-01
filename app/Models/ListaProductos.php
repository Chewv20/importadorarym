<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Lista de productos que un cliente puede ver/pedir (Fase 7.3). Reemplaza, por
 * ahora, la idea pausada de "precios por cliente" con algo más simple: qué
 * productos, no a qué precio. Un cliente sin lista asignada ve el catálogo
 * completo — el filtro solo se activa cuando tiene una lista.
 */
class ListaProductos extends Model
{
    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM listas_productos WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /** Todas las listas (incluye inactivas), con el número de productos y de clientes. */
    public function todas(): array
    {
        return $this->db->query(
            "SELECT lp.*,
                    (SELECT COUNT(*) FROM listas_productos_items WHERE lista_id = lp.id) AS num_productos,
                    (SELECT COUNT(*) FROM usuarios WHERE lista_productos_id = lp.id) AS num_clientes
               FROM listas_productos lp
              ORDER BY lp.nombre"
        )->fetchAll();
    }

    /** Solo listas activas (para el <select> de asignar a un cliente). */
    public function activas(): array
    {
        return $this->db->query(
            "SELECT id, nombre FROM listas_productos WHERE activa = 1 ORDER BY nombre"
        )->fetchAll();
    }

    public function crear(array $data): int
    {
        $this->db->prepare(
            "INSERT INTO listas_productos (nombre, descripcion, activa) VALUES (?, ?, ?)"
        )->execute([
            $data['nombre'],
            $data['descripcion'] ?? null,
            isset($data['activa']) ? (int) (bool) $data['activa'] : 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): void
    {
        $this->db->prepare(
            "UPDATE listas_productos SET nombre = ?, descripcion = ?, activa = ? WHERE id = ?"
        )->execute([
            $data['nombre'],
            $data['descripcion'] ?? null,
            isset($data['activa']) ? (int) (bool) $data['activa'] : 1,
            $id,
        ]);
    }

    /** Al eliminar, los clientes asignados vuelven al catálogo completo (FK ON DELETE SET NULL). */
    public function eliminar(int $id): void
    {
        $this->db->prepare("DELETE FROM listas_productos WHERE id = ?")->execute([$id]);
    }

    /* --------------------------------- Productos de la lista --------- */

    /** Productos de la lista, con sus datos (para pintarlos en el panel). */
    public function productos(int $listaId): array
    {
        $st = $this->db->prepare(
            "SELECT p.* FROM productos p
               JOIN listas_productos_items lpi ON lpi.producto_id = p.id
              WHERE lpi.lista_id = ? ORDER BY p.nombre"
        );
        $st->execute([$listaId]);
        return $st->fetchAll();
    }

    /** Solo los IDs (para el filtro del catálogo — más barato que traer filas completas). */
    public function productoIds(int $listaId): array
    {
        $st = $this->db->prepare("SELECT producto_id FROM listas_productos_items WHERE lista_id = ?");
        $st->execute([$listaId]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }

    public function agregarProducto(int $listaId, int $productoId): void
    {
        $this->db->prepare(
            "INSERT IGNORE INTO listas_productos_items (lista_id, producto_id) VALUES (?, ?)"
        )->execute([$listaId, $productoId]);
    }

    public function quitarProducto(int $listaId, int $productoId): void
    {
        $this->db->prepare(
            "DELETE FROM listas_productos_items WHERE lista_id = ? AND producto_id = ?"
        )->execute([$listaId, $productoId]);
    }

    /**
     * Lista efectiva para filtrar el catálogo de un cliente: null si no tiene
     * lista asignada o si su lista está inactiva (equivale a "ve todo", el
     * comportamiento de siempre — una lista desactivada deja de aplicar sin
     * tener que desasignarla cliente por cliente).
     * @return ?array<int> IDs de producto permitidos (puede ser un array vacío:
     *         lista activa sin productos = el cliente no ve nada, a propósito).
     */
    public function idsParaCliente(?int $listaProductosId): ?array
    {
        if ($listaProductosId === null) {
            return null;
        }
        $lista = $this->find($listaProductosId);
        if (!$lista || (int) $lista['activa'] !== 1) {
            return null;
        }
        return $this->productoIds($listaProductosId);
    }
}
