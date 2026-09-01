<?php
/** @var array $cot @var array $items @var array $productos @var float $iva */
$id = (int) $cot['id'];
$puede = can('cotizaciones.gestionar');
$pill = [
    'nueva' => ['Nueva', 'pill--warn'], 'cotizada' => ['Cotizada', 'pill--ok'],
    'aprobada' => ['Aprobada', 'pill--ok'], 'rechazada' => ['Rechazada', 'pill--danger'],
    'convertida' => ['Convertida', 'pill--off'],
];
[$eTxt, $eCls] = $pill[$cot['estado']] ?? [$cot['estado'], 'pill--off'];
?>
<div class="admin-head">
    <h1>Cotización <?= e($cot['folio'] ?? '') ?> <span class="pill <?= $eCls ?>"><?= e($eTxt) ?></span></h1>
    <div class="admin-head__actions">
        <a class="btn btn--outline btn--sm" href="<?= url('/admin/cotizaciones/' . $id . '/imprimir') ?>" target="_blank" rel="noopener">Imprimir / PDF</a>
        <a class="btn btn--outline btn--sm" href="<?= url('/admin/cotizaciones') ?>">← Volver</a>
    </div>
</div>

<div class="card-panel mb-6">
    <div class="cot-client">
        <div><strong><?= e($cot['nombre']) ?></strong><?= $cot['empresa'] ? ' · ' . e($cot['empresa']) : '' ?></div>
        <div class="text-muted fs-sm"><?= e($cot['email']) ?><?= $cot['telefono'] ? ' · ' . e($cot['telefono']) : '' ?> · origen: <?= e($cot['origen']) ?></div>
        <?php if (!empty($cot['mensaje'])): ?><div class="mt-2"><strong>Nota del cliente:</strong> <?= nl2br(e($cot['mensaje'])) ?></div><?php endif; ?>
        <?php if (!empty($cot['requiere_impresion'])): ?>
            <div class="mt-2">
                <span class="pill pill--warn">Requiere impresión</span>
                <?php if (!empty($cot['logo_archivo'])): ?>
                    <a class="btn btn--outline btn--sm ml-2" href="<?= url('/admin/cotizaciones/' . $id . '/logo') ?>">Descargar logo del cliente</a>
                <?php else: ?>
                    <span class="text-muted fs-sm">Sin logo adjunto</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($cot['estado'] === 'convertida' && !empty($cot['pedido_id'])): ?>
    <div class="alert alert--ok mb-6">Convertida en pedido. <a href="<?= url('/admin/pedidos/' . (int) $cot['pedido_id']) ?>">Ver pedido →</a></div>
<?php endif; ?>

<!-- Partidas + precios -->
<form method="post" action="<?= url('/admin/cotizaciones/' . $id) ?>">
    <?= csrf_field() ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Producto</th><th class="ta-right">Cantidad</th><th class="ta-right">Precio unit.</th><th class="ta-right">Importe</th><?php if ($puede): ?><th></th><?php endif; ?></tr></thead>
            <tbody>
            <?php if ($items): foreach ($items as $it): ?>
                <tr>
                    <td><?= e($it['descripcion']) ?></td>
                    <td class="ta-right"><input class="cot-input" type="number" min="1" name="items[<?= (int) $it['id'] ?>][cantidad]" value="<?= (int) $it['cantidad'] ?>" <?= $puede ? '' : 'disabled' ?>></td>
                    <td class="ta-right"><input class="cot-input" type="number" min="0" step="0.01" name="items[<?= (int) $it['id'] ?>][precio]" value="<?= number_format((float) $it['precio_unitario'], 2, '.', '') ?>" <?= $puede ? '' : 'disabled' ?>></td>
                    <td class="ta-right">$<?= number_format((float) $it['importe'], 2) ?></td>
                    <?php if ($puede): ?>
                    <td class="ta-right"><button class="btn btn--outline btn--sm" type="submit" formaction="<?= url('/admin/cotizaciones/' . $id . '/item/' . (int) $it['id'] . '/quitar') ?>">Quitar</button></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="5" class="text-muted">Sin partidas. Agrega productos abajo.</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="3" class="ta-right">Subtotal</td><td class="ta-right">$<?= number_format((float) ($cot['subtotal'] ?? 0), 2) ?></td><?php if ($puede): ?><td></td><?php endif; ?></tr>
                <tr><td colspan="3" class="ta-right">IVA (<?= rtrim(rtrim(number_format($iva, 2), '0'), '.') ?>%)</td><td class="ta-right">$<?= number_format((float) ($cot['iva'] ?? 0), 2) ?></td><?php if ($puede): ?><td></td><?php endif; ?></tr>
                <tr><td colspan="3" class="ta-right"><strong>Total</strong></td><td class="ta-right"><strong>$<?= number_format((float) ($cot['total'] ?? 0), 2) ?></strong></td><?php if ($puede): ?><td></td><?php endif; ?></tr>
            </tfoot>
        </table>
    </div>

    <?php if ($puede): ?>
    <p class="text-muted fs-sm">Al guardar se recalculan importes y totales.</p>
    <div class="form-actions">
        <button type="submit" class="btn btn--outline">Guardar</button>
        <button type="submit" name="enviar" value="1" class="btn btn--accent">Guardar y enviar al cliente</button>
    </div>
    <?php endif; ?>
</form>

<?php if ($puede): ?>
    <!-- Agregar partida -->
    <section class="card-panel mt-6">
        <h2 class="card-panel__title">Agregar producto</h2>
        <form method="post" action="<?= url('/admin/cotizaciones/' . $id . '/item') ?>" class="cot-additem" data-prodpicker>
            <?= csrf_field() ?>
            <div class="prodpicker">
                <input type="text" class="prodpicker__input" placeholder="Busca por nombre o SKU…" autocomplete="off" aria-label="Buscar producto">
                <input type="hidden" name="producto_id" value="">
                <div class="prodpicker__results" hidden></div>
            </div>
            <input type="number" name="cantidad" value="1" min="1" aria-label="Cantidad">
            <button type="submit" class="btn btn--primary btn--sm">Agregar</button>
        </form>
    </section>

    <!-- Acciones de estado -->
    <section class="cot-actions mt-6">
        <?php if ($cot['estado'] === 'aprobada'): ?>
            <form method="post" action="<?= url('/admin/cotizaciones/' . $id . '/convertir') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--accent">Convertir en pedido</button>
            </form>
        <?php endif; ?>
        <?php if (!in_array($cot['estado'], ['rechazada', 'convertida'], true)): ?>
            <form method="post" action="<?= url('/admin/cotizaciones/' . $id . '/estado') ?>" data-confirm="¿Marcar como rechazada?">
                <?= csrf_field() ?>
                <input type="hidden" name="estado" value="rechazada">
                <button type="submit" class="btn btn--outline btn--sm">Marcar rechazada</button>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>
