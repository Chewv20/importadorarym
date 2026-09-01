<?php
/** @var array $productos @var array $carrito @var array $categorias @var string $q @var string $catActual @var string $accion */
$productos = $productos ?? [];
$carrito   = $carrito ?? [];
?>
<div class="portal-head">
    <h1 class="portal-title">Solicitar cotización</h1>
    <a class="btn btn--outline btn--sm" href="<?= url('/portal/cotizaciones') ?>">Mis cotizaciones</a>
</div>
<p class="text-muted mb-8">Agrega los productos y cantidades que quieres cotizar. Un asesor te enviará la cotización con precios.</p>

<div class="order-grid">
    <div>
        <?php require APP_PATH . '/Views/partials/portal_filtros.php'; ?>
        <?php if ($productos): ?>
            <?php foreach ($productos as $p): ?>
                <div class="order-item">
                    <div class="order-item__info">
                        <div class="order-item__name"><?= e($p['nombre']) ?></div>
                        <div class="order-item__meta">
                            <?= e($p['categoria'] ?? '') ?><?= !empty($p['unidad']) ? ' · ' . e($p['unidad']) : '' ?>
                        </div>
                    </div>
                    <form class="qty-form" method="post" action="<?= url('/portal/cotizar/agregar') ?>">
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
        $baseUrl = url('/portal/cotizar') . ($qs ? '?' . http_build_query($qs) : '');
        require APP_PATH . '/Views/partials/pagination.php';
        ?>
    </div>

    <aside class="cart">
        <div class="cart__title">Tu solicitud (<?= count($carrito) ?>)</div>

        <?php if ($carrito): ?>
            <?php foreach ($carrito as $c): ?>
                <div class="cart__item">
                    <div class="order-item__info">
                        <div class="order-item__name"><?= e($c['nombre']) ?></div>
                        <div class="order-item__meta"><?= (int) $c['cantidad'] ?><?= $c['unidad'] !== '' ? ' × ' . e($c['unidad']) : ' pza(s)' ?></div>
                    </div>
                    <form method="post" action="<?= url('/portal/cotizar/quitar') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="producto_id" value="<?= (int) $c['id'] ?>">
                        <button class="btn btn--outline btn--sm" type="submit" aria-label="Quitar <?= e($c['nombre']) ?>">Quitar</button>
                    </form>
                </div>
            <?php endforeach; ?>

            <form method="post" action="<?= url('/portal/cotizar') ?>" class="mt-6">
                <?= csrf_field() ?>
                <div class="field mb-4">
                    <label for="mensaje">Detalle o indicaciones (opcional)</label>
                    <textarea id="mensaje" name="mensaje" placeholder="Personalización, fecha requerida, etc."></textarea>
                </div>
                <button type="submit" class="btn btn--accent btn--block">Enviar solicitud</button>
            </form>
        <?php else: ?>
            <p class="cart__empty">Aún no agregas productos a tu solicitud.</p>
        <?php endif; ?>
    </aside>
</div>
