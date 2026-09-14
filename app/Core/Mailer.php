<?php

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Servicio de correo transaccional.
 *
 * Envuelve a PHPMailer (vendorizado en app/Vendor/PHPMailer, sin Composer) y
 * renderiza plantillas HTML de app/Views/emails con el layout de correo.
 *
 * Nunca lanza excepción hacia el flujo del usuario: si el envío falla se
 * registra en storage/logs/mail.log y devuelve false.
 *
 * Config en config/app.php -> 'mail'. Con MAIL_MAILER=log no se envía nada:
 * el correo se vuelca al log (modo desarrollo).
 */
class Mailer
{
    /**
     * Content-ID del logo incrustado en el correo (ver enviar() y
     * emails/layout.php). Se incrusta como adjunto embebido en vez de
     * referenciarlo por URL: así se ve en cualquier entorno —incluido este,
     * donde APP_URL apunta a localhost, inalcanzable para el cliente de
     * correo del destinatario— y además evita el bloqueo de imágenes remotas
     * que aplican muchos clientes de correo por defecto.
     */
    private const LOGO_CID = 'logo-rym';

    /** Carga puntual de PHPMailer (el autoloader del proyecto solo cubre App\). */
    private static function cargarPHPMailer(): void
    {
        if (class_exists(PHPMailer::class)) {
            return;
        }
        $base = ROOT_PATH . '/app/Vendor/PHPMailer/src/';
        require_once $base . 'Exception.php';
        require_once $base . 'PHPMailer.php';
        require_once $base . 'SMTP.php';
    }

    /**
     * Envía un correo HTML a partir de una plantilla de app/Views/emails.
     *
     * @param string|string[] $to      Destinatario(s).
     * @param string          $asunto  Asunto.
     * @param string          $vista   Nombre de la plantilla en Views/emails (sin ruta ni .php).
     * @param array           $datos   Variables para la plantilla.
     */
    public static function enviar($to, string $asunto, string $vista, array $datos = []): bool
    {
        $cfg  = config('app.mail', []);
        $html = self::render($vista, $datos + ['asunto' => $asunto]);
        $destinatarios = array_filter(array_map('trim', (array) $to));

        if (!$destinatarios) {
            return false;
        }

        // Modo desarrollo: no se envía, se registra el correo.
        if (($cfg['mailer'] ?? 'log') === 'log') {
            self::log('LOG (no enviado) a ' . implode(', ', $destinatarios)
                . ' | asunto: ' . $asunto);
            return true;
        }

        // Office 365 (Microsoft Graph, app-only) tiene prioridad sobre SMTP
        // cuando hay una configuración activa en el panel. Sin fallback
        // automático a SMTP: si Graph falla, mejor que se vea en el log a
        // que se envíe por una vía no auditada sin que nadie se entere.
        $graphCfg = GraphMailer::configActiva();
        if ($graphCfg !== null) {
            try {
                $logoPath = ROOT_PATH . '/public/assets/img/logos/importadorarym.jpg';
                $embebidos = is_file($logoPath)
                    ? [['cid' => self::LOGO_CID, 'path' => $logoPath, 'nombre' => basename($logoPath)]]
                    : null;
                GraphMailer::enviar($graphCfg, $destinatarios, $asunto, $html, $embebidos);
                return true;
            } catch (\Throwable $e) {
                self::log('ERROR Graph a ' . implode(', ', $destinatarios)
                    . ' | asunto: ' . $asunto . ' | ' . $e->getMessage());
                return false;
            }
        }

        try {
            self::cargarPHPMailer();
            $mail = new PHPMailer(true);
            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';

            if (($cfg['mailer'] ?? '') === 'smtp') {
                $mail->isSMTP();
                $mail->Host       = (string) ($cfg['host'] ?? '');
                $mail->Port       = (int) ($cfg['port'] ?? 587);
                $mail->SMTPAuth   = true;
                $mail->Username   = (string) ($cfg['username'] ?? '');
                $mail->Password   = (string) ($cfg['password'] ?? '');
                $enc = strtolower((string) ($cfg['encryption'] ?? 'tls'));
                if ($enc === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($enc === 'tls') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                }
                $mail->Timeout = 15;
            }

            $logoPath = ROOT_PATH . '/public/assets/img/logos/importadorarym.jpg';
            if (is_file($logoPath)) {
                $mail->addEmbeddedImage($logoPath, self::LOGO_CID, basename($logoPath));
            }

            $mail->setFrom(
                (string) ($cfg['from_address'] ?? 'no-responder@localhost'),
                (string) ($cfg['from_name'] ?? 'Importadora RYM')
            );
            if (!empty($cfg['reply_to'])) {
                $mail->addReplyTo((string) $cfg['reply_to']);
            }
            foreach ($destinatarios as $dest) {
                $mail->addAddress($dest);
            }

            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $html;
            $mail->AltBody = self::textoPlano($html);

            $mail->send();
            return true;
        } catch (PHPMailerException | \Throwable $e) {
            self::log('ERROR al enviar a ' . implode(', ', $destinatarios)
                . ' | asunto: ' . $asunto . ' | ' . $e->getMessage());
            return false;
        }
    }

    /** Renderiza una plantilla de correo dentro del layout de email. */
    private static function render(string $vista, array $datos): string
    {
        $datos['appName'] = config('app.name', 'Importadora RYM');
        $datos['appUrl']  = rtrim((string) config('app.url'), '/');
        $datos['logoCid'] = self::LOGO_CID;

        $cuerpo = View::capture('emails/' . $vista, $datos);
        return View::capture('emails/layout', $datos + ['contenido' => $cuerpo]);
    }

    /** Versión de texto plano de respaldo a partir del HTML. */
    private static function textoPlano(string $html): string
    {
        $txt = preg_replace('/<(br|\/p|\/div|\/tr|\/h[1-6])>/i', "\n", $html) ?? $html;
        $txt = strip_tags($txt);
        $txt = html_entity_decode($txt, ENT_QUOTES, 'UTF-8');
        $txt = preg_replace("/[ \t]+/", ' ', $txt) ?? $txt;
        $txt = preg_replace("/\n{3,}/", "\n\n", $txt) ?? $txt;
        return trim($txt);
    }

    private static function log(string $mensaje): void
    {
        $linea = '[' . date('Y-m-d H:i:s') . '] ' . $mensaje . PHP_EOL;
        @file_put_contents(ROOT_PATH . '/storage/logs/mail.log', $linea, FILE_APPEND);
    }
}
