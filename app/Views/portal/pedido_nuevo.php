<?php
/** @var array $productos @var array $carrito @var array $categorias @var string $q @var string $catActual @var string $accion */
$productos = $productos ?? [];
$carrito   = $carrito ?? [];
?>
<h1 class="portal-title">Nuevo pedido</h1>
<p class="text-muted mb-8">Agrega los productos y cantidades que necesitas. Un asesor te enviará la cotización.</p>

<div class="order-grid">
    <!-- Catálogo -->
    <div>
        <?php require APP_PATH . '/Views/partials/portal_filtros.php'; ?>
        <?php if ($productos): ?>
            <?php foreach ($productos as $p): ?>
                <div class="order-item">
                    <div class="order-item__main">
                        <?php $imagen = $p['imagen'] ?? null; require APP_PATH . '/Views/partials/order_item_img.php'; ?>
                        <div class="order-item__info">
                            <div class="order-item__name"><?= e($p['nombre']) ?></div>
                            <div class="order-item__meta">
                                <?= e($p['categoria'] ?? '') ?><?= !empty($p['unidad']) ? ' · ' . e($p['unidad']) : '' ?><?= !empty($p['minimo_efectivo']) ? ' · mín. ' . (int) $p['minimo_efectivo'] . ' pzas' : '' ?><?= !empty($p['disponibilidad']) && $p['disponibilidad'] !== 'disponible' ? ' · ' . e(match ($p['disponibilidad']) { 'agotado' => 'Agotado', 'bajo_pedido' => 'Bajo pedido', default => '' }) : '' ?>
                            </div>
                        </div>
                    </div>
                    <form class="qty-form" method="post" action="<?= url('/portal/carrito/agregar') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="producto_id" value="<?= (int) $p['id'] ?>">
                        <input type="number" name="cantidad" value="1" min="1" aria-label="Cantidad de <?= e($p['nombre']) ?>">
                        <button class="btn btn--primary btn--sm" type="submit">Agregar</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="empty-state"><?= ($q ?? '') !== '' || ($catActual ?? '') !== '' ? 'No se encontraron productos con ese criterio.' : 'No hay productos disponibles por el momento.' ?></p>
        <?php endif; ?>

        <?php
        $qs = array_filter(['q' => $q ?? '', 'categoria' => $catActual ?? ''], static fn ($v) => $v !== '');
        $baseUrl = url('/portal/pedidos/nuevo') . ($qs ? '?' . http_build_query($qs) : '');
        require APP_PATH . '/Views/partials/pagination.php';
        ?>
    </div>

    <!-- Carrito -->
    <aside class="cart">
        <div class="cart__title">Tu pedido (<?= count($carrito) ?>)</div>

        <?php if ($carrito): ?>
            <?php foreach ($carrito as $c): ?>
                <div class="cart__item">
                    <div class="order-item__main">
                        <?php $imagen = $c['imagen'] ?? null; require APP_PATH . '/Views/partials/order_item_img.php'; ?>
                        <div class="order-item__info">
                            <div class="order-item__name"><?= e($c['nombre']) ?></div>
                            <div class="order-item__meta"><?= (int) $c['cantidad'] ?><?= $c['unidad'] !== '' ? ' × ' . e($c['unidad']) : ' pza(s)' ?></div>
                        </div>
                    </div>
                    <form method="post" action="<?= url('/portal/carrito/quitar') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="producto_id" value="<?= (int) $c['id'] ?>">
                        <button class="btn btn--outline btn--sm" type="submit" aria-label="Quitar <?= e($c['nombre']) ?>">Quitar</button>
                    </form>
                </div>
            <?php endforeach; ?>

            <a href="<?= url('/portal/pedidos/confirmar') ?>" class="btn btn--accent btn--block mt-6">Revisar y confirmar</a>
        <?php else: ?>
            <p class="cart__empty">Aún no agregas productos a tu pedido.</p>
        <?php endif; ?>
    </aside>
</div>
