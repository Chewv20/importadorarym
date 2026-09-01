<?php /** @var string $nombre @var string $folio @var float $total @var string $verUrl @var string $appName */ ?>
<h1 style="margin:0 0 16px;font-size:20px;color:#2A3A8F;">Tu cotización está lista</h1>

<p style="margin:0 0 16px;">Hola <?= e($nombre) ?>,</p>

<p style="margin:0 0 16px;">
  Preparamos tu cotización <strong><?= e($folio) ?></strong> en
  <strong><?= e($appName) ?></strong>. El total es
  <strong>$<?= number_format((float) $total, 2) ?> MXN</strong> (IVA incluido).
</p>

<p style="margin:24px 0;">
  <a href="<?= e($verUrl) ?>" style="display:inline-block;background:#2A3A8F;color:#fff;text-decoration:none;padding:12px 24px;border-radius:8px;font-size:15px;font-weight:bold;">Ver y aprobar mi cotización</a>
</p>

<p style="margin:0 0 16px;">
  Desde el portal puedes revisar el detalle, descargarla en PDF y aprobarla para continuar con tu pedido.
</p>

<p style="margin:0;color:#6B7280;font-size:14px;">¿Dudas? Responde este correo o llámanos al (55) 5612 1612.</p>
