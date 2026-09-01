<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Postulacion extends Model
{
    public const ESTADOS = [
        'recibida'    => 'Recibida',
        'en_revision' => 'En revisión',
        'entrevista'  => 'Entrevista',
        'rechazada'   => 'Rechazada',
        'contratada'  => 'Contratada',
    ];

    public function crear(array $d): int
    {
        $this->db->prepare(
            "INSERT INTO postulaciones (vacante_id, nombre, email, telefono, area_interes, sueldo_deseado, disponibilidad, escolaridad, mensaje, cv_archivo, ip)
             VALUES (:vacante_id, :nombre, :email, :telefono, :area_interes, :sueldo_deseado, :disponibilidad, :escolaridad, :mensaje, :cv, :ip)"
        )->execute([
            ':vacante_id'     => $d['vacante_id'] ?? null,
            ':nombre'         => $d['nombre'],
            ':email'          => $d['email'],
            ':telefono'       => ($d['telefono'] ?? '') ?: null,
            ':area_interes'   => ($d['area_interes'] ?? '') ?: null,
            ':sueldo_deseado' => ($d['sueldo_deseado'] ?? '') ?: null,
            ':disponibilidad' => ($d['disponibilidad'] ?? '') ?: null,
            ':escolaridad'    => ($d['escolaridad'] ?? '') ?: null,
            ':mensaje'        => ($d['mensaje'] ?? '') ?: null,
            ':cv'             => ($d['cv_archivo'] ?? '') ?: null,
            ':ip'             => ($d['ip'] ?? '') ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare(
            "SELECT p.*, v.titulo AS vacante_titulo, v.slug AS vacante_slug
               FROM postulaciones p LEFT JOIN vacantes v ON v.id = p.vacante_id
              WHERE p.id = ?"
        );
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    private function where(array $f, array &$params): string
    {
        $cond = [];
        if (!empty($f['vacante_id'])) {
            if ($f['vacante_id'] === 'general') {
                $cond[] = 'p.vacante_id IS NULL';
            } else {
                $cond[] = 'p.vacante_id = :vac';
                $params[':vac'] = (int) $f['vacante_id'];
            }
        }
        if (!empty($f['estado'])) {
            $cond[] = 'p.estado = :estado';
            $params[':estado'] = $f['estado'];
        }
        return $cond ? 'WHERE ' . implode(' AND ', $cond) : '';
    }

    public function paginado(int $limit, int $offset, array $f = []): array
    {
        $params = [];
        $where  = $this->where($f, $params);
        $sql = "SELECT p.*, v.titulo AS vacante_titulo
                  FROM postulaciones p LEFT JOIN vacantes v ON v.id = p.vacante_id
                {$where}
                ORDER BY p.created_at DESC, p.id DESC
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
        $st = $this->db->prepare("SELECT COUNT(*) FROM postulaciones p {$where}");
        $st->execute($params);
        return (int) $st->fetchColumn();
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $this->db->prepare("UPDATE postulaciones SET estado = ? WHERE id = ?")->execute([$estado, $id]);
    }

    /** Agenda la entrevista: guarda la fecha y pasa el estado a 'entrevista'. */
    public function agendarCita(int $id, string $fechaHora): void
    {
        $this->db->prepare("UPDATE postulaciones SET cita_at = ?, estado = 'entrevista' WHERE id = ?")
                 ->execute([$fechaHora, $id]);
    }

    /**
     * Borra postulaciones más antiguas que N días y devuelve los nombres de
     * los CV eliminados para que el llamador borre los archivos del disco.
     * @return string[]
     */
    public function purgar(int $dias = 365): array
    {
        $st = $this->db->prepare(
            "SELECT cv_archivo FROM postulaciones
              WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY) AND cv_archivo IS NOT NULL"
        );
        $st->execute([$dias]);
        $archivos = array_filter($st->fetchAll(PDO::FETCH_COLUMN));

        $this->db->prepare("DELETE FROM postulaciones WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)")
                 ->execute([$dias]);
        return $archivos;
    }
}
