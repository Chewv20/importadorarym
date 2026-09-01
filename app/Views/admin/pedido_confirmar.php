<?php
/** @var array $cliente @var array $carrito */
$cliente = $cliente ?? [];
$carrito = $carrito ?? [];
?>
<div class="admin-head">
    <h1>Revisar pedido</h1>
</div>
<p class="text-muted mb-6">
    Pedido para <strong><?= e($cliente['nombre'] ?? '') ?></strong><?= !empty($cliente['empresa']) ? ' · ' . e($cliente['empresa']) : '' ?>
    <?php if ((int) ($cliente['aprobado'] ?? 0) !== 1): ?>
        <span class="pill pill--off">Cuenta pendiente de aprobación</span>
    <?php endif; ?>
</p>

<?php foreach ($carrito as $c): ?>
    <div class="order-item">
        <div class="order-item__main">
            <?php $imagen = $c['imagen'] ?? null; require APP_PATH . '/Views/partials/order_item_img.php'; ?>
            <div class="order-item__info">
                <div class="order-item__name"><?= e($c['nombre']) ?></div>
                <div class="order-item__meta"><?= $c['unidad'] !== '' ? e($c['unidad']) : 'pza(s)' ?></div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <form class="qty-form" method="post" action="<?= url('/admin/pedidos/nuevo/carrito/actualizar') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="producto_id" value="<?= (int) $c['id'] ?>">
                <input type="number" name="cantidad" value="<?= (int) $c['cantidad'] ?>" min="1" max="9999" aria-label="Cantidad de <?= e($c['nombre']) ?>">
                <button class="btn btn--outline btn--sm" type="submit">Actualizar</button>
            </form>
            <form method="post" action="<?= url('/admin/pedidos/nuevo/carrito/quitar') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="producto_id" value="<?= (int) $c['id'] ?>">
                <input type="hidden" name="origen" value="confirmar">
                <button class="btn btn--outline btn--sm" type="submit" aria-label="Quitar <?= e($c['nombre']) ?>">Quitar</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<form method="post" action="<?= url('/admin/pedidos/nuevo') ?>" class="mt-8">
    <?= csrf_field() ?>
    <div class="field mb-4">
        <label for="referencia">Referencia del cliente (opcional)</label>
        <input type="text" id="referencia" name="referencia" maxlength="60" placeholder="Su número de pedido o control interno">
    </div>
    <div class="field mb-4">
        <label for="notas">Notas (opcional)</label>
        <textarea id="notas" name="notas" placeholder="Indicaciones especiales, fecha de entrega..."></textarea>
    </div>
    <button type="submit" class="btn btn--accent btn--lg btn--block">Crear pedido</button>
</form>

<p class="mt-4"><a href="<?= url('/admin/pedidos/nuevo') ?>">← Seguir agregando productos</a></p>
