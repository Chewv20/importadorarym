<?php
/**
 * Layout de correo. Recibe $contenido (HTML interior), $appName, $appUrl,
 * $asunto, $logoCid. Estilos en línea a propósito: los clientes de correo
 * ignoran CSS externo.
 *
 * El logo se referencia por Content-ID (adjunto embebido por Mailer::enviar,
 * no por URL): una URL absoluta solo se ve si el servidor es alcanzable
 * públicamente desde el cliente de correo del destinatario, lo que además de
 * fallar en local queda sujeto al bloqueo de imágenes remotas de muchos
 * clientes de correo.
 */
$logo = isset($logoCid) ? 'cid:' . $logoCid : asset_url('assets/img/logos/importadorarym.jpg');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($asunto ?? ($appName ?? 'Importadora RYM')) ?></title>
</head>
<body style="margin:0;padding:0;background:#F4F5F7;font-family:Arial,Helvetica,sans-serif;color:#20242E;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F4F5F7;padding:24px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:600px;background:#FFFFFF;border-radius:12px;overflow:hidden;border:1px solid #E1E3E8;">
          <!-- Encabezado -->
          <tr>
            <td style="background:#2A3A8F;padding:20px 32px;">
              <img src="<?= e($logo) ?>" alt="<?= e($appName ?? 'Importadora RYM') ?>" width="150" style="display:block;border:0;max-width:150px;height:auto;background:#fff;border-radius:6px;">
            </td>
          </tr>
          <!-- Contenido -->
          <tr>
            <td style="padding:32px;font-size:15px;line-height:1.6;color:#20242E;">
              <?= $contenido ?>
            </td>
          </tr>
          <!-- Pie -->
          <tr>
            <td style="padding:20px 32px;background:#F4F5F7;border-top:1px solid #E1E3E8;font-size:12px;line-height:1.5;color:#6B7280;">
              <?= e($appName ?? 'Importadora RYM') ?> · Av. San Lorenzo N° 279, Nave 27, Iztapalapa, CDMX<br>
              Tel. (55) 5612 1612 · <a href="<?= e($appUrl ?? '#') ?>" style="color:#005EB8;text-decoration:none;"><?= e(preg_replace('#^https?://#', '', (string)($appUrl ?? ''))) ?></a><br>
              <span style="color:#9AA0AC;">Este es un correo automático, por favor no respondas a esta dirección.</span>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
