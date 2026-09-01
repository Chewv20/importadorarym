<?php
/** @var string $nombre @var string $folio @var string $verUrl @var ?string $calificarUrl */
?>
<h1 style="margin:0 0 16px;font-size:20px;color:#2A3A8F;">Tu pedido fue entregado</h1>

<p style="margin:0 0 16px;">Hola <?= e($nombre) ?>,</p>

<p style="margin:0 0 16px;">
  Tu pedido <strong><?= e($folio) ?></strong> ya fue entregado. ¡Gracias por tu compra!
</p>

<p style="margin:24px 0;">
  <a href="<?= e($calificarUrl ?? $verUrl) ?>" style="display:inline-block;background:#2A3A8F;color:#fff;text-decoration:none;padding:12px 24px;border-radius:8px;font-size:15px;font-weight:bold;">Calificar mi entrega</a>
</p>

<p style="margin:0 0 16px;font-size:14px;"><a href="<?= e($verUrl) ?>" style="color:#005EB8;">Ver mi pedido completo</a></p>

<p style="margin:0;color:#6B7280;font-size:14px;">¿Dudas? Responde este correo o llámanos al (55) 5612 1612.</p>
