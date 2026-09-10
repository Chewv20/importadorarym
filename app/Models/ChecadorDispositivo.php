<?php

namespace App\Models;

use App\Core\Model;

/**
 * Dispositivos (tablets de recepción) autorizados para el kiosco de visitas.
 * Se arman una vez con un enlace de activación de un solo uso y quedan
 * autorizados por una cookie con un device-token (se guarda su hash).
 */
class ChecadorDispositivo extends Model
{
    /** Horas que vive un enlace de activación antes de caducar. */
    public const ACTIVACION_HORAS = 24;

    /** Crea un dispositivo y devuelve el token de activación EN CLARO (para el enlace). */
    public function crear(string $nombre, ?int $oficinaId = null): string
    {
        $token = bin2hex(random_bytes(32));
        $this->db->prepare(
            "INSERT INTO checador_dispositivos (nombre, oficina_id, activacion_token_hash, activacion_expira_en, activo)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR), 1)"
        )->execute([$nombre, $oficinaId, hash('sha256', $token), self::ACTIVACION_HORAS]);
        return $token;
    }

    /** Asigna (o quita, con null) la oficina de un dispositivo ya creado. */
    public function asignarOficina(int $id, ?int $oficinaId): void
    {
        $this->db->prepare("UPDATE checador_dispositivos SET oficina_id = ? WHERE id = ?")
                 ->execute([$oficinaId, $id]);
    }

    /**
     * Dispositivo pendiente de activar por su token de activación. El enlace
     * caduca: uno filtrado o reenviado meses después ya no arma la tablet.
     */
    public function porActivacionToken(string $tokenHash): ?array
    {
        $st = $this->db->prepare(
            "SELECT * FROM checador_dispositivos
              WHERE activacion_token_hash = ?
                AND activacion_expira_en IS NOT NULL
                AND activacion_expira_en > NOW()
              LIMIT 1"
        );
        $st->execute([$tokenHash]);
        return $st->fetch() ?: null;
    }

    /** Marca el dispositivo como activado: guarda el device-token y consume el de activación. */
    public function activar(int $id, string $deviceTokenHash): void
    {
        $this->db->prepare(
            "UPDATE checador_dispositivos
                SET device_token_hash = ?, activacion_token_hash = NULL,
                    activacion_expira_en = NULL, activo = 1, activado_en = NOW()
              WHERE id = ?"
        )->execute([$deviceTokenHash, $id]);
    }

    /** Dispositivo ACTIVO por el token de su cookie. */
    public function porDeviceToken(string $tokenHash): ?array
    {
        $st = $this->db->prepare(
            "SELECT * FROM checador_dispositivos WHERE device_token_hash = ? AND activo = 1 LIMIT 1"
        );
        $st->execute([$tokenHash]);
        return $st->fetch() ?: null;
    }

    public function marcarUso(int $id): void
    {
        $this->db->prepare("UPDATE checador_dispositivos SET ultimo_uso_en = NOW() WHERE id = ?")->execute([$id]);
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM checador_dispositivos WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function todos(): array
    {
        return $this->db->query(
            "SELECT d.*, o.nombre AS oficina_nombre
               FROM checador_dispositivos d
               LEFT JOIN oficinas o ON o.id = d.oficina_id
              ORDER BY d.created_at DESC"
        )->fetchAll();
    }

    /** Revoca el dispositivo: invalida su cookie y lo desactiva. */
    public function revocar(int $id): void
    {
        $this->db->prepare(
            "UPDATE checador_dispositivos SET activo = 0, device_token_hash = NULL WHERE id = ?"
        )->execute([$id]);
    }

    /**
     * Regenera el enlace de activación (para rearmar una tablet). Reactiva el
     * dispositivo e invalida la cookie anterior. Devuelve el token EN CLARO.
     */
    public function regenerarActivacion(int $id): string
    {
        $token = bin2hex(random_bytes(32));
        $this->db->prepare(
            "UPDATE checador_dispositivos
                SET activacion_token_hash = ?,
                    activacion_expira_en = DATE_ADD(NOW(), INTERVAL ? HOUR),
                    device_token_hash = NULL, activo = 1
              WHERE id = ?"
        )->execute([hash('sha256', $token), self::ACTIVACION_HORAS, $id]);
        return $token;
    }
}
