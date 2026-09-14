<?php

namespace App\Core;

use App\Models\ConfiguracionCorreo;

/**
 * Envío de correo vía Microsoft Graph API en modo app-only (Client
 * Credentials Flow) — sin usuario interactivo ni refresh tokens que
 * mantener. cURL puro, sin SDK (el proyecto no usa Composer).
 *
 * El access_token de Graph dura ~60-90 min; se cachea en la tabla
 * `configuracion_correo` (ver App\Models\ConfiguracionCorreo) para no
 * pedir uno nuevo en cada envío.
 */
class GraphMailer
{
    private const TOKEN_URL   = 'https://login.microsoftonline.com/%s/oauth2/v2.0/token';
    private const SEND_URL    = 'https://graph.microsoft.com/v1.0/users/%s/sendMail';
    private const SCOPE       = 'https://graph.microsoft.com/.default';
    private const MARGEN_SEGS = 300; // renovar 5 min antes de que venza

    /** Config activa, o null si no hay ninguna marcada como activa. */
    public static function configActiva(): ?array
    {
        return (new ConfiguracionCorreo())->activa();
    }

    /**
     * Envía un correo HTML ya renderizado. Lanza \RuntimeException con un
     * mensaje técnico (para loguear) si algo falla — el llamador decide qué
     * mostrar al usuario final.
     *
     * @param array $embebidos [['cid' => .., 'path' => .., 'nombre' => ..], ...]
     */
    public static function enviar(
        array $cfg,
        array $destinatarios,
        string $asunto,
        string $html,
        ?array $embebidos = null
    ): void {
        $token = self::obtenerToken($cfg);

        $attachments = [];
        foreach ($embebidos ?? [] as $adj) {
            if (!is_file($adj['path'])) {
                continue;
            }
            $attachments[] = [
                '@odata.type'  => '#microsoft.graph.fileAttachment',
                'name'         => $adj['nombre'],
                'contentId'    => $adj['cid'],
                'isInline'     => true,
                'contentType'  => self::mimeDe($adj['path']),
                'contentBytes' => base64_encode((string) file_get_contents($adj['path'])),
            ];
        }

        $payload = [
            'message' => [
                'subject' => $asunto,
                'body'    => ['contentType' => 'HTML', 'content' => $html],
                'toRecipients' => array_map(
                    static fn (string $d) => ['emailAddress' => ['address' => $d]],
                    $destinatarios
                ),
                'from' => [
                    'emailAddress' => [
                        'address' => $cfg['mailbox'],
                        'name'    => $cfg['remitente_nombre'],
                    ],
                ],
            ],
            'saveToSentItems' => true,
        ];
        if ($attachments) {
            $payload['message']['attachments'] = $attachments;
        }

        $url = sprintf(self::SEND_URL, rawurlencode($cfg['mailbox']));
        [$status, $body] = self::curlJson('POST', $url, $payload, $token);

        if ($status !== 202) {
            throw new \RuntimeException('Graph sendMail HTTP ' . $status . ': ' . $body);
        }
    }

    /** Token vigente (de caché en BD) o uno nuevo si venció/no existe. */
    private static function obtenerToken(array $cfg): string
    {
        $expira = !empty($cfg['token_expira_en']) ? strtotime((string) $cfg['token_expira_en']) : 0;
        if (!empty($cfg['token_cache']) && $expira > time() + self::MARGEN_SEGS) {
            return $cfg['token_cache'];
        }
        return self::solicitarToken($cfg);
    }

    private static function solicitarToken(array $cfg): string
    {
        $secret = Crypto::decrypt($cfg['client_secret_cifrado']);
        if ($secret === null) {
            throw new \RuntimeException('No se pudo descifrar el client_secret.');
        }

        $url  = sprintf(self::TOKEN_URL, $cfg['tenant_id']);
        $form = http_build_query([
            'client_id'     => $cfg['client_id'],
            'client_secret' => $secret,
            'scope'         => self::SCOPE,
            'grant_type'    => 'client_credentials',
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $form,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $status !== 200) {
            throw new \RuntimeException('Token Graph HTTP ' . $status . ': ' . ($err ?: $raw));
        }

        $json  = json_decode((string) $raw, true);
        $token = $json['access_token'] ?? null;
        $ttl   = (int) ($json['expires_in'] ?? 3600);
        if (!$token) {
            throw new \RuntimeException('Respuesta de token sin access_token: ' . $raw);
        }

        (new ConfiguracionCorreo())->guardarTokenCache((int) $cfg['id'], $token, $ttl);
        return $token;
    }

    /** POST/PATCH JSON con Bearer token; devuelve [status, body]. */
    private static function curlJson(string $method, string $url, array $payload, string $token): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);
        return [$status, $raw !== false ? (string) $raw : ('cURL error: ' . $err)];
    }

    private static function mimeDe(string $path): string
    {
        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'gif'         => 'image/gif',
            default       => 'application/octet-stream',
        };
    }
}
