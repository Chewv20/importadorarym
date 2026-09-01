<?php /** @var string $nombre @var string $appName */ ?>
<h1 style="margin:0 0 16px;font-size:20px;color:#2A3A8F;">¡Gracias por tu solicitud!</h1>

<p style="margin:0 0 16px;">Hola <?= e($nombre) ?>,</p>

<p style="margin:0 0 16px;">
  Recibimos tu solicitud de cotización en <strong><?= e($appName) ?></strong>.
  Uno de nuestros asesores la revisará y te contactará a la brevedad, normalmente
  dentro de las siguientes horas hábiles.
</p>

<p style="margin:0 0 16px;">
  Si tu solicitud es urgente, también puedes escribirnos por
  <a href="<?= e(whatsapp_url()) ?>" style="color:#2A3A8F;">WhatsApp al 55 5297 9776</a>
  o llamarnos al <strong>(55) 5612 1612</strong>.
</p>

<p style="margin:24px 0 0;color:#6B7280;font-size:14px;">
  Gracias por confiar en nosotros.<br>Equipo de <?= e($appName) ?>
</p>
