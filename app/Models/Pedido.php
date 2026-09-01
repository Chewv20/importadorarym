<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Pedido extends Model
{
    public const ESTADOS = ['borrador', 'enviado', 'en_proceso', 'parcial', 'sincronizado', 'cancelado'];

    /** Tope de filas en exportaciones a Excel (mismo criterio que Visita/EncuestaPedido). */
    public const MAX_EXPORT = 10000;
    /**
     * Crea un pedido con sus items dentro de una transacción y devuelve su id.
     *
     * @param array $items Cada item: ['producto_id','sku','nombre','cantidad','precio_unitario']
     */
    public function crear(int $usuarioId, array $items, ?string $notas = null, string $estado = 'borrador', ?string $referencia = null): int
    {
        $this->db->beginTransaction();

        try {
            $folio = $this->generarFolio();

            $this->db->prepare(
                "INSERT INTO pedidos (usuario_id, folio, referencia_cliente, estado, notas) VALUES (?, ?, ?, ?, ?)"
            )->execute([$usuarioId, $folio, $referencia, $estado, $notas]);

            $pedidoId = (int) $this->db->lastInsertId();

            $stItem = $this->db->prepare(
                "INSERT INTO pedido_items (pedido_id, producto_id, sku, nombre, cantidad, precio_unitario, importe)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $subtotal = 0.0;
            foreach ($items as $item) {
                $cantidad = (int) ($item['cantidad'] ?? 1);
                $precio   = (float) ($item['precio_unitario'] ?? 0);
                $importe  = $cantidad * $precio;
                $subtotal += $importe;

                $stItem->execute([
                    $pedidoId,
                    $item['producto_id'] ?? null,
                    $item['sku'] ?? null,
                    $item['nombre'],
                    $cantidad,
                    $precio,
                    $importe,
                ]);
            }

            $this->db->prepare("UPDATE pedidos SET subtotal = ?, total = ? WHERE id = ?")
                     ->execute([$subtotal, $subtotal, $pedidoId]);

            $this->db->commit();
            return $pedidoId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM pedidos WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function items(int $pedidoId): array
    {
        $st = $this->db->prepare("SELECT * FROM pedido_items WHERE pedido_id = ? ORDER BY id");
        $st->execute([$pedidoId]);
        return $st->fetchAll();
    }

    /** Pedidos del cliente, del más reciente al más antiguo (acotado). */
    public function porUsuario(int $usuarioId, int $limite = 200): array
    {
        $st = $this->db->prepare(
            "SELECT * FROM pedidos WHERE usuario_id = :uid ORDER BY created_at DESC LIMIT :lim"
        );
        $st->bindValue(':uid', $usuarioId, PDO::PARAM_INT);
        $st->bindValue(':lim', $limite, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function contar(?int $vendedorId = null): int
    {
        if ($vendedorId === null) {
            return (int) $this->db->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
        }
        $st = $this->db->prepare(
            "SELECT COUNT(*) FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id WHERE u.vendedor_id = ?"
        );
        $st->execute([$vendedorId]);
        return (int) $st->fetchColumn();
    }

    /* --------------------------------- Admin ------------------------- */

    /** Etapas del flujo (cancelado es terminal aparte). */
    public const FLUJO = ['borrador', 'enviado', 'en_proceso', 'sincronizado'];

    private function filtros(?string $estado, ?string $busqueda, ?int $vendedorId = null): array
    {
        $cond = [];
        $params = [];
        if ($estado !== null) {
            $cond[] = 'p.estado = :estado';
            $params[':estado'] = $estado;
        }
        if ($busqueda !== null && $busqueda !== '') {
            $cond[] = '(p.folio LIKE :q OR u.nombre LIKE :q OR u.empresa LIKE :q)';
            $params[':q'] = '%' . $busqueda . '%';
        }
        if ($vendedorId !== null) {
            $cond[] = 'u.vendedor_id = :vend';
            $params[':vend'] = $vendedorId;
        }
        return [$cond ? 'WHERE ' . implode(' AND ', $cond) : '', $params];
    }

    /** vendedor_id del cliente dueño del pedido, o null. (control de acceso). */
    public function vendedorDe(int $pedidoId): ?int
    {
        $st = $this->db->prepare(
            "SELECT u.vendedor_id FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id WHERE p.id = ?"
        );
        $st->execute([$pedidoId]);
        $v = $st->fetchColumn();
        return $v !== false && $v !== null ? (int) $v : null;
    }

    public function adminPaginado(int $limit, int $offset, ?string $estado = null, ?string $busqueda = null, ?int $vendedorId = null): array
    {
        [$where, $params] = $this->filtros($estado, $busqueda, $vendedorId);
        $sql = "SELECT p.*, u.nombre AS cliente_nombre, u.empresa AS cliente_empresa,
                       (SELECT COUNT(*) FROM pedido_items WHERE pedido_id = p.id) AS num_items
                  FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id
                {$where}
                ORDER BY p.created_at DESC LIMIT :lim OFFSET :off";
        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function contarAdmin(?string $estado = null, ?string $busqueda = null, ?int $vendedorId = null): int
    {
        [$where, $params] = $this->filtros($estado, $busqueda, $vendedorId);
        $st = $this->db->prepare("SELECT COUNT(*) FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id {$where}");
        $st->execute($params);
        return (int) $st->fetchColumn();
    }

    public function findConCliente(int $id): ?array
    {
        $st = $this->db->prepare(
            "SELECT p.*, u.nombre AS cliente_nombre, u.empresa AS cliente_empresa,
                    u.email AS cliente_email, u.telefono AS cliente_telefono, u.rfc AS cliente_rfc,
                    u.clave_sae AS cliente_clave_sae,
                    v.clave_vendedor AS vendedor_clave, v.comision AS vendedor_comision, v.nombre AS vendedor_nombre
               FROM pedidos p
               JOIN usuarios u ON u.id = p.usuario_id
               LEFT JOIN usuarios v ON v.id = u.vendedor_id
              WHERE p.id = ?"
        );
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $this->db->prepare("UPDATE pedidos SET estado = ? WHERE id = ?")->execute([$estado, $id]);
    }

    /** Partidas de un pedido con la clave SAE y el precio de catálogo del producto. */
    public function itemsParaSae(int $pedidoId): array
    {
        $st = $this->db->prepare(
            "SELECT pi.*, pr.clave_sae, pr.esquema_impuestos, pr.precio AS precio_catalogo
               FROM pedido_items pi
               LEFT JOIN productos pr ON pr.id = pi.producto_id
              WHERE pi.pedido_id = ? ORDER BY pi.id"
        );
        $st->execute([$pedidoId]);
        return $st->fetchAll();
    }

    /**
     * Partidas de un pedido que todavía no se exportaron a SAE (sin envío
     * asignado) — para la selección de partidas al exportar (Fase 7.5).
     */
    public function itemsPendientesSae(int $pedidoId): array
    {
        $st = $this->db->prepare(
            "SELECT pi.*, pr.clave_sae, pr.esquema_impuestos, pr.precio AS precio_catalogo
               FROM pedido_items pi
               LEFT JOIN productos pr ON pr.id = pi.producto_id
              WHERE pi.pedido_id = ? AND pi.envio_id IS NULL ORDER BY pi.id"
        );
        $st->execute([$pedidoId]);
        return $st->fetchAll();
    }

    /**
     * Partidas pendientes de varios pedidos en UNA consulta, agrupadas por
     * pedido_id (evita N+1 en la exportación por lote a SAE).
     */
    public function itemsPendientesSaePorPedidos(array $pedidoIds): array
    {
        $pedidoIds = array_values(array_unique(array_map('intval', $pedidoIds)));
        if (!$pedidoIds) {
            return [];
        }
        $ph = implode(',', array_fill(0, count($pedidoIds), '?'));
        $st = $this->db->prepare(
            "SELECT pi.*, pr.clave_sae, pr.esquema_impuestos, pr.precio AS precio_catalogo
               FROM pedido_items pi
               LEFT JOIN productos pr ON pr.id = pi.producto_id
              WHERE pi.pedido_id IN ($ph) AND pi.envio_id IS NULL ORDER BY pi.pedido_id, pi.id"
        );
        $st->execute($pedidoIds);

        $out = [];
        foreach ($st->fetchAll() as $row) {
            $out[(int) $row['pedido_id']][] = $row;
        }
        return $out;
    }

    /**
     * Partidas ya asignadas a un envío concreto — para reconstruir el Excel de
     * SAE si la descarga original se interrumpió (mismos datos que se usaron
     * al exportar, ya que el precio y la clave quedaron fijos en ese momento).
     */
    public function itemsDeEnvio(int $envioId): array
    {
        $st = $this->db->prepare(
            "SELECT pi.*, pr.clave_sae, pr.esquema_impuestos, pr.precio AS precio_catalogo
               FROM pedido_items pi
               LEFT JOIN productos pr ON pr.id = pi.producto_id
              WHERE pi.envio_id = ? ORDER BY pi.id"
        );
        $st->execute([$envioId]);
        return $st->fetchAll();
    }

    /**
     * Guarda el precio unitario de cada partida y recalcula los totales.
     * @param array<int,float> $precios  item_id => precio
     */
    public function guardarPrecios(int $pedidoId, array $precios): void
    {
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare(
                "UPDATE pedido_items SET precio_unitario = ?, importe = cantidad * ? WHERE id = ? AND pedido_id = ?"
            );
            foreach ($precios as $itemId => $precio) {
                $p = round((float) $precio, 2);
                $st->execute([$p, $p, (int) $itemId, $pedidoId]);
            }
            $sum = $this->db->prepare("SELECT COALESCE(SUM(importe),0) FROM pedido_items WHERE pedido_id = ?");
            $sum->execute([$pedidoId]);
            $total = (float) $sum->fetchColumn();
            $this->db->prepare("UPDATE pedidos SET subtotal = ?, total = ? WHERE id = ?")
                     ->execute([$total, $total, $pedidoId]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Guarda precios de varias partidas (posiblemente de distintos pedidos)
     * y recalcula los totales de cada pedido afectado.
     * @param array<int,float> $precios item_id => precio
     */
    public function guardarPreciosItems(array $precios): void
    {
        if (!$precios) {
            return;
        }
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare("UPDATE pedido_items SET precio_unitario = ?, importe = cantidad * ? WHERE id = ?");
            foreach ($precios as $itemId => $precio) {
                $p = round((float) $precio, 2);
                $st->execute([$p, $p, (int) $itemId]);
            }

            $ids = implode(',', array_map('intval', array_keys($precios)));
            $pedidoIds = $this->db->query("SELECT DISTINCT pedido_id FROM pedido_items WHERE id IN ($ids)")
                ->fetchAll(PDO::FETCH_COLUMN);

            $up = $this->db->prepare(
                "UPDATE pedidos SET
                    subtotal = (SELECT COALESCE(SUM(importe),0) FROM pedido_items WHERE pedido_id = ?),
                    total    = (SELECT COALESCE(SUM(importe),0) FROM pedido_items WHERE pedido_id = ?)
                 WHERE id = ?"
            );
            foreach ($pedidoIds as $pid) {
                $up->execute([(int) $pid, (int) $pid, (int) $pid]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** ¿Todas las partidas del pedido tienen precio capturado (> 0)? */
    public function tienePrecios(int $pedidoId): bool
    {
        $st = $this->db->prepare(
            "SELECT COUNT(*) FROM pedido_items WHERE pedido_id = ? AND (precio_unitario IS NULL OR precio_unitario <= 0)"
        );
        $st->execute([$pedidoId]);
        return (int) $st->fetchColumn() === 0;
    }

    /** Pedidos con al menos una partida pendiente de exportar (para exportación en lote). */
    public function pendientesSae(): array
    {
        return $this->db->query(
            "SELECT p.*, u.nombre AS cliente_nombre, u.clave_sae AS cliente_clave_sae,
                    v.clave_vendedor AS vendedor_clave, v.comision AS vendedor_comision
               FROM pedidos p
               JOIN usuarios u ON u.id = p.usuario_id
               LEFT JOIN usuarios v ON v.id = u.vendedor_id
              WHERE p.estado IN ('enviado', 'parcial') AND p.erp_sincronizado = 0
                AND EXISTS (SELECT 1 FROM pedido_items pi WHERE pi.pedido_id = p.id AND pi.envio_id IS NULL)
              ORDER BY p.created_at"
        )->fetchAll();
    }

    /**
     * Pedidos aún no enviados al ERP (para el proceso de sincronización).
     */
    public function pendientesErp(): array
    {
        return $this->db->query(
            "SELECT * FROM pedidos WHERE estado = 'enviado' AND erp_sincronizado = 0 ORDER BY created_at"
        )->fetchAll();
    }

    public function marcarSincronizado(int $pedidoId, string $erpFolio): void
    {
        $this->db->prepare(
            "UPDATE pedidos
                SET estado = 'sincronizado', erp_sincronizado = 1,
                    erp_folio = ?, erp_sincronizado_at = NOW(), erp_error = NULL
              WHERE id = ?"
        )->execute([$erpFolio, $pedidoId]);
    }

    /** Conteo por estado (todos los estados, con 0 donde no haya). */
    public function contarPorEstado(?int $vendedorId = null): array
    {
        if ($vendedorId === null) {
            $rows = $this->db->query("SELECT estado, COUNT(*) c FROM pedidos GROUP BY estado")->fetchAll();
        } else {
            $st = $this->db->prepare(
                "SELECT p.estado, COUNT(*) c FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id
                  WHERE u.vendedor_id = ? GROUP BY p.estado"
            );
            $st->execute([$vendedorId]);
            $rows = $st->fetchAll();
        }
        $map = array_fill_keys(self::ESTADOS, 0);
        foreach ($rows as $r) {
            $map[$r['estado']] = (int) $r['c'];
        }
        return $map;
    }

    /* --------------------------------- Reportes ---------------------- */

    /** Pedidos en un rango de fechas (con cliente), opcionalmente por estado y vendedor. */
    public function reportePedidos(string $desde, string $hasta, ?string $estado = null, ?int $vendedorId = null): array
    {
        $sql = "SELECT p.folio, p.created_at, u.nombre AS cliente, u.empresa,
                       p.estado,
                       (SELECT COUNT(*) FROM pedido_items WHERE pedido_id = p.id) AS num_items,
                       p.total, p.erp_folio
                  FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id
                 WHERE p.created_at BETWEEN :desde AND :hasta";
        if ($estado !== null)     $sql .= " AND p.estado = :estado";
        if ($vendedorId !== null) $sql .= " AND u.vendedor_id = :vend";
        $sql .= " ORDER BY p.created_at DESC LIMIT " . self::MAX_EXPORT;

        $st = $this->db->prepare($sql);
        $st->bindValue(':desde', $desde);
        $st->bindValue(':hasta', $hasta);
        if ($estado !== null)     $st->bindValue(':estado', $estado);
        if ($vendedorId !== null) $st->bindValue(':vend', $vendedorId, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /** Ranking de productos más pedidos en el rango (excluye cancelados). */
    public function reporteProductos(string $desde, string $hasta, ?int $vendedorId = null): array
    {
        $sql = "SELECT pi.sku, pi.nombre,
                       SUM(pi.cantidad) AS cantidad,
                       COUNT(DISTINCT pi.pedido_id) AS pedidos
                  FROM pedido_items pi
                  JOIN pedidos p ON p.id = pi.pedido_id
                  JOIN usuarios u ON u.id = p.usuario_id
                 WHERE p.created_at BETWEEN :desde AND :hasta AND p.estado <> 'cancelado'";
        if ($vendedorId !== null) $sql .= " AND u.vendedor_id = :vend";
        $sql .= " GROUP BY pi.producto_id, pi.sku, pi.nombre ORDER BY cantidad DESC, pedidos DESC LIMIT " . self::MAX_EXPORT;

        $st = $this->db->prepare($sql);
        $st->bindValue(':desde', $desde);
        $st->bindValue(':hasta', $hasta);
        if ($vendedorId !== null) $st->bindValue(':vend', $vendedorId, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /** Ranking de clientes por total de pedidos en el rango (excluye cancelados). */
    public function reporteClientes(string $desde, string $hasta, ?int $vendedorId = null): array
    {
        $sql = "SELECT u.nombre, u.empresa,
                       COUNT(p.id) AS num_pedidos,
                       COALESCE(SUM(p.total), 0) AS total
                  FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id
                 WHERE p.created_at BETWEEN :desde AND :hasta AND p.estado <> 'cancelado'";
        if ($vendedorId !== null) $sql .= " AND u.vendedor_id = :vend";
        $sql .= " GROUP BY u.id, u.nombre, u.empresa ORDER BY total DESC, num_pedidos DESC LIMIT " . self::MAX_EXPORT;

        $st = $this->db->prepare($sql);
        $st->bindValue(':desde', $desde);
        $st->bindValue(':hasta', $hasta);
        if ($vendedorId !== null) $st->bindValue(':vend', $vendedorId, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /* --------------------------- Historial de exportaciones a SAE ---- */

    public function registrarExportacion(int $pedidoId, string $tipo, ?int $usuarioId, ?string $usuarioEmail): void
    {
        $this->db->prepare(
            "INSERT INTO pedido_exportaciones (pedido_id, usuario_id, usuario_email, tipo)
             VALUES (?, ?, ?, ?)"
        )->execute([$pedidoId, $usuarioId, $usuarioEmail, $tipo]);
    }

    public function exportaciones(int $pedidoId): array
    {
        $st = $this->db->prepare(
            "SELECT * FROM pedido_exportaciones WHERE pedido_id = ? ORDER BY created_at DESC, id DESC"
        );
        $st->execute([$pedidoId]);
        return $st->fetchAll();
    }

    /* --------------------------- Envíos / remesas (Fase 7.5) ---------- */

    /**
     * Crea un envío (remesa) con las partidas indicadas: las marca como
     * exportadas, limpia su recordatorio y ajusta el estado del pedido según
     * queden o no partidas pendientes. Devuelve el id del envío creado.
     */
    public function crearEnvio(
        int $pedidoId,
        array $itemIds,
        int $usuarioId,
        ?string $fechaEntrega = null,
        ?string $saeSerie = null,
        ?int $saeConsecutivo = null
    ): int {
        $itemIds = array_values(array_unique(array_map('intval', $itemIds)));
        if (!$itemIds) {
            throw new \InvalidArgumentException('crearEnvio requiere al menos una partida.');
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                "INSERT INTO pedido_envios (pedido_id, exportado_por, fecha_entrega, sae_serie, sae_consecutivo)
                 VALUES (?, ?, ?, ?, ?)"
            )->execute([$pedidoId, $usuarioId, $fechaEntrega, $saeSerie, $saeConsecutivo]);
            $envioId = (int) $this->db->lastInsertId();

            $ph = implode(',', array_fill(0, count($itemIds), '?'));
            $st = $this->db->prepare(
                "UPDATE pedido_items SET envio_id = ?, exportado_en = NOW(), recordatorio_en = NULL
                  WHERE pedido_id = ? AND envio_id IS NULL AND id IN ({$ph})"
            );
            $st->execute(array_merge([$envioId, $pedidoId], $itemIds));

            if ($st->rowCount() !== count($itemIds)) {
                throw new \RuntimeException(
                    'Alguna de las partidas ya fue exportada por otra persona; recarga el pedido e inténtalo de nuevo.'
                );
            }

            $this->actualizarEstadoPorExportacion($pedidoId);

            $this->db->commit();
            return $envioId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Pasa el pedido a 'parcial' en cuanto queda al menos una partida pendiente
     * junto con al menos una ya exportada, y a 'sincronizado' cuando la última
     * partida pendiente se exporta — pero SOLO si venía de 'parcial': un pedido
     * exportado completo de una sola vez (como hasta ahora) no cambia de estado
     * solo por exportarse, sigue requiriendo el folio de SAE para eso.
     */
    private function actualizarEstadoPorExportacion(int $pedidoId): void
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM pedido_items WHERE pedido_id = ? AND envio_id IS NULL");
        $st->execute([$pedidoId]);
        $pendientes = (int) $st->fetchColumn();

        $pedido = $this->find($pedidoId);
        if (!$pedido || $pedido['estado'] === 'cancelado') {
            return;
        }

        if ($pendientes > 0 && $pedido['estado'] !== 'parcial') {
            $this->cambiarEstado($pedidoId, 'parcial');
        } elseif ($pendientes === 0 && $pedido['estado'] === 'parcial') {
            $this->cambiarEstado($pedidoId, 'sincronizado');
        }
    }

    /** Número de partidas de un pedido aún sin exportar (0 = ya se exportó todo). */
    public function contarPendientesSae(int $pedidoId): int
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM pedido_items WHERE pedido_id = ? AND envio_id IS NULL");
        $st->execute([$pedidoId]);
        return (int) $st->fetchColumn();
    }

    /** Envíos (remesas) de un pedido, del más reciente al más antiguo. */
    public function envios(int $pedidoId): array
    {
        $st = $this->db->prepare(
            "SELECT pe.*, u.email AS exportado_por_email,
                    (SELECT COUNT(*) FROM pedido_items WHERE envio_id = pe.id) AS num_partidas
               FROM pedido_envios pe
               LEFT JOIN usuarios u ON u.id = pe.exportado_por
              WHERE pe.pedido_id = ? ORDER BY pe.created_at DESC, pe.id DESC"
        );
        $st->execute([$pedidoId]);
        return $st->fetchAll();
    }

    /** Programa el recordatorio interno para las partidas aún pendientes del pedido. */
    public function establecerRecordatorio(int $pedidoId, int $dias): void
    {
        $this->db->prepare(
            "UPDATE pedido_items SET recordatorio_en = DATE_ADD(CURDATE(), INTERVAL ? DAY)
              WHERE pedido_id = ? AND envio_id IS NULL"
        )->execute([$dias, $pedidoId]);
    }

    /**
     * Pedidos con partidas pendientes de exportar cuyo recordatorio ya venció
     * (para el cron `database/recordatorio_sae_pendiente.php`).
     */
    public function pedidosConRecordatorioVencido(): array
    {
        return $this->db->query(
            "SELECT p.id, p.folio, u.nombre AS cliente_nombre,
                    COUNT(*) AS partidas_pendientes
               FROM pedido_items pi
               JOIN pedidos p ON p.id = pi.pedido_id
               JOIN usuarios u ON u.id = p.usuario_id
              WHERE pi.envio_id IS NULL AND pi.recordatorio_en IS NOT NULL AND pi.recordatorio_en <= CURDATE()
              GROUP BY p.id, p.folio, u.nombre
              ORDER BY p.created_at"
        )->fetchAll();
    }

    /** Limpia el recordatorio de las partidas pendientes de un pedido (ya se avisó). */
    public function limpiarRecordatorio(int $pedidoId): void
    {
        $this->db->prepare(
            "UPDATE pedido_items SET recordatorio_en = NULL WHERE pedido_id = ? AND envio_id IS NULL"
        )->execute([$pedidoId]);
    }

    /* --------------------------- Tracking de reparto (Fase 7.7) ------- */

    /** Un envío con los datos del pedido/cliente/zona/repartidor para el panel y el reparto. */
    public function envioConDetalle(int $envioId): ?array
    {
        $st = $this->db->prepare(
            "SELECT pe.*, p.folio, p.id AS pedido_id, p.usuario_id,
                    u.nombre AS cliente_nombre, u.telefono AS cliente_telefono,
                    u.email AS cliente_email,
                    z.nombre AS zona_nombre, rep.nombre AS repartidor_nombre
               FROM pedido_envios pe
               JOIN pedidos p ON p.id = pe.pedido_id
               JOIN usuarios u ON u.id = p.usuario_id
               LEFT JOIN zonas z ON z.id = pe.zona_id
               LEFT JOIN usuarios rep ON rep.id = pe.repartidor_id
              WHERE pe.id = ?"
        );
        $st->execute([$envioId]);
        return $st->fetch() ?: null;
    }

    /**
     * Envíos de un pedido para el portal del cliente: con cuántas partidas
     * llevó cada uno y si ya se calificó su entrega (Fase 7.8).
     */
    public function enviosParaPortal(int $pedidoId): array
    {
        $st = $this->db->prepare(
            "SELECT pe.*,
                    (SELECT COUNT(*) FROM pedido_items WHERE envio_id = pe.id) AS num_partidas,
                    ee.id AS encuesta_id, ee.llego_completo AS encuesta_llego_completo,
                    ee.satisfaccion AS encuesta_satisfaccion, ee.que_falto AS encuesta_que_falto,
                    ee.comentario AS encuesta_comentario
               FROM pedido_envios pe
               LEFT JOIN envio_encuestas ee ON ee.envio_id = pe.id
              WHERE pe.pedido_id = ? ORDER BY pe.created_at DESC"
        );
        $st->execute([$pedidoId]);
        return $st->fetchAll();
    }

    /** Asigna (o quita) el repartidor y la zona de un envío. */
    public function asignarRepartidor(int $envioId, ?int $repartidorId, ?int $zonaId, ?string $notas): void
    {
        $this->db->prepare(
            "UPDATE pedido_envios SET repartidor_id = ?, zona_id = ?, notas = ? WHERE id = ?"
        )->execute([$repartidorId, $zonaId, $notas, $envioId]);
    }

    /** Envíos asignados a un repartidor, pendientes de acción (sin entregar aún). */
    public function enviosDeRepartidor(int $repartidorId): array
    {
        $st = $this->db->prepare(
            "SELECT pe.*, p.folio,
                    u.nombre AS cliente_nombre, u.telefono AS cliente_telefono,
                    u.calle, u.numero_ext, u.numero_int, u.colonia, u.delegacion_municipio, u.estado_direccion,
                    z.nombre AS zona_nombre
               FROM pedido_envios pe
               JOIN pedidos p ON p.id = pe.pedido_id
               JOIN usuarios u ON u.id = p.usuario_id
               LEFT JOIN zonas z ON z.id = pe.zona_id
              WHERE pe.repartidor_id = ? AND (pe.evento IS NULL OR pe.evento = 'en_ruta')
              ORDER BY (z.nombre IS NULL), z.nombre, (pe.evento IS NULL) DESC, pe.eta IS NULL, pe.eta, pe.created_at"
        );
        $st->execute([$repartidorId]);
        return $st->fetchAll();
    }

    /** Envíos que un repartidor ya entregó (historial reciente). */
    public function enviosEntregadosDeRepartidor(int $repartidorId, int $limite = 20): array
    {
        $st = $this->db->prepare(
            "SELECT pe.*, p.folio, u.nombre AS cliente_nombre
               FROM pedido_envios pe
               JOIN pedidos p ON p.id = pe.pedido_id
               JOIN usuarios u ON u.id = p.usuario_id
              WHERE pe.repartidor_id = :rep AND pe.evento = 'entregado'
              ORDER BY pe.updated_at DESC LIMIT :lim"
        );
        $st->bindValue(':rep', $repartidorId, PDO::PARAM_INT);
        $st->bindValue(':lim', $limite, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function marcarEnRuta(int $envioId, ?string $eta): void
    {
        $this->db->prepare(
            "UPDATE pedido_envios SET evento = 'en_ruta', eta = ? WHERE id = ?"
        )->execute([$eta ?: null, $envioId]);
    }

    public function marcarEntregado(int $envioId): void
    {
        $this->db->prepare(
            "UPDATE pedido_envios SET evento = 'entregado' WHERE id = ?"
        )->execute([$envioId]);
    }

    private function generarFolio(): string
    {
        return 'RYM-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }
}
