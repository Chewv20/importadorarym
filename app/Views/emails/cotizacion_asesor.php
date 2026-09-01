<?php
/** @var array $cot  Datos del lead. @var string $appUrl */
$origenes = ['landing' => 'Landing', 'contacto' => 'Contacto', 'productos' => 'Productos', 'portal' => 'Portal'];
$fila = static function (string $etq, ?string $val): string {
    if (($val ?? '') === '') return '';
    return '<tr>'
        . '<td style="padding:6px 12px 6px 0;color:#6B7280;font-size:13px;white-space:nowrap;vertical-align:top;">' . e($etq) . '</td>'
        . '<td style="padding:6px 0;font-size:14px;color:#20242E;">' . nl2br(e($val)) . '</td>'
        . '</tr>';
};
?>
<h1 style="margin:0 0 8px;font-size:20px;color:#2A3A8F;">Nueva solicitud de cotización</h1>
<p style="margin:0 0 20px;color:#6B7280;font-size:14px;">Recibiste un nuevo lead desde el sitio. Datos capturados:</p>

<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
  <?= $fila('Nombre',    $cot['nombre']           ?? '') ?>
  <?= $fila('Empresa',   $cot['empresa']          ?? '') ?>
  <?= $fila('Correo',    $cot['email']            ?? '') ?>
  <?= $fila('Teléfono',  $cot['telefono']         ?? '') ?>
  <?= $fila('Producto',  $cot['producto_interes'] ?? '') ?>
  <?= $fila('Mensaje',   $cot['mensaje']          ?? '') ?>
  <?= $fila('Impresión', !empty($cot['requiere_impresion']) ? 'Sí, requiere impresión (ver logo adjunto en el panel)' : '') ?>
  <?= $fila('Origen',    $origenes[$cot['origen'] ?? ''] ?? ($cot['origen'] ?? '')) ?>
</table>

<p style="margin:24px 0 0;">
  <a href="<?= e($appUrl) ?>/admin/cotizaciones" style="display:inline-block;background:#2A3A8F;color:#fff;text-decoration:none;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:bold;">Ver en el panel</a>
</p>
