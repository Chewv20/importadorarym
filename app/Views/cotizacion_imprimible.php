<?php
/** @var array $cot @var array $items */
$logo = asset('assets/img/logos/importadorarym.jpg');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cotización <?= e($cot['folio'] ?? '') ?> — Importadora RYM</title>
<style nonce="<?= e(csp_nonce()) ?>">
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; color: #20242E; margin: 0; padding: 32px; background: #fff; font-size: 14px; }
    .doc { max-width: 800px; margin: 0 auto; }
    .doc__head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #2A3A8F; padding-bottom: 16px; margin-bottom: 20px; }
    .doc__logo { max-width: 180px; height: auto; }
    .doc__title { text-align: right; }
    .doc__title h1 { color: #2A3A8F; font-size: 22px; margin: 0 0 4px; }
    .doc__title .folio { font-size: 16px; font-weight: bold; }
    .doc__title .fecha { color: #6B7280; font-size: 13px; }
    .doc__meta { display: flex; justify-content: space-between; gap: 24px; margin-bottom: 20px; }
    .doc__box { flex: 1; }
    .doc__box h3 { font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #6B7280; margin: 0 0 4px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    th { background: #2A3A8F; color: #fff; text-align: left; padding: 8px 10px; font-size: 12px; }
    th.r, td.r { text-align: right; }
    td { padding: 8px 10px; border-bottom: 1px solid #E1E3E8; }
    tfoot td { border: none; padding: 4px 10px; }
    tfoot .total td { font-size: 16px; font-weight: bold; color: #2A3A8F; border-top: 2px solid #2A3A8F; padding-top: 8px; }
    .doc__foot { margin-top: 24px; color: #6B7280; font-size: 12px; border-top: 1px solid #E1E3E8; padding-top: 12px; }
    .print-btn { text-align: center; margin-bottom: 20px; }
    .print-btn button { background: #2A3A8F; color: #fff; border: none; padding: 10px 24px; border-radius: 8px; font-size: 14px; cursor: pointer; }
    @media print { .print-btn { display: none; } body { padding: 0; } }
</style>
</head>
<body>
<div class="doc">
    <div class="print-btn"><button type="button" id="btnImprimir">Imprimir / Guardar como PDF</button></div>

    <div class="doc__head">
        <img class="doc__logo" src="<?= e($logo) ?>" alt="Importadora RYM">
        <div class="doc__title">
            <h1>Cotización</h1>
            <div class="folio"><?= e($cot['folio'] ?? '') ?></div>
            <div class="fecha"><?= e(date('d/m/Y', strtotime($cot['created_at']))) ?></div>
        </div>
    </div>

    <div class="doc__meta">
        <div class="doc__box">
            <h3>Emisor</h3>
            Importadora RYM S.A. de C.V.<br>
            Av. San Lorenzo N° 279, Nave 27<br>
            Iztapalapa, CDMX, C.P. 09850<br>
            Tel. (55) 5612 1612 · cotizaciones@importadorarym.com
        </div>
        <div class="doc__box">
            <h3>Cliente</h3>
            <?= e($cot['nombre']) ?><br>
            <?= $cot['empresa'] ? e($cot['empresa']) . '<br>' : '' ?>
            <?= e($cot['email']) ?><?= $cot['telefono'] ? '<br>' . e($cot['telefono']) : '' ?>
        </div>
    </div>

    <table>
        <thead>
            <tr><th>Producto</th><th class="r">Cant.</th><th class="r">Precio</th><th class="r">Importe</th></tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= e($it['descripcion']) ?></td>
                    <td class="r"><?= (int) $it['cantidad'] ?></td>
                    <td class="r">$<?= number_format((float) $it['precio_unitario'], 2) ?></td>
                    <td class="r">$<?= number_format((float) $it['importe'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="3" class="r">Subtotal</td><td class="r">$<?= number_format((float) ($cot['subtotal'] ?? 0), 2) ?></td></tr>
            <tr><td colspan="3" class="r">IVA</td><td class="r">$<?= number_format((float) ($cot['iva'] ?? 0), 2) ?></td></tr>
            <tr class="total"><td colspan="3" class="r">Total</td><td class="r">$<?= number_format((float) ($cot['total'] ?? 0), 2) ?></td></tr>
        </tfoot>
    </table>

    <?php if (!empty($cot['mensaje'])): ?>
        <div><strong>Notas del cliente:</strong> <?= nl2br(e($cot['mensaje'])) ?></div>
    <?php endif; ?>

    <div class="doc__foot">
        Precios en pesos mexicanos (MXN). Cotización sujeta a cambios sin previo aviso. Vigencia sugerida: 15 días.
    </div>
</div>
<script nonce="<?= e(csp_nonce()) ?>">
document.getElementById('btnImprimir').addEventListener('click', function () { window.print(); });
</script>
</body>
</html>
