<?php
/** @var string $nombre @var string $etiqueta @var array $items @var int $frecuenciaDias @var string $verUrl @var string $appName */
$fila = static function (array $it): string {
    return '<tr>'
        . '<td style="padding:6px 12px 6px 0;font-size:14px;color:#20242E;border-bottom:1px solid #E1E3E8;">' . e($it['nombre']) . '</td>'
        . '<td style="padding:6px 0;font-size:14px;color:#6B7280;text-align:right;white-space:nowrap;border-bottom:1px solid #E1E3E8;">×' . (int) $it['cantidad'] . '</td>'
        . '</tr>';
};
?>
<h1 style="margin:0 0 16px;font-size:20px;color:#2A3A8F;">Es hora de repetir tu pedido</h1>

<p style="margin:0 0 16px;">Hola <?= e($nombre) ?>,</p>

<p style="margin:0 0 16px;">
  Programaste <strong><?= e($etiqueta) ?></strong> para recordarte cada <?= (int) $frecuenciaDias ?> días.
  Este es un buen momento para volver a pedirlo:
</p>

<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0 0 20px;">
  <?php foreach ($items as $it): ?>
    <?= $fila($it) ?>
  <?php endforeach; ?>
</table>

<p style="margin:24px 0;">
  <a href="<?= e($verUrl) ?>" style="display:inline-block;background:#2A3A8F;color:#fff;text-decoration:none;padding:12px 24px;border-radius:8px;font-size:15px;font-weight:bold;">Ver y pedir ahora</a>
</p>

<p style="margin:0;color:#6B7280;font-size:14px;">
  ¿Ya no lo necesitas por ahora? Puedes pausar este recordatorio desde tu portal, sin perder la programación.
</p>
