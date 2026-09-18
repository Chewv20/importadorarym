<?php
/** @var ?array $cliente @var array $productos @var array $carrito @var array $categorias
 *  @var array $clientes @var string $q @var string $catActual @var int $page @var int $pages */
$cliente   = $cliente ?? null;
$productos = $productos ?? [];
$carrito   = $carrito ?? [];
$clientes  = $clientes ?? [];
?>
<div class="admin-head"><h1>Nuevo pedido para un cliente</h1></div>

<?php if (!$cliente): ?>
    <p class="text-muted mb-6">Busca al cliente para el que vas a levantar el pedido. No es necesario que su cuenta esté aprobada.</p>
    <form class="prodpicker" data-clientpicker method="post" action="<?= url('/admin/pedidos/nuevo/cliente') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="cliente_id" value="">
        <div class="field mb-4">
            <label for="clientpicker_input">Cliente</label>
            <input class="prodpicker__input" id="clientpicker_input" type="text" placeholder="Nombre o empresa…" autocomplete="off">
            <div class="prodpicker__results" hidden></div>
        </div>
        <button type="submit" class="btn btn--accent">Continuar</button>
    </form>

    <?php if ($clientes): ?>
        <p class="text-muted fs-sm mt-6 mb-4">— o elige de tus clientes asignados —</p>
        <form method="post" action="<?= url('/admin/pedidos/nuevo/cliente') ?>">
            <?= csrf_field() ?>
            <div class="field mb-4">
                <label for="clientpicker_select">Cliente</label>
                <select id="clientpicker_select" name="cliente_id" required>
                    <option value="">— Selecciona —</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= (int) $c['id'] ?>">
                            <?= e($c['nombre']) ?><?= $c['empresa'] ? ' · ' . e($c['empresa']) : '' ?><?= (int) $c['aprobado'] !== 1 ? ' (pendiente de aprobación)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn--outline">Continuar</button>
        </form>
    <?php endif; ?>
<?php else: ?>
    <div class="admin-head mb-6">
        <p class="text-muted">
            Pedido para <strong><?= e($cliente['nombre']) ?></strong><?= $cliente['empresa'] ? ' · ' . e($cliente['empresa']) : '' ?>
            <?php if ((int) ($cliente['aprobado'] ?? 0) !== 1): ?>
                <span class="pill pill--off">Cuenta pendiente de aprobación</span>
            <?php endif; ?>
        </p>
        <form method="post" action="<?= url('/admin/pedidos/nuevo/cliente/cambiar') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--outline btn--sm">Cambiar cliente</button>
        </form>
    </div>

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
                                    <?= e($p['categoria'] ?? '') ?><?= !empty($p['unidad']) ? ' · ' . e($p['unidad']) : '' ?><?= !empty($p['minimo_efectivo']) ? ' · mín. ' . (int) $p['minimo_efectivo'] . ' pzas' : '' ?>
                                </div>
                            </div>
                        </div>
                        <form class="qty-form" method="post" action="<?= url('/admin/pedidos/nuevo/carrito/agregar') ?>">
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
            $baseUrl = url('/admin/pedidos/nuevo') . ($qs ? '?' . http_build_query($qs) : '');
            require APP_PATH . '/Views/partials/pagination.php';
            ?>
        </div>

        <!-- Carrito -->
        <aside class="cart">
            <div class="cart__title">Partidas (<?= count($carrito) ?>)</div>

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
                        <form method="post" action="<?= url('/admin/pedidos/nuevo/carrito/quitar') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="producto_id" value="<?= (int) $c['id'] ?>">
                            <button class="btn btn--outline btn--sm" type="submit" aria-label="Quitar <?= e($c['nombre']) ?>">Quitar</button>
                        </form>
                    </div>
                <?php endforeach; ?>

                <a href="<?= url('/admin/pedidos/nuevo/confirmar') ?>" class="btn btn--accent btn--block mt-6">Revisar y confirmar</a>
            <?php else: ?>
                <p class="cart__empty">Aún no agregas productos a este pedido.</p>
            <?php endif; ?>
        </aside>
    </div>
<?php endif; ?>
