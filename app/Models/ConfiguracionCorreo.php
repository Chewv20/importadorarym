<?php

namespace App\Models;

use App\Core\Crypto;
use App\Core\Model;

class ConfiguracionCorreo extends Model
{
    /** La fila activa (activo = 1), o null si no hay ninguna. */
    public function activa(): ?array
    {
        $st = $this->db->query("SELECT * FROM configuracion_correo WHERE activo = 1 ORDER BY id DESC LIMIT 1");
        return $st->fetch() ?: null;
    }

    /** Última fila guardada (activa o no) — para precargar el formulario del panel. */
    public function ultima(): ?array
    {
        $st = $this->db->query("SELECT * FROM configuracion_correo ORDER BY id DESC LIMIT 1");
        return $st->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM configuracion_correo WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /**
     * Guarda la configuración (una sola fila lógica: actualiza la existente o
     * crea la primera). Invalida el token cacheado porque las credenciales
     * pudieron haber cambiado.
     */
    public function guardar(array $datos, int $usuarioId): int
    {
        $actual = $this->ultima();

        if ($actual) {
            $st = $this->db->prepare(
                "UPDATE configuracion_correo
                    SET tenant_id = ?, client_id = ?, client_secret_cifrado = ?,
                        mailbox = ?, remitente_nombre = ?, activo = ?,
                        token_cache = NULL, token_expira_en = NULL,
                        actualizado_por = ?
                  WHERE id = ?"
            );
            $st->execute([
                $datos['tenant_id'],
                $datos['client_id'],
                $datos['client_secret_cifrado'],
                $datos['mailbox'],
                $datos['remitente_nombre'],
                $datos['activo'] ? 1 : 0,
                $usuarioId,
                $actual['id'],
            ]);
            return (int) $actual['id'];
        }

        $st = $this->db->prepare(
            "INSERT INTO configuracion_correo
                (tenant_id, client_id, client_secret_cifrado, mailbox, remitente_nombre, activo, actualizado_por)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $st->execute([
            $datos['tenant_id'],
            $datos['client_id'],
            $datos['client_secret_cifrado'],
            $datos['mailbox'],
            $datos['remitente_nombre'],
            $datos['activo'] ? 1 : 0,
            $usuarioId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** El token se guarda cifrado (defensa en profundidad: es de vida corta, ~60-90 min). */
    public function guardarTokenCache(int $id, string $token, int $ttlSegundos): void
    {
        $st = $this->db->prepare(
            "UPDATE configuracion_correo
                SET token_cache = ?, token_expira_en = DATE_ADD(NOW(), INTERVAL ? SECOND)
              WHERE id = ?"
        );
        $st->execute([Crypto::encrypt($token), $ttlSegundos, $id]);
    }
}
