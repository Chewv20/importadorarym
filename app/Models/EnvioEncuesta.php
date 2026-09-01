<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Encuesta de satisfacción de la ENTREGA física (opinión del cliente sobre el
 * envío que se marcó "entregado" — Fase 7.8). Distinta de EncuestaPedido
 * (satisfacción del proceso de compra, una por pedido completo): esta es una
 * respuesta por envío, así un pedido repartido en partes puede tener varias.
 */
class EnvioEncuesta extends Model
{
    public function crear(array $d): int
    {
        $this->db->prepare(
            "INSERT INTO envio_encuestas (envio_id, usuario_id, llego_completo, que_falto, satisfaccion, comentario)
             VALUES (:envio, :usuario, :completo, :falto, :sat, :com)"
        )->execute([
            ':envio'    => (int) $d['envio_id'],
            ':usuario'  => $d['usuario_id'] ?? null,
            ':completo' => (int) (bool) $d['llego_completo'],
            ':falto'    => ($d['que_falto'] ?? '') ?: null,
            ':sat'      => (int) $d['satisfaccion'],
            ':com'      => ($d['comentario'] ?? '') ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Encuesta de un envío, o null si aún no se ha calificado. */
    public function porEnvio(int $envioId): ?array
    {
        $st = $this->db->prepare("SELECT * FROM envio_encuestas WHERE envio_id = ?");
        $st->execute([$envioId]);
        return $st->fetch() ?: null;
    }

    /* --------------------------------- Panel ------------------------- */

    private function where(array $f, array &$params): string
    {
        $cond = [];
        if (!empty($f['desde'])) {
            $cond[] = 'ee.created_at >= :desde';
            $params[':desde'] = $f['desde'] . ' 00:00:00';
        }
        if (!empty($f['hasta'])) {
            $cond[] = 'ee.created_at <= :hasta';
            $params[':hasta'] = $f['hasta'] . ' 23:59:59';
        }
        return $cond ? 'WHERE ' . implode(' AND ', $cond) : '';
    }

    private const SELECT_BASE =
        "SELECT ee.*, p.folio AS pedido_folio, u.nombre AS cliente_nombre, u.empresa AS cliente_empresa,
                (SELECT COUNT(*) FROM pedido_items WHERE envio_id = ee.envio_id) AS num_partidas
           FROM envio_encuestas ee
           LEFT JOIN pedido_envios pe ON pe.id = ee.envio_id
           LEFT JOIN pedidos p ON p.id = pe.pedido_id
           LEFT JOIN usuarios u ON u.id = ee.usuario_id";

    public function paginado(int $limit, int $offset, array $f = []): array
    {
        $params = [];
        $where  = $this->where($f, $params);
        $sql = self::SELECT_BASE . " {$where} ORDER BY ee.created_at DESC, ee.id DESC LIMIT :lim OFFSET :off";
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
        $st = $this->db->prepare("SELECT COUNT(*) FROM envio_encuestas ee {$where}");
        $st->execute($params);
        return (int) $st->fetchColumn();
    }

    /**
     * Métricas agregadas: total, promedio de satisfacción, % que llegó completo
     * y distribución de satisfacción.
     * @return array{total:int, prom_satisfaccion:float, pct_completo:?float, distribucion:array<int,int>}
     */
    public function metricas(array $f = []): array
    {
        $params = [];
        $where  = $this->where($f, $params);

        $st = $this->db->prepare(
            "SELECT COUNT(*) total, AVG(ee.satisfaccion) prom_sat, AVG(ee.llego_completo) pct_completo
               FROM envio_encuestas ee {$where}"
        );
        $st->execute($params);
        $r = $st->fetch() ?: [];
        $total = (int) ($r['total'] ?? 0);

        $dist = array_fill(1, 5, 0);
        $ds = $this->db->prepare("SELECT ee.satisfaccion s, COUNT(*) c FROM envio_encuestas ee {$where} GROUP BY ee.satisfaccion");
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
            'pct_completo'      => $total > 0 ? round(((float) $r['pct_completo']) * 100, 1) : null,
            'distribucion'      => $dist,
        ];
    }

    /** Filas máximas de una exportación (evita agotar la memoria con el histórico). */
    public const MAX_EXPORT = 10000;

    public function exportar(array $f = []): array
    {
        $params = [];
        $where  = $this->where($f, $params);
        $sql = self::SELECT_BASE . " {$where} ORDER BY ee.created_at DESC LIMIT " . self::MAX_EXPORT;
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }
}
