<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Registros de visita (kiosco de recepción). Solo entrada en v1.
 */
class Visita extends Model
{
    public function crear(array $d): int
    {
        $this->db->prepare(
            "INSERT INTO visitas
                (nombre_visitante, empresa, telefono, num_personas, motivo,
                 anfitrion_id, anfitrion_email, dispositivo_id, ip)
             VALUES (:nombre, :empresa, :telefono, :personas, :motivo,
                     :anfitrion_id, :anfitrion_email, :dispositivo_id, :ip)"
        )->execute([
            ':nombre'          => $d['nombre_visitante'],
            ':empresa'         => ($d['empresa'] ?? '') ?: null,
            ':telefono'        => ($d['telefono'] ?? '') ?: null,
            ':personas'        => max(1, (int) ($d['num_personas'] ?? 1)),
            ':motivo'          => ($d['motivo'] ?? '') ?: null,
            ':anfitrion_id'    => $d['anfitrion_id'] ?? null,
            ':anfitrion_email' => ($d['anfitrion_email'] ?? '') ?: null,
            ':dispositivo_id'  => $d['dispositivo_id'] ?? null,
            ':ip'              => ($d['ip'] ?? '') ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Construye la cláusula WHERE del historial y llena $params. */
    private function where(array $f, array &$params): string
    {
        $cond = [];
        if (!empty($f['anfitrion_id'])) {
            $cond[] = 'v.anfitrion_id = :anf';
            $params[':anf'] = (int) $f['anfitrion_id'];
        }
        if (!empty($f['desde'])) {
            $cond[] = 'v.created_at >= :desde';
            $params[':desde'] = $f['desde'] . ' 00:00:00';
        }
        if (!empty($f['hasta'])) {
            $cond[] = 'v.created_at <= :hasta';
            $params[':hasta'] = $f['hasta'] . ' 23:59:59';
        }
        return $cond ? 'WHERE ' . implode(' AND ', $cond) : '';
    }

    public function paginado(int $limit, int $offset, array $f = []): array
    {
        $params = [];
        $where  = $this->where($f, $params);
        $sql = "SELECT v.*, a.nombre AS anfitrion_nombre, a.area AS anfitrion_area,
                       d.nombre AS dispositivo_nombre
                  FROM visitas v
                  LEFT JOIN anfitriones a ON a.id = v.anfitrion_id
                  LEFT JOIN checador_dispositivos d ON d.id = v.dispositivo_id
                {$where}
                ORDER BY v.created_at DESC, v.id DESC
                LIMIT :lim OFFSET :off";
        $st = $this->db->prepare($sql);
        foreach ($params as $k => $val) {
            $st->bindValue($k, $val);
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
        $st = $this->db->prepare("SELECT COUNT(*) FROM visitas v {$where}");
        $st->execute($params);
        return (int) $st->fetchColumn();
    }

    /** Todos los registros que cumplen el filtro (sin paginar), para exportar. */
    /** Filas máximas de una exportación (evita agotar la memoria con el histórico). */
    public const MAX_EXPORT = 10000;

    public function exportar(array $f = []): array
    {
        $params = [];
        $where  = $this->where($f, $params);
        $sql = "SELECT v.*, a.nombre AS anfitrion_nombre, a.area AS anfitrion_area,
                       d.nombre AS dispositivo_nombre
                  FROM visitas v
                  LEFT JOIN anfitriones a ON a.id = v.anfitrion_id
                  LEFT JOIN checador_dispositivos d ON d.id = v.dispositivo_id
                {$where}
                ORDER BY v.created_at DESC, v.id DESC
                LIMIT " . self::MAX_EXPORT;
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** Borra registros de visita más antiguos que N días (mantenimiento/privacidad). */
    public function purgar(int $dias = 365): int
    {
        $st = $this->db->prepare("DELETE FROM visitas WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $st->execute([$dias]);
        return $st->rowCount();
    }
}
