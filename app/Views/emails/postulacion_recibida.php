<?php /** @var string $nombre @var string $vacante @var string $appName */ ?>
<h1 style="margin:0 0 16px;font-size:20px;color:#2A3A8F;">¡Recibimos tu postulación!</h1>

<p style="margin:0 0 16px;">Hola <?= e($nombre) ?>,</p>

<p style="margin:0 0 16px;">
  <?php if ($vacante !== ''): ?>
    Gracias por tu interés en <strong><?= e($appName) ?></strong>. Recibimos tu postulación
    para la vacante de <strong><?= e($vacante) ?></strong> y nuestro equipo de Recursos
    Humanos la revisará. Si tu perfil coincide con lo que buscamos, te contactaremos para
    los siguientes pasos.
  <?php else: ?>
    Gracias por tu interés en <strong><?= e($appName) ?></strong>. Recibimos tu solicitud
    de empleo y nuestro equipo de Recursos Humanos la revisará. Si en el futuro surge una
    vacante que coincida con tu perfil, te contactaremos.
  <?php endif; ?>
</p>

<p style="margin:0;color:#6B7280;font-size:14px;">
  Este es un acuse automático; no es necesario responder.<br>Equipo de <?= e($appName) ?>
</p>
