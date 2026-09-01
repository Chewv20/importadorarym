<?php
/** @var string $vacante @var string $nombre @var string $email @var string $telefono @var string $mensaje @var string $fecha @var string $panelUrl @var string $appName */
?>
<h1 style="margin:0 0 16px;font-size:20px;color:#2A3A8F;">Nueva postulación recibida</h1>

<?php if ($vacante !== ''): ?>
<p style="margin:0 0 16px;">Se recibió una postulación para la vacante <strong><?= e($vacante) ?></strong>.</p>
<?php else: ?>
<p style="margin:0 0 16px;">Se recibió una <strong>solicitud de empleo general</strong> (sin vacante publicada específica).</p>
<?php endif; ?>

<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 16px;font-size:14px;color:#20242E;">
  <tr><td style="padding:4px 12px 4px 0;color:#6B7280;">Candidato:</td><td style="padding:4px 0;"><strong><?= e($nombre) ?></strong></td></tr>
  <tr><td style="padding:4px 12px 4px 0;color:#6B7280;">Correo:</td><td style="padding:4px 0;"><?= e($email) ?></td></tr>
  <?php if ($telefono !== ''): ?>
  <tr><td style="padding:4px 12px 4px 0;color:#6B7280;">Teléfono:</td><td style="padding:4px 0;"><?= e($telefono) ?></td></tr>
  <?php endif; ?>
  <tr><td style="padding:4px 12px 4px 0;color:#6B7280;">Fecha:</td><td style="padding:4px 0;"><?= e($fecha) ?></td></tr>
</table>

<?php if ($mensaje !== ''): ?>
<p style="margin:0 0 16px;"><strong>Mensaje:</strong><br><?= nl2br(e($mensaje)) ?></p>
<?php endif; ?>

<p style="margin:24px 0;">
  <a href="<?= e($panelUrl) ?>" style="display:inline-block;background:#2A3A8F;color:#fff;text-decoration:none;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:bold;">Ver en el panel y descargar CV</a>
</p>

<p style="margin:0;color:#6B7280;font-size:14px;">Bolsa de trabajo de <?= e($appName) ?>.</p>
