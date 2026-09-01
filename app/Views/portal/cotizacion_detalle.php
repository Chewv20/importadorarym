<?php
/** @var array $cot @var array $items */
$cotizada = in_array($cot['estado'], ['cotizada', 'aprobada', 'convertida'], true);
?>
<div class="portal-head">
    <h1 class="portal-title">Cotización <?= e($cot['folio'] ?? '') ?></h1>
    <a class="btn btn--outline btn--sm" href="<?= url('/portal/cotizaciones') ?>">← Volver</a>
</div>

<?php if ($cot['estado'] === 'nueva'): ?>
    <div class="alert alert--ok mb-6">Tu solicitud está <strong>en revisión</strong>. Un asesor te enviará los precios pronto.</div>
<?php elseif ($cot['estado'] === 'cotizada'): ?>
    <div class="alert alert--ok mb-6">Tu cotización está lista. Revísala y <strong>apruébala</strong> para continuar.</div>
<?php elseif ($cot['estado'] === 'aprobada'): ?>
    <div class="alert alert--ok mb-6">Aprobaste esta cotización. Un asesor dará seguimiento.</div>
<?php elseif ($cot['estado'] === 'convertida'): ?>
    <div class="alert alert--ok mb-6">Esta cotización se convirtió en pedido.</div>
<?php elseif ($cot['estado'] === 'rechazada'): ?>
    <div class="alert alert--error mb-6">Rechazaste esta cotización.</div>
<?php endif; ?>

<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Producto</th><th class="ta-right">Cantidad</th><?php if ($cotizada): ?><th class="ta-right">Precio</th><th class="ta-right">Importe</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?= e($it['descripcion']) ?></td>
                <td class="ta-right"><?= (int) $it['cantidad'] ?></td>
                <?php if ($cotizada): ?>
                    <td class="ta-right">$<?= number_format((float) $it['precio_unitario'], 2) ?></td>
                    <td class="ta-right">$<?= number_format((float) $it['importe'], 2) ?></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <?php if ($cotizada && $cot['total'] !== null): ?>
        <tfoot>
            <tr><td colspan="3" class="ta-right">Subtotal</td><td class="ta-right">$<?= number_format((float) $cot['subtotal'], 2) ?></td></tr>
            <tr><td colspan="3" class="ta-right">IVA</td><td class="ta-right">$<?= number_format((float) $cot['iva'], 2) ?></td></tr>
            <tr><td colspan="3" class="ta-right"><strong>Total</strong></td><td class="ta-right"><strong>$<?= number_format((float) $cot['total'], 2) ?></strong></td></tr>
        </tfoot>
        <?php endif; ?>
    </table>
</div>

<?php if ($cotizada): ?>
    <div class="mt-6">
        <a class="btn btn--outline" href="<?= url('/portal/cotizaciones/' . (int) $cot['id'] . '/imprimir') ?>" target="_blank" rel="noopener">Imprimir / Guardar PDF</a>
    </div>
<?php endif; ?>

<?php if ($cot['estado'] === 'cotizada'): ?>
    <div class="cot-actions mt-8">
        <form method="post" action="<?= url('/portal/cotizaciones/' . (int) $cot['id'] . '/responder') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="aceptar">
            <button type="submit" class="btn btn--accent">Aprobar cotización</button>
        </form>
        <form method="post" action="<?= url('/portal/cotizaciones/' . (int) $cot['id'] . '/responder') ?>"
              data-confirm="¿Rechazar esta cotización?">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="rechazar">
            <button type="submit" class="btn btn--outline">Rechazar</button>
        </form>
    </div>
<?php endif; ?>
