<?php
/** @var string $nombre @var string $folio @var string $estado @var string $label @var string $verUrl @var string $appName */
$desc = [
    'enviado'      => 'Recibimos tu pedido y lo estamos revisando.',
    'en_proceso'   => 'Ya estamos preparando tu pedido.',
    'sincronizado' => 'Tu pedido fue procesado. Pronto te contactaremos para coordinar la entrega.',
    'cancelado'    => 'Tu pedido fue cancelado. Si tienes dudas, contáctanos.',
][$estado] ?? 'El estado de tu pedido se actualizó.';
?>
<h1 style="margin:0 0 16px;font-size:20px;color:#2A3A8F;">Actualización de tu pedido</h1>

<p style="margin:0 0 16px;">Hola <?= e($nombre) ?>,</p>

<p style="margin:0 0 16px;">
  Tu pedido <strong><?= e($folio) ?></strong> ahora está
  <strong><?= e(ucfirst($label)) ?></strong>. <?= e($desc) ?>
</p>

<p style="margin:24px 0;">
  <a href="<?= e($verUrl) ?>" style="display:inline-block;background:#2A3A8F;color:#fff;text-decoration:none;padding:12px 24px;border-radius:8px;font-size:15px;font-weight:bold;">Ver mi pedido</a>
</p>

<p style="margin:0;color:#6B7280;font-size:14px;">¿Dudas? Responde este correo o llámanos al (55) 5612 1612.</p>
