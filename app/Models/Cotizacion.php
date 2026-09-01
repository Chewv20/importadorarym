<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Cotizacion extends Model
{
    /** Ciclo de vida: nueva -> cotizada -> aprobada/rechazada -> convertida. */
    public const ESTADOS = ['nueva', 'cotizada', 'aprobada', 'rechazada', 'convertida'];

    /** Tope de filas en exportaciones a Excel (mismo criterio que Visita/EncuestaPedido). */
    public const MAX_EXPORT = 10000;

    public function crear(array $data): int
    {
        $sql = "INSERT INTO cotizaciones
                    (usuario_id, nombre, empresa, email, telefono, producto_interes, mensaje, requiere_impresion, logo_archivo, origen, ip, user_agent)
                VALUES
                    (:usuario_id, :nombre, :empresa, :email, :telefono, :producto_interes, :mensaje, :requiere_impresion, :logo_archivo, :origen, :ip, :user_agent)";
        $this->db->prepare($sql)->execute([
            ':usuario_id'         => $data['usuario_id'] ?? null,
            ':nombre'             => $data['nombre'],
            ':empresa'            => $data['empresa'] ?? null,
            ':email'              => $data['email'],
            ':telefono'           => $data['telefono'] ?? null,
            ':producto_interes'   => $data['producto_interes'] ?? null,
            ':mensaje'            => $data['mensaje'] ?? null,
            ':requiere_impresion' => !empty($data['requiere_impresion']) ? 1 : 0,
            ':logo_archivo'       => $data['logo_archivo'] ?? null,
            ':origen'             => $data['origen'] ?? 'landing',
            ':ip'                 => $data['ip'] ?? null,
            ':user_agent'         => $data['user_agent'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Genera el folio COT-00001 a partir del id. */
    public function generarFolio(int $id): string
    {
        $folio = 'COT-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
        $this->db->prepare("UPDATE cotizaciones SET folio = ? WHERE id = ?")->execute([$folio, $id]);
        return $folio;
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM cotizaciones WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /**
     * vendedor_id del cliente dueño de la cotización, o null si es pública
     * (sin usuario_id) o el cliente no tiene vendedor. Para control de acceso.
     */
    public function vendedorDe(int $id): ?int
    {
        $st = $this->db->prepare(
            "SELECT u.vendedor_id FROM cotizaciones c
               JOIN usuarios u ON u.id = c.usuario_id
              WHERE c.id = ?"
        );
        $st->execute([$id]);
        $v = $st->fetchColumn();
        return $v !== false && $v !== null ? (int) $v : null;
    }

    /* --------------------------------------- Partidas (items) -------- */

    public function items(int $cotizacionId): array
    {
        $st = $this->db->prepare(
            "SELECT *, (cantidad * precio_unitario) AS importe
               FROM cotizacion_items WHERE cotizacion_id = ? ORDER BY id"
        );
        $st->execute([$cotizacionId]);
        return $st->fetchAll();
    }

    public function agregarItems(int $cotizacionId, array $items): void
    {
        if (!$items) {
            return;
        }
        $st = $this->db->prepare(
            "INSERT INTO cotizacion_items (cotizacion_id, producto_id, descripcion, cantidad, precio_unitario)
             VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($items as $it) {
            $st->execute([
                $cotizacionId,
                ($it['producto_id'] ?? null) ?: null,
                mb_substr((string) $it['descripcion'], 0, 200),
                max(1, (int) $it['cantidad']),
                round((float) ($it['precio_unitario'] ?? 0), 2),
            ]);
        }
    }

    /** Reemplaza todas las partidas (edición desde el panel). */
    public function reemplazarItems(int $cotizacionId, array $items): void
    {
        $this->db->prepare("DELETE FROM cotizacion_items WHERE cotizacion_id = ?")->execute([$cotizacionId]);
        $this->agregarItems($cotizacionId, $items);
    }

    /** Actualiza cantidad y precio de las partidas por id (edición del panel). */
    public function actualizarLineas(int $cotizacionId, array $lineas): void
    {
        $st = $this->db->prepare(
            "UPDATE cotizacion_items SET cantidad = ?, precio_unitario = ?
             WHERE id = ? AND cotizacion_id = ?"
        );
        foreach ($lineas as $itemId => $l) {
            $st->execute([
                max(1, (int) ($l['cantidad'] ?? 1)),
                max(0, round((float) ($l['precio'] ?? 0), 2)),
                (int) $itemId,
                $cotizacionId,
            ]);
        }
    }

    public function eliminarItem(int $itemId, int $cotizacionId): void
    {
        $this->db->prepare("DELETE FROM cotizacion_items WHERE id = ? AND cotizacion_id = ?")
            ->execute([$itemId, $cotizacionId]);
    }

    /** Recalcula subtotal/IVA/total desde las partidas y los guarda. */
    public function recalcular(int $id, float $tasaIva): array
    {
        $sub = 0.0;
        foreach ($this->items($id) as $it) {
            $sub += (float) $it['importe'];
        }
        $iva = round($sub * $tasaIva / 100, 2);
        $total = round($sub + $iva, 2);
        $this->guardarTotales($id, $sub, $iva, $total);
        return ['subtotal' => round($sub, 2), 'iva' => $iva, 'total' => $total];
    }

    /* --------------------------------------- Totales y estado -------- */

    public function guardarTotales(int $id, float $subtotal, float $iva, float $total): void
    {
        $this->db->prepare(
            "UPDATE cotizaciones SET subtotal = ?, iva = ?, total = ? WHERE id = ?"
        )->execute([round($subtotal, 2), round($iva, 2), round($total, 2), $id]);
    }

    public function actualizarEstado(int $id, string $estado): void
    {
        $this->db->prepare("UPDATE cotizaciones SET estado = ? WHERE id = ?")->execute([$estado, $id]);
    }

    public function marcarEnviada(int $id): void
    {
        $this->db->prepare(
            "UPDATE cotizaciones SET estado = 'cotizada', enviada_en = NOW() WHERE id = ?"
        )->execute([$id]);
    }

    public function vincularPedido(int $id, int $pedidoId): void
    {
        $this->db->prepare(
            "UPDATE cotizaciones SET pedido_id = ?, estado = 'convertida' WHERE id = ?"
        )->execute([$pedidoId, $id]);
    }

    /* --------------------------------------- Listados ---------------- */

    /**
     * Une con el cliente para poder filtrar por vendedor. El INNER JOIN excluye
     * las cotizaciones públicas (sin usuario_id), que no tienen vendedor.
     */
    private function joinVendedor(?int $vendedorId): string
    {
        return $vendedorId !== null
            ? ' JOIN usuarios u ON u.id = c.usuario_id AND u.vendedor_id = :vend'
            : '';
    }

    public function paginado(int $limit, int $offset, ?string $estado = null, ?int $vendedorId = null): array
    {
        $join = $this->joinVendedor($vendedorId);
        $where = $estado !== null ? 'WHERE c.estado = :estado' : '';
        $sql = "SELECT c.* FROM cotizaciones c {$join} {$where} ORDER BY c.created_at DESC LIMIT :lim OFFSET :off";
        $st = $this->db->prepare($sql);
        if ($estado !== null) {
            $st->bindValue(':estado', $estado);
        }
        if ($vendedorId !== null) {
            $st->bindValue(':vend', $vendedorId, PDO::PARAM_INT);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function contar(?string $estado = null, ?int $vendedorId = null): int
    {
        $join = $this->joinVendedor($vendedorId);
        $where = $estado !== null ? 'WHERE c.estado = :estado' : '';
        $st = $this->db->prepare("SELECT COUNT(*) FROM cotizaciones c {$join} {$where}");
        if ($estado !== null) {
            $st->bindValue(':estado', $estado);
        }
        if ($vendedorId !== null) {
            $st->bindValue(':vend', $vendedorId, PDO::PARAM_INT);
        }
        $st->execute();
        return (int) $st->fetchColumn();
    }

    /** Últimas cotizaciones (para el dashboard). */
    public function recientes(int $limit = 6, ?int $vendedorId = null): array
    {
        $join = $this->joinVendedor($vendedorId);
        $st = $this->db->prepare("SELECT c.* FROM cotizaciones c {$join} ORDER BY c.created_at DESC LIMIT :lim");
        if ($vendedorId !== null) {
            $st->bindValue(':vend', $vendedorId, PDO::PARAM_INT);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /** Conteo por estado (todos los estados, con 0 donde no haya). */
    public function contarPorEstado(?int $vendedorId = null): array
    {
        $join = $this->joinVendedor($vendedorId);
        $st = $this->db->prepare("SELECT c.estado AS estado, COUNT(*) AS cnt FROM cotizaciones c {$join} GROUP BY c.estado");
        if ($vendedorId !== null) {
            $st->bindValue(':vend', $vendedorId, PDO::PARAM_INT);
        }
        $st->execute();
        $map = array_fill_keys(self::ESTADOS, 0);
        foreach ($st->fetchAll() as $r) {
            if (isset($map[$r['estado']])) {
                $map[$r['estado']] = (int) $r['cnt'];
            }
        }
        return $map;
    }

    /** Cotizaciones/leads por semana ISO de las últimas N semanas (buckets con 0). */
    public function porSemana(int $semanas = 8, ?int $vendedorId = null): array
    {
        $join = $this->joinVendedor($vendedorId);
        $st = $this->db->prepare(
            "SELECT YEARWEEK(c.created_at, 3) AS yw, COUNT(*) AS cnt
               FROM cotizaciones c {$join}
              WHERE c.created_at >= DATE_SUB(CURDATE(), INTERVAL :semanas WEEK)
              GROUP BY yw"
        );
        if ($vendedorId !== null) {
            $st->bindValue(':vend', $vendedorId, PDO::PARAM_INT);
        }
        $st->bindValue(':semanas', $semanas, PDO::PARAM_INT);
        $st->execute();
        $map = [];
        foreach ($st->fetchAll() as $r) {
            $map[(int) $r['yw']] = (int) $r['cnt'];
        }

        $lunes = strtotime('monday this week');
        $out = [];
        for ($i = $semanas - 1; $i >= 0; $i--) {
            $ts = $lunes - $i * 7 * 86400;
            $yw = (int) date('oW', $ts);
            $out[] = ['label' => date('d/m', $ts), 'valor' => $map[$yw] ?? 0];
        }
        return $out;
    }

    /* --------------------------------------- Reportes ---------------- */

    /** Cotizaciones/leads en un rango de fechas, opcionalmente por estado y vendedor. */
    public function reporteLeads(string $desde, string $hasta, ?string $estado = null, ?int $vendedorId = null): array
    {
        $join = $this->joinVendedor($vendedorId);
        $sql = "SELECT c.folio, c.created_at, c.nombre, c.empresa, c.email, c.telefono,
                       c.origen, c.estado, c.total
                  FROM cotizaciones c {$join}
                 WHERE c.created_at BETWEEN :desde AND :hasta";
        if ($estado !== null) $sql .= " AND c.estado = :estado";
        $sql .= " ORDER BY c.created_at DESC LIMIT " . self::MAX_EXPORT;

        $st = $this->db->prepare($sql);
        $st->bindValue(':desde', $desde);
        $st->bindValue(':hasta', $hasta);
        if ($estado !== null)     $st->bindValue(':estado', $estado);
        if ($vendedorId !== null) $st->bindValue(':vend', $vendedorId, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /** Cotizaciones de un cliente (portal). */
    /** Cotizaciones del cliente, de la más reciente a la más antigua (acotado). */
    public function porUsuario(int $usuarioId, int $limite = 200): array
    {
        $st = $this->db->prepare(
            "SELECT * FROM cotizaciones WHERE usuario_id = :uid ORDER BY created_at DESC LIMIT :lim"
        );
        $st->bindValue(':uid', $usuarioId, PDO::PARAM_INT);
        $st->bindValue(':lim', $limite, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }
}
