<?php /** @var string $nombre @var string $appName @var string $appUrl */ ?>
<h1 style="margin:0 0 16px;font-size:20px;color:#2A3A8F;">Tu cuenta ya está activa</h1>

<p style="margin:0 0 16px;">Hola <?= e($nombre) ?>,</p>

<p style="margin:0 0 16px;">
  ¡Buenas noticias! Un asesor activó tu cuenta en el portal de
  <strong><?= e($appName) ?></strong>. A partir de ahora ya puedes iniciar sesión
  y <strong>levantar pedidos</strong> en línea, además de solicitar cotizaciones.
</p>

<p style="margin:24px 0;">
  <a href="<?= e($appUrl) ?>/portal/login" style="display:inline-block;background:#2A3A8F;color:#fff;text-decoration:none;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:bold;">Entrar al portal</a>
</p>

<p style="margin:0;color:#6B7280;font-size:14px;">
  Si tú no solicitaste esta cuenta, ignora este mensaje.
</p>
