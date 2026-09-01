<?php
/** @var array $pedido @var array $items Cada item trae 'errores' (array, vacío si exportable). */
$hayBloqueadas = (bool) array_filter($items, static fn ($it) => !empty($it['errores']));

// Sugerencia de fecha: hoy, o el siguiente día hábil si hoy cae en fin de semana.
$fechaSugerida = date('Y-m-d');
while (in_array((int) date('N', strtotime($fechaSugerida)), [6, 7], true)) {
    $fechaSugerida = date('Y-m-d', strtotime($fechaSugerida . ' +1 day'));
}
?>
<p class="mb-4"><a href="<?= url('/admin/pedidos/' . (int) $pedido['id']) ?>">← Volver al pedido</a></p>

<div class="admin-head">
    <h1>Exportar a SAE — <?= e($pedido['folio']) ?></h1>
</div>

<p class="text-muted mb-6">
    Elige qué partidas exportar y captura su precio unitario. Las que dejes sin marcar
    quedan pendientes — puedes exportarlas después, individualmente o por lote.
    Cliente: <strong><?= e($pedido['cliente_nombre']) ?></strong>
    <?= $pedido['cliente_empresa'] ? '(' . e($pedido['cliente_empresa']) . ')' : '' ?>.
</p>

<?php if ($hayBloqueadas): ?>
    <div class="alert alert--error mb-6">Las partidas marcadas en rojo no se pueden exportar todavía (falta su clave o esquema de impuestos en el producto) — quedarán pendientes.</div>
<?php endif; ?>

<form method="post" action="<?= url('/admin/pedidos/' . (int) $pedido['id'] . '/exportar') ?>" class="form--admin">
    <?= csrf_field() ?>

    <div class="field mb-6">
        <label for="fecha_entrega">Fecha de entrega</label>
        <input type="date" id="fecha_entrega" name="fecha_entrega" required
               value="<?= e($fechaSugerida) ?>" data-fecha-serie data-fecha-serie-out="#fecha-entrega-serie">
        <span class="text-muted fs-sm">
            Determina la serie de SAE y su consecutivo (automático, por serie) — solo días hábiles, no se permite sábado ni domingo.
            Serie: <strong id="fecha-entrega-serie"></strong>
        </span>
    </div>

    <div class="table-wrap mb-6">
        <table class="table">
            <thead><tr>
                <th><input type="checkbox" data-sae-check-all checked title="Seleccionar/quitar todas"> Exportar</th>
                <th>Producto</th><th>Clave SAE</th><th>Cantidad</th><th>Precio unitario</th>
            </tr></thead>
            <tbody>
            <?php foreach ($items as $it):
                $valor = (float) $it['precio_unitario'] > 0
                    ? (float) $it['precio_unitario']
                    : ($it['precio_catalogo'] !== null ? (float) $it['precio_catalogo'] : '');
                $bloqueada = !empty($it['errores']);
            ?>
                <tr class="<?= $bloqueada ? 'row-blocked' : '' ?>">
                    <td><input type="checkbox" name="item_id[]" value="<?= (int) $it['id'] ?>" data-sae-item <?= $bloqueada ? 'disabled' : 'checked' ?>></td>
                    <td>
                        <?= e($it['nombre']) ?>
                        <?php if ($bloqueada): ?>
                            <br><span class="text-danger fs-xs"><?= e(implode(' ', array_unique($it['errores']))) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($it['clave_sae'] ?? '—') ?></td>
                    <td><?= (int) $it['cantidad'] ?></td>
                    <td>
                        <input type="number" name="precio[<?= (int) $it['id'] ?>]" step="0.01" min="0"
                               value="<?= $valor === '' ? '' : e(number_format((float) $valor, 2, '.', '')) ?>"
                               class="price-input" <?= $bloqueada ? 'disabled' : 'required' ?>>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--accent">Guardar precios y descargar Excel</button>
        <a class="btn btn--outline" href="<?= url('/admin/pedidos/' . (int) $pedido['id']) ?>">Cancelar</a>
    </div>
</form>
