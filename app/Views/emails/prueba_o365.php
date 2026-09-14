<?php /** @var string $usuarioNombre @var string $appName */ ?>
<h1 style="margin:0 0 16px;font-size:20px;color:#2A3A8F;">Correo de prueba</h1>

<p style="margin:0 0 16px;">
  Este es un correo de prueba enviado desde el panel de administración de
  <strong><?= e($appName) ?></strong> por <strong><?= e($usuarioNombre) ?></strong>
  para verificar la configuración de Office 365.
</p>

<p style="margin:0;color:#6B7280;font-size:14px;">
  Si recibiste este mensaje, el envío por Microsoft Graph está funcionando correctamente.
</p>
