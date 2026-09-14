<?php

namespace App\Core;

/**
 * Cifrado simétrico reversible para secretos que la app debe poder leer en
 * claro (p. ej. el client_secret de Office 365/Graph en `configuracion_correo`).
 * AES-256-GCM (autenticado) con clave derivada de APP_KEY — sin dependencias
 * nuevas, solo openssl_* (viene con PHP).
 *
 * Si APP_KEY cambia, los valores ya cifrados dejan de poder descifrarse
 * (mismo efecto que ya tiene hoy rotar APP_KEY sobre captcha_secret()).
 */
class Crypto
{
    private const CIPHER = 'aes-256-gcm';

    private static function key(): string
    {
        $k = (string) config('app.key', '');
        if ($k === '') {
            throw new \RuntimeException('APP_KEY no configurada: requerida para cifrar/descifrar.');
        }
        return hash('sha256', $k, true);
    }

    /** Devuelve base64(iv . tag . ciphertext). */
    public static function encrypt(string $plain): string
    {
        $iv  = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $tag = '';
        $ct  = openssl_encrypt($plain, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($ct === false) {
            throw new \RuntimeException('No se pudo cifrar el valor.');
        }
        return base64_encode($iv . $tag . $ct);
    }

    /** Devuelve el texto plano, o null si el payload es inválido o no se pudo descifrar. */
    public static function decrypt(string $payload): ?string
    {
        $raw = base64_decode($payload, true);
        $ivLen = openssl_cipher_iv_length(self::CIPHER);
        if ($raw === false || strlen($raw) < $ivLen + 16) {
            return null;
        }
        $iv  = substr($raw, 0, $ivLen);
        $tag = substr($raw, $ivLen, 16);
        $ct  = substr($raw, $ivLen + 16);
        $plain = openssl_decrypt($ct, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        return $plain === false ? null : $plain;
    }
}
