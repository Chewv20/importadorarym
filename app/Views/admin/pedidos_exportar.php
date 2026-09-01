<?php
/** @var array $exportables  Cada uno: ['pedido'=>..., 'items'=>...] Cada item trae 'errores'. */
/** @var array $bloqueados   Cada uno: ['folio'=>..., 'motivos'=>[...]] */

// Sugerencia de fecha: hoy, o el siguiente día hábil si hoy cae en fin de semana.
$fechaSugerida = date('Y-m-d');
while (in_array((int) date('N', strtotime($fechaSugerida)), [6, 7], true)) {
    $fechaSugerida = date('Y-m-d', strtotime($fechaSugerida . ' +1 day'));
}
?>
<p class="mb-4"><a href="<?= url('/admin/pedidos') ?>">← Volver a pedidos</a></p>

<div class="admin-head"><h1>Exportar pendientes a SAE</h1></div>

<?php if ($bloqueados): ?>
    <div class="alert alert--error mb-6">
        <strong>Estos pedidos no se incluyen</strong> hasta capturar la clave de SAE del cliente:
        <ul class="mt-2">
            <?php foreach ($bloqueados as $b): ?>
                <li><strong><?= e($b['folio']) ?></strong>: <?= e(implode(' ', $b['motivos'])) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!$exportables): ?>
    <p class="empty-state">No hay pedidos listos para exportar. Captura las claves de SAE de los pedidos bloqueados.</p>
<?php else: ?>
    <p class="text-muted mb-6">
        Elige qué partidas exportar de cada pedido y captura su precio unitario. Las que
        dejes sin marcar quedan pendientes. Se generará un solo Excel con lo seleccionado
        de <strong><?= count($exportables) ?></strong> pedido(s) para importar en Aspel SAE.
        Las partidas ya exportadas antes no se incluyen de nuevo.
    </p>

    <p class="text-muted mb-6">Cada pedido lleva su propia fecha de entrega — determina su serie de SAE (L a V) y su consecutivo, que se asigna automáticamente por serie. No se permite sábado ni domingo.</p>

    <form method="post" action="<?= url('/admin/pedidos/exportar') ?>">
        <?= csrf_field() ?>

        <?php foreach ($exportables as $ex): $p = $ex['pedido']; $fechaInputId = 'fecha_entrega_' . (int) $p['id']; ?>
            <div class="panel">
                <div class="panel__title">
                    <?= e($p['folio']) ?> — <?= e($p['cliente_nombre']) ?>
                    <?= !empty($p['referencia_cliente']) ? '· Su pedido: ' . e($p['referencia_cliente']) : '' ?>
                </div>
                <div class="field mb-4">
                    <label for="<?= $fechaInputId ?>">Fecha de entrega</label>
                    <input type="date" id="<?= $fechaInputId ?>" name="fecha_entrega[<?= (int) $p['id'] ?>]"
                           value="<?= e($fechaSugerida) ?>" data-fecha-serie data-fecha-serie-out="#<?= $fechaInputId ?>_serie">
                    <span class="text-muted fs-sm">Serie: <strong id="<?= $fechaInputId ?>_serie"></strong>. Si no vas a exportar este pedido ahora, quita la marca de "Exportar" de su tabla.</span>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr>
                            <th><input type="checkbox" data-sae-check-all checked title="Seleccionar/quitar todas"> Exportar</th>
                            <th>Producto</th><th>Clave SAE</th><th>Cantidad</th><th>Precio unitario</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($ex['items'] as $it):
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
            </div>
        <?php endforeach; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn--accent">Guardar precios y descargar Excel</button>
            <a class="btn btn--outline" href="<?= url('/admin/pedidos') ?>">Cancelar</a>
        </div>
    </form>
<?php endif; ?>
