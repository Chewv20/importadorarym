<?php
/** @var array $pedidos Cada uno: ['id','folio','cliente_nombre','partidas_pendientes'] */
?>
<h1 style="margin:0 0 8px;font-size:20px;color:#2A3A8F;">Partidas pendientes de exportar a SAE</h1>
<p style="margin:0 0 20px;color:#6B7280;font-size:14px;">
  Estos pedidos tienen partidas que se dejaron pendientes al exportar y ya llegó la fecha del recordatorio que se programó:
</p>

<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:8px 0 16px;">
  <tr>
    <th align="left"  style="padding:8px;border-bottom:2px solid #E1E3E8;font-size:13px;color:#6B7280;">Pedido</th>
    <th align="left"  style="padding:8px;border-bottom:2px solid #E1E3E8;font-size:13px;color:#6B7280;">Cliente</th>
    <th align="right" style="padding:8px;border-bottom:2px solid #E1E3E8;font-size:13px;color:#6B7280;">Partidas pendientes</th>
  </tr>
  <?php foreach ($pedidos as $p): ?>
  <tr>
    <td style="padding:8px;border-bottom:1px solid #E1E3E8;font-size:14px;">
      <a href="<?= e($appUrl) ?>/admin/pedidos/<?= (int) $p['id'] ?>" style="color:#005EB8;text-decoration:none;"><?= e($p['folio']) ?></a>
    </td>
    <td style="padding:8px;border-bottom:1px solid #E1E3E8;font-size:14px;"><?= e($p['cliente_nombre']) ?></td>
    <td align="right" style="padding:8px;border-bottom:1px solid #E1E3E8;font-size:14px;"><?= (int) $p['partidas_pendientes'] ?></td>
  </tr>
  <?php endforeach; ?>
</table>

<p style="margin:24px 0 0;color:#6B7280;font-size:13px;">
  Si siguen pendientes después de exportarlas, puedes programar un nuevo recordatorio desde el detalle de cada pedido.
</p>
