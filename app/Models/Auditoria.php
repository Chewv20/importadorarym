<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Auditoria extends Model
{
    public const TIPOS = ['ingreso', 'cambio'];

    public function registrar(array $d): void
    {
        $sql = "INSERT INTO auditoria
                    (usuario_id, usuario_email, tipo, accion, entidad, entidad_id, descripcion, ip, user_agent)
                VALUES
                    (:usuario_id, :usuario_email, :tipo, :accion, :entidad, :entidad_id, :descripcion, :ip, :user_agent)";
        $this->db->prepare($sql)->execute([
            ':usuario_id'    => $d['usuario_id'] ?? null,
            ':usuario_email' => $d['usuario_email'] ?? null,
            ':tipo'          => $d['tipo'],
            ':accion'        => $d['accion'],
            ':entidad'       => $d['entidad'] ?? null,
            ':entidad_id'    => $d['entidad_id'] ?? null,
            ':descripcion'   => $d['descripcion'] ?? null,
            ':ip'            => $d['ip'] ?? null,
            ':user_agent'    => $d['user_agent'] ?? null,
        ]);
    }

    /**
     * @param array $f Filtros: tipo, usuario (LIKE por email), desde/hasta (Y-m-d).
     */
    public function paginado(int $limit, int $offset, array $f = []): array
    {
        $params = [];
        $where  = $this->where($f, $params);
        $sql = "SELECT * FROM auditoria {$where} ORDER BY created_at DESC, id DESC LIMIT :lim OFFSET :off";
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
        $st = $this->db->prepare("SELECT COUNT(*) FROM auditoria {$where}");
        $st->execute($params);
        return (int) $st->fetchColumn();
    }

    /** Construye la cláusula WHERE y llena $params con los valores enlazados. */
    private function where(array $f, array &$params): string
    {
        $cond = [];
        if (!empty($f['tipo'])) {
            $cond[] = 'tipo = :tipo';
            $params[':tipo'] = $f['tipo'];
        }
        if (!empty($f['usuario'])) {
            $cond[] = 'usuario_email LIKE :usuario';
            $params[':usuario'] = '%' . $f['usuario'] . '%';
        }
        if (!empty($f['desde'])) {
            $cond[] = 'created_at >= :desde';
            $params[':desde'] = $f['desde'] . ' 00:00:00';
        }
        if (!empty($f['hasta'])) {
            $cond[] = 'created_at <= :hasta';
            $params[':hasta'] = $f['hasta'] . ' 23:59:59';
        }
        return $cond ? 'WHERE ' . implode(' AND ', $cond) : '';
    }

    /** Borra registros más antiguos que N días (para cron/mantenimiento). */
    public function purgar(int $dias = 180): int
    {
        $st = $this->db->prepare("DELETE FROM auditoria WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $st->execute([$dias]);
        return $st->rowCount();
    }
}
