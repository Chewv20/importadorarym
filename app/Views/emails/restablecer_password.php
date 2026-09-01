<?php /** @var string $nombre @var string $restablecerUrl @var int $minutos @var string $appName */ ?>
<h1 style="margin:0 0 16px;font-size:20px;color:#2A3A8F;">Restablece tu contraseña</h1>

<p style="margin:0 0 16px;">Hola <?= e($nombre) ?>,</p>

<p style="margin:0 0 16px;">
  Recibimos una solicitud para restablecer la contraseña de tu cuenta en
  <strong><?= e($appName) ?></strong>. Haz clic en el botón para crear una nueva:
</p>

<p style="margin:24px 0;">
  <a href="<?= e($restablecerUrl) ?>" style="display:inline-block;background:#2A3A8F;color:#fff;text-decoration:none;padding:12px 24px;border-radius:8px;font-size:15px;font-weight:bold;">Crear nueva contraseña</a>
</p>

<p style="margin:0 0 8px;color:#6B7280;font-size:13px;">Si el botón no funciona, copia y pega esta dirección en tu navegador:</p>
<p style="margin:0 0 16px;font-size:13px;word-break:break-all;"><a href="<?= e($restablecerUrl) ?>" style="color:#005EB8;"><?= e($restablecerUrl) ?></a></p>

<p style="margin:0 0 16px;color:#6B7280;font-size:14px;">
  El enlace vence en <strong><?= (int) $minutos ?> minutos</strong> y solo puede usarse una vez.
</p>

<p style="margin:0;color:#6B7280;font-size:14px;">Si tú no solicitaste este cambio, ignora este mensaje; tu contraseña seguirá igual.</p>
