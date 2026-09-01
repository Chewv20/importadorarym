<?php

namespace App\Models;

use App\Core\Model;

/**
 * Tokens de restablecimiento de contraseña. Se almacena el hash del token;
 * el token en claro solo viaja en el correo. Un solo uso y con caducidad.
 */
class PasswordReset extends Model
{
    /** Crea un token para el correo (reemplaza los anteriores del mismo correo). */
    public function crear(string $email, string $tokenHash, int $minutos = 60): void
    {
        $this->db->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
        $this->db->prepare(
            "INSERT INTO password_resets (email, token_hash, expira_en)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))"
        )->execute([$email, $tokenHash, $minutos]);
    }

    /** Devuelve el correo asociado a un token vigente, o null si no existe/expiró. */
    public function emailPorToken(string $tokenHash): ?string
    {
        $st = $this->db->prepare(
            "SELECT email FROM password_resets WHERE token_hash = ? AND expira_en > NOW() LIMIT 1"
        );
        $st->execute([$tokenHash]);
        $email = $st->fetchColumn();
        return $email === false ? null : (string) $email;
    }

    /** Elimina todos los tokens de un correo (tras usarlo). */
    public function eliminarDe(string $email): void
    {
        $this->db->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
    }

    /** Limpieza de tokens caducados (para cron/mantenimiento). */
    public function purgar(): int
    {
        $st = $this->db->prepare("DELETE FROM password_resets WHERE expira_en <= NOW()");
        $st->execute();
        return $st->rowCount();
    }
}
