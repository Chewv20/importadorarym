<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Encuesta de experiencia de pedido (opinión del cliente sobre el proceso).
 * Una respuesta por pedido.
 */
class EncuestaPedido extends Model
{
    /** Estados de pedido en los que ya se puede calificar la experiencia. */
    public const ESTADOS_CALIFICABLES = ['enviado', 'en_proceso', 'sincronizado'];

    public function crear(array $d): int
    {
        $this->db->prepare(
            "INSERT INTO encuestas_pedido (pedido_id, usuario_id, satisfaccion, facilidad, nps, comentario)
             VALUES (:pedido, :usuario, :sat, :fac, :nps, :com)"
        )->execute([
            ':pedido'  => (int) $d['pedido_id'],
            ':usuario' => $d['usuario_id'] ?? null,
            ':sat'     => (int) $d['satisfaccion'],
            ':fac'     => (int) $d['facilidad'],
            ':nps'     => isset($d['nps']) && $d['nps'] !== null ? (int) $d['nps'] : null,
            ':com'     => ($d['comentario'] ?? '') ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Encuesta de un pedido, o null si aún no ha sido calificado. */
    public function porPedido(int $pedidoId): ?array
    {
        $st = $this->db->prepare("SELECT * FROM encuestas_pedido WHERE pedido_id = ?");
        $st->execute([$pedidoId]);
        return $st->fetch() ?: null;
    }

    /* --------------------------------- Panel ------------------------- */

    private function where(array $f, array &$params): string
    {
        $cond = [];
        if (!empty($f['desde'])) {
            $cond[] = 'e.created_at >= :desde';
            $params[':desde'] = $f['desde'] . ' 00:00:00';
        }
        if (!empty($f['hasta'])) {
            $cond[] = 'e.created_at <= :hasta';
            $params[':hasta'] = $f['hasta'] . ' 23:59:59';
        }
        return $cond ? 'WHERE ' . implode(' AND ', $cond) : '';
    }

    public function paginado(int $limit, int $offset, array $f = []): array
    {
        $params = [];
        $where  = $this->where($f, $params);
        $sql = "SELECT e.*, p.folio AS pedido_folio, u.nombre AS cliente_nombre, u.empresa AS cliente_empresa
                  FROM encuestas_pedido e
                  LEFT JOIN pedidos p ON p.id = e.pedido_id
                  LEFT JOIN usuarios u ON u.id = e.usuario_id
                {$where}
                ORDER BY e.created_at DESC, e.id DESC
                LIMIT :lim OFFSET :off";
        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function contar(array $f = []): int
    {
        $params = [];
        $where  = $this->where($f, $params);
        $st = $this->db->prepare("SELECT COUNT(*) FROM encuestas_pedido e {$where}");
        $st->execute($params);
        return (int) $st->fetchColumn();
    }

    /**
     * Métricas agregadas: total, promedios, NPS y distribución de satisfacción.
     * @return array{total:int, prom_satisfaccion:float, prom_facilidad:float, nps:?int, con_nps:int, distribucion:array<int,int>}
     */
    public function metricas(array $f = []): array
    {
        $params = [];
        $where  = $this->where($f, $params);

        $st = $this->db->prepare(
            "SELECT COUNT(*) total,
                    AVG(e.satisfaccion) prom_sat,
                    AVG(e.facilidad) prom_fac,
                    SUM(e.nps IS NOT NULL) con_nps,
                    SUM(e.nps >= 9) promotores,
                    SUM(e.nps BETWEEN 0 AND 6) detractores
               FROM encuestas_pedido e {$where}"
        );
        $st->execute($params);
        $r = $st->fetch() ?: [];

        $total  = (int) ($r['total'] ?? 0);
        $conNps = (int) ($r['con_nps'] ?? 0);
        $nps = null;
        if ($conNps > 0) {
            $nps = (int) round((((int) $r['promotores'] - (int) $r['detractores']) / $conNps) * 100);
        }

        // Distribución de satisfacción (1-5), con 0 donde no haya.
        $dist = array_fill(1, 5, 0);
        $ds = $this->db->prepare("SELECT e.satisfaccion s, COUNT(*) c FROM encuestas_pedido e {$where} GROUP BY e.satisfaccion");
        $ds->execute($params);
        foreach ($ds->fetchAll() as $row) {
            $n = (int) $row['s'];
            if ($n >= 1 && $n <= 5) {
                $dist[$n] = (int) $row['c'];
            }
        }

        return [
            'total'             => $total,
            'prom_satisfaccion' => round((float) ($r['prom_sat'] ?? 0), 2),
            'prom_facilidad'    => round((float) ($r['prom_fac'] ?? 0), 2),
            'nps'               => $nps,
            'con_nps'           => $conNps,
            'distribucion'      => $dist,
        ];
    }

    /** Filas máximas de una exportación (evita agotar la memoria con el histórico). */
    public const MAX_EXPORT = 10000;

    public function exportar(array $f = []): array
    {
        $params = [];
        $where  = $this->where($f, $params);
        $sql = "SELECT e.*, p.folio AS pedido_folio, u.nombre AS cliente_nombre, u.empresa AS cliente_empresa
                  FROM encuestas_pedido e
                  LEFT JOIN pedidos p ON p.id = e.pedido_id
                  LEFT JOIN usuarios u ON u.id = e.usuario_id
                {$where}
                ORDER BY e.created_at DESC
                LIMIT " . self::MAX_EXPORT;
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }
}
