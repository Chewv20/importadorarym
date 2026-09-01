<?php /** @var string $nombre @var string $vacante @var string $fecha @var string $appName */ ?>
<h1 style="margin:0 0 16px;font-size:20px;color:#2A3A8F;">Te invitamos a una entrevista</h1>

<p style="margin:0 0 16px;">Hola <?= e($nombre) ?>,</p>

<p style="margin:0 0 16px;">
  ¡Buenas noticias! Tu postulación para la vacante de <strong><?= e($vacante) ?></strong>
  avanzó y queremos conocerte. Te esperamos para una entrevista:
</p>

<p style="margin:0 0 20px;font-size:18px;color:#2A3A8F;"><strong><?= e($fecha) ?></strong></p>

<p style="margin:0 0 16px;">
  Por favor confirma tu asistencia respondiendo a este correo o al teléfono que te
  compartió el equipo. Te pedimos llegar con unos minutos de anticipación.
</p>

<p style="margin:0;color:#6B7280;font-size:14px;">
  Equipo de Recursos Humanos · <?= e($appName) ?>
</p>
