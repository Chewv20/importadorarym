<?php
/**
 * @var string $mensaje @var string $archivo @var int $linea
 * @var string $trace @var string $url @var string $ip @var string $cuando
 */
?>
<h1 style="margin:0 0 8px;font-size:20px;color:#B3261E;">Error 500 en el sitio</h1>
<p style="margin:0 0 20px;color:#6B7280;font-size:14px;">
  Se registró un error interno en producción. Este es un aviso automático —
  el detalle completo también queda en <code>storage/logs/php-error.log</code>.
</p>

<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0 0 16px;">
  <tr>
    <td style="padding:6px 8px;color:#6B7280;font-size:13px;width:110px;">Cuándo</td>
    <td style="padding:6px 8px;font-size:14px;"><?= e($cuando) ?></td>
  </tr>
  <tr>
    <td style="padding:6px 8px;color:#6B7280;font-size:13px;">Petición</td>
    <td style="padding:6px 8px;font-size:14px;"><?= e($url) ?></td>
  </tr>
  <tr>
    <td style="padding:6px 8px;color:#6B7280;font-size:13px;">IP</td>
    <td style="padding:6px 8px;font-size:14px;"><?= e($ip) ?></td>
  </tr>
  <tr>
    <td style="padding:6px 8px;color:#6B7280;font-size:13px;">Archivo</td>
    <td style="padding:6px 8px;font-size:14px;"><?= e($archivo) ?>:<?= (int) $linea ?></td>
  </tr>
</table>

<p style="margin:0 0 8px;font-size:14px;"><strong><?= e($mensaje) ?></strong></p>

<pre style="margin:0;padding:12px;background:#F3F4F6;border-radius:8px;font-size:12px;line-height:1.5;white-space:pre-wrap;word-break:break-word;color:#374151;"><?= e($trace) ?></pre>
