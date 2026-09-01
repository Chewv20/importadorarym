<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Plantilla de pedido recurrente: un cliente programa repetir un conjunto de
 * productos cada N días. El cron (database/recordatorios_recurrentes.php)
 * envía el aviso y reprograma la siguiente fecha.
 */
class PedidoRecurrente extends Model
{
    /** Frecuencias permitidas (días). Controlado: nada de texto libre. */
    public const FRECUENCIAS = [7, 15, 30, 45, 60, 90];

    /**
     * Crea la plantilla a partir de los items de un pedido ya existente.
     * @param array $items Cada uno con producto_id, sku, nombre, cantidad.
     */
    public function crear(int $usuarioId, int $origenPedidoId, array $items, int $frecuenciaDias, ?string $nombre = null): int
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                "INSERT INTO pedidos_recurrentes
                    (usuario_id, origen_pedido_id, nombre, frecuencia_dias, proximo_recordatorio_en)
                 VALUES (?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL ? DAY))"
            )->execute([$usuarioId, $origenPedidoId, $nombre, $frecuenciaDias, $frecuenciaDias]);

            $id = (int) $this->db->lastInsertId();

            $stItem = $this->db->prepare(
                "INSERT INTO pedidos_recurrentes_items (pedido_recurrente_id, producto_id, sku, nombre, cantidad)
                 VALUES (?, ?, ?, ?, ?)"
            );
            foreach ($items as $it) {
                $stItem->execute([
                    $id,
                    $it['producto_id'] ?? null,
                    $it['sku'] ?? null,
                    $it['nombre'],
                    max(1, (int) ($it['cantidad'] ?? 1)),
                ]);
            }

            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM pedidos_recurrentes WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /** Plantillas del cliente, activas primero, más recientes primero. */
    public function porUsuario(int $usuarioId): array
    {
        $st = $this->db->prepare(
            "SELECT * FROM pedidos_recurrentes WHERE usuario_id = ? ORDER BY activo DESC, created_at DESC"
        );
        $st->execute([$usuarioId]);
        return $st->fetchAll();
    }

    public function items(int $pedidoRecurrenteId): array
    {
        $st = $this->db->prepare(
            "SELECT * FROM pedidos_recurrentes_items WHERE pedido_recurrente_id = ? ORDER BY id"
        );
        $st->execute([$pedidoRecurrenteId]);
        return $st->fetchAll();
    }

    public function pausar(int $id): void
    {
        $this->db->prepare("UPDATE pedidos_recurrentes SET activo = 0 WHERE id = ?")->execute([$id]);
    }

    /** Reanuda y reprograma el recordatorio desde hoy (no arrastra fechas vencidas). */
    public function reanudar(int $id): void
    {
        $this->db->prepare(
            "UPDATE pedidos_recurrentes
                SET activo = 1, proximo_recordatorio_en = DATE_ADD(CURDATE(), INTERVAL frecuencia_dias DAY)
              WHERE id = ?"
        )->execute([$id]);
    }

    public function eliminar(int $id): void
    {
        $this->db->prepare("DELETE FROM pedidos_recurrentes WHERE id = ?")->execute([$id]);
    }

    public function cambiarFrecuencia(int $id, int $frecuenciaDias): void
    {
        $this->db->prepare(
            "UPDATE pedidos_recurrentes
                SET frecuencia_dias = ?, proximo_recordatorio_en = DATE_ADD(CURDATE(), INTERVAL ? DAY)
              WHERE id = ?"
        )->execute([$frecuenciaDias, $frecuenciaDias, $id]);
    }

    /* --------------------------------- Cron: recordatorios ------------ */

    /** Plantillas activas cuyo recordatorio ya toca enviarse. */
    public function pendientesDeAviso(): array
    {
        return $this->db->query(
            "SELECT pr.*, u.nombre AS cliente_nombre, u.email AS cliente_email, u.activo AS cliente_activo
               FROM pedidos_recurrentes pr
               JOIN usuarios u ON u.id = pr.usuario_id
              WHERE pr.activo = 1 AND pr.proximo_recordatorio_en <= CURDATE()"
        )->fetchAll();
    }

    /** Marca el aviso enviado y reprograma la siguiente fecha. */
    public function marcarAvisado(int $id): void
    {
        $this->db->prepare(
            "UPDATE pedidos_recurrentes
                SET ultimo_recordatorio_en = NOW(),
                    proximo_recordatorio_en = DATE_ADD(CURDATE(), INTERVAL frecuencia_dias DAY)
              WHERE id = ?"
        )->execute([$id]);
    }
}
