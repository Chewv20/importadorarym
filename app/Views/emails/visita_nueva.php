<?php
/**
 * @var string $anfitrion @var string $visitante @var string $empresa
 * @var string $motivo @var int $personas @var string $telefono @var string $hora
 * @var string $appName
 */
?>
<h1 style="margin:0 0 16px;font-size:20px;color:#2A3A8F;">Tienes una visita en recepción</h1>

<p style="margin:0 0 16px;">Hola <?= e($anfitrion) ?>,</p>

<p style="margin:0 0 16px;">
  <strong><?= e($visitante) ?></strong><?= $empresa !== '' ? ' de <strong>' . e($empresa) . '</strong>' : '' ?>
  te espera en <strong>recepción</strong>.
</p>

<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 16px;font-size:14px;color:#20242E;">
  <tr><td style="padding:4px 12px 4px 0;color:#6B7280;">Hora de registro:</td><td style="padding:4px 0;"><?= e($hora) ?></td></tr>
  <?php if ($motivo !== ''): ?>
  <tr><td style="padding:4px 12px 4px 0;color:#6B7280;">Motivo:</td><td style="padding:4px 0;"><?= e($motivo) ?></td></tr>
  <?php endif; ?>
  <tr><td style="padding:4px 12px 4px 0;color:#6B7280;">Personas:</td><td style="padding:4px 0;"><?= (int) $personas ?></td></tr>
  <?php if ($telefono !== ''): ?>
  <tr><td style="padding:4px 12px 4px 0;color:#6B7280;">Teléfono:</td><td style="padding:4px 0;"><?= e($telefono) ?></td></tr>
  <?php endif; ?>
</table>

<p style="margin:0;color:#6B7280;font-size:14px;">
  Por favor acude a recepción para recibir a tu visita. Este aviso se generó
  automáticamente desde la libreta de visitas de <?= e($appName) ?>.
</p>
