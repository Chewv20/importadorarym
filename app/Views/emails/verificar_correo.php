<?php /** @var string $nombre @var string $verificaUrl @var string $appName */ ?>
<h1 style="margin:0 0 16px;font-size:20px;color:#2A3A8F;">Verifica tu correo</h1>

<p style="margin:0 0 16px;">Hola <?= e($nombre) ?>,</p>

<p style="margin:0 0 16px;">
  Gracias por registrarte en <strong><?= e($appName) ?></strong>. Para completar tu
  cuenta y poder <strong>levantar pedidos</strong> más adelante, confirma que este
  correo es tuyo:
</p>

<p style="margin:24px 0;">
  <a href="<?= e($verificaUrl) ?>" style="display:inline-block;background:#2A3A8F;color:#fff;text-decoration:none;padding:12px 24px;border-radius:8px;font-size:15px;font-weight:bold;">Verificar mi correo</a>
</p>

<p style="margin:0 0 8px;color:#6B7280;font-size:13px;">Si el botón no funciona, copia y pega esta dirección en tu navegador:</p>
<p style="margin:0 0 16px;font-size:13px;word-break:break-all;"><a href="<?= e($verificaUrl) ?>" style="color:#005EB8;"><?= e($verificaUrl) ?></a></p>

<p style="margin:0;color:#6B7280;font-size:14px;">Si tú no creaste esta cuenta, ignora este mensaje.</p>
