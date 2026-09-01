<?php
/**
 * @var string $folio @var string $cliente @var string $empresa
 * @var string $referencia @var string $notas @var array $items
 * @var int $pedidoId @var string $appUrl @var ?string $creadoPor
 */
$creadoPor = $creadoPor ?? null;
?>
<h1 style="margin:0 0 8px;font-size:20px;color:#2A3A8F;">Nuevo pedido: <?= e($folio) ?></h1>
<p style="margin:0 0 20px;color:#6B7280;font-size:14px;">
  <strong><?= e($cliente) ?></strong><?= $empresa !== '' ? ' · ' . e($empresa) : '' ?>
  <?= $creadoPor !== null
        ? 'tiene un pedido nuevo, capturado por ' . e($creadoPor) . '.'
        : 'levantó un pedido en el portal.' ?>
</p>

<?php if ($referencia !== ''): ?>
  <p style="margin:0 0 12px;font-size:14px;"><span style="color:#6B7280;">Su pedido (ref. cliente):</span> <strong><?= e($referencia) ?></strong></p>
<?php endif; ?>

<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:8px 0 16px;">
  <tr>
    <th align="left"  style="padding:8px;border-bottom:2px solid #E1E3E8;font-size:13px;color:#6B7280;">Producto</th>
    <th align="right" style="padding:8px;border-bottom:2px solid #E1E3E8;font-size:13px;color:#6B7280;">Cant.</th>
  </tr>
  <?php foreach ($items as $it): ?>
  <tr>
    <td style="padding:8px;border-bottom:1px solid #E1E3E8;font-size:14px;">
      <?= e($it['nombre'] ?? '') ?>
      <?php if (!empty($it['sku'])): ?><br><span style="color:#9AA0AC;font-size:12px;">SKU: <?= e($it['sku']) ?></span><?php endif; ?>
    </td>
    <td align="right" style="padding:8px;border-bottom:1px solid #E1E3E8;font-size:14px;"><?= (int) ($it['cantidad'] ?? 0) ?></td>
  </tr>
  <?php endforeach; ?>
</table>

<?php if ($notas !== ''): ?>
  <p style="margin:0 0 16px;font-size:14px;"><span style="color:#6B7280;">Notas:</span><br><?= nl2br(e($notas)) ?></p>
<?php endif; ?>

<p style="margin:24px 0 0;">
  <a href="<?= e($appUrl) ?>/admin/pedidos/<?= (int) $pedidoId ?>" style="display:inline-block;background:#2A3A8F;color:#fff;text-decoration:none;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:bold;">Ver pedido en el panel</a>
</p>
