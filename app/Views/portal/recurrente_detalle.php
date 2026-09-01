<?php
/** @var array $plantilla @var array $items */
$items = $items ?? [];
?>
<p class="mb-4"><a href="<?= url('/portal/recurrentes') ?>">← Volver a mis pedidos recurrentes</a></p>
<h1 class="portal-title"><?= e($plantilla['nombre'] ?: ('Pedido recurrente #' . $plantilla['id'])) ?></h1>

<div class="flex flex-wrap gap-4 items-center mb-8">
    <span class="pill <?= (int) $plantilla['activo'] === 1 ? 'pill--ok' : 'pill--off' ?>">
        <?= (int) $plantilla['activo'] === 1 ? 'Activo' : 'Pausado' ?>
    </span>
    <span class="text-muted fs-sm">Cada <?= (int) $plantilla['frecuencia_dias'] ?> días</span>
    <?php if ((int) $plantilla['activo'] === 1): ?>
        <span class="text-muted fs-sm">· Próximo recordatorio: <?= e(date('d/m/Y', strtotime($plantilla['proximo_recordatorio_en']))) ?></span>
    <?php endif; ?>
</div>

<form method="post" action="<?= url('/portal/recurrentes/' . (int) $plantilla['id'] . '/pedir') ?>" class="mb-8">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn--primary">Agregar al carrito y pedir</button>
    <span class="text-muted fs-sm ml-2">Solo se agregan los productos que sigan disponibles.</span>
</form>

<h2 class="portal-subtitle">Productos</h2>
<div class="table-wrap mb-8">
    <table class="table">
        <thead><tr><th>Producto</th><th>SKU</th><th>Cantidad</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?= e($it['nombre']) ?></td>
                <td><?= !empty($it['sku']) ? e($it['sku']) : '—' ?></td>
                <td><?= (int) $it['cantidad'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h2 class="portal-subtitle">Cambiar frecuencia</h2>
<form method="post" action="<?= url('/portal/recurrentes/' . (int) $plantilla['id'] . '/frecuencia') ?>" class="form form--inline mb-8">
    <?= csrf_field() ?>
    <div class="field">
        <label for="frecuencia_dias">Recordar cada</label>
        <select id="frecuencia_dias" name="frecuencia_dias">
            <?php foreach (\App\Models\PedidoRecurrente::FRECUENCIAS as $f): ?>
                <option value="<?= $f ?>" <?= (int) $plantilla['frecuencia_dias'] === $f ? 'selected' : '' ?>><?= $f ?> días</option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn--outline btn--sm">Guardar</button>
</form>

<div class="flex gap-2">
    <?php if ((int) $plantilla['activo'] === 1): ?>
        <form method="post" action="<?= url('/portal/recurrentes/' . (int) $plantilla['id'] . '/pausar') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--outline btn--sm">Pausar recordatorios</button>
        </form>
    <?php else: ?>
        <form method="post" action="<?= url('/portal/recurrentes/' . (int) $plantilla['id'] . '/reanudar') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--outline btn--sm">Reactivar recordatorios</button>
        </form>
    <?php endif; ?>
    <form method="post" action="<?= url('/portal/recurrentes/' . (int) $plantilla['id'] . '/eliminar') ?>"
          data-confirm="¿Eliminar este pedido recurrente?">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--ghost btn--sm">Eliminar</button>
    </form>
</div>
