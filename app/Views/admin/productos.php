<?php /** @var array $productos @var ?string $busqueda */ $busqueda = $busqueda ?? null; ?>
<div class="admin-head">
    <h1>Productos</h1>
    <?php if (can('productos.crear')): ?>
        <div class="admin-head__actions">
            <a class="btn btn--outline" href="<?= url('/admin/productos/importar') ?>">Importar CSV</a>
            <a class="btn btn--accent" href="<?= url('/admin/productos/nuevo') ?>">+ Nuevo producto</a>
        </div>
    <?php endif; ?>
</div>

<form class="filters-row" method="get" action="<?= url('/admin/productos') ?>">
    <input type="search" name="q" value="<?= e($busqueda ?? '') ?>" placeholder="Buscar nombre, SKU o clave SAE…" class="filter-search" aria-label="Buscar productos">
    <button class="btn btn--primary btn--sm" type="submit">Buscar</button>
    <?php if ($busqueda): ?><a class="btn btn--outline btn--sm" href="<?= url('/admin/productos') ?>">Limpiar</a><?php endif; ?>
</form>

<?php if ($productos): ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Nombre</th><th>Categoría</th><th>SKU</th><th>Clave SAE</th><th>Esq. imp.</th><th>Presentación</th><th>Destacado</th><th>Personalizable</th><th>Activo</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($productos as $p): ?>
            <tr>
                <td><?= e($p['nombre']) ?></td>
                <td><?= e($p['categoria'] ?? '—') ?></td>
                <td><?= e($p['sku'] ?? '—') ?></td>
                <td><?= !empty($p['clave_sae']) ? e($p['clave_sae']) : '<span class="text-muted">—</span>' ?></td>
                <td>
                    <?php if ($p['esquema_impuestos'] !== null): ?>
                        <?= (int) $p['esquema_impuestos'] ?>
                    <?php elseif (($esquemaGeneral ?? null) !== null): ?>
                        <span class="text-muted" title="Usa el esquema general del .env"><?= (int) $esquemaGeneral ?> *</span>
                    <?php else: ?>
                        <span class="pill pill--off" title="Sin esto no se puede exportar el pedido a SAE">falta</span>
                    <?php endif; ?>
                </td>
                <td><?= e($p['unidad'] ?? '—') ?></td>
                <td><span class="pill <?= (int) $p['destacado'] === 1 ? 'pill--ok' : 'pill--off' ?>"><?= (int) $p['destacado'] === 1 ? 'Sí' : 'No' ?></span></td>
                <td><span class="pill <?= (int) $p['personalizable'] === 1 ? 'pill--ok' : 'pill--off' ?>"><?= (int) $p['personalizable'] === 1 ? 'Sí' : 'No' ?></span></td>
                <td><span class="pill <?= (int) $p['activo'] === 1 ? 'pill--ok' : 'pill--off' ?>"><?= (int) $p['activo'] === 1 ? 'Sí' : 'No' ?></span></td>
                <td>
                    <?php if (can('productos.editar')): ?>
                        <a class="btn btn--outline btn--sm" href="<?= url('/admin/productos/' . (int) $p['id'] . '/editar') ?>">Editar</a>
                    <?php endif; ?>
                    <?php if (can('productos.eliminar')): ?>
                        <form class="inline-form" method="post" action="<?= url('/admin/productos/' . (int) $p['id'] . '/eliminar') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn--outline btn--sm" type="submit">Eliminar</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php $baseUrl = url('/admin/productos') . ($busqueda ? '?' . http_build_query(['q' => $busqueda]) : ''); require APP_PATH . '/Views/partials/pagination.php'; ?>
<?php else: ?>
    <?php if ($busqueda): ?>
        <p class="empty-state">Ningún producto coincide con «<?= e($busqueda) ?>».</p>
    <?php else: ?>
        <p class="empty-state">No hay productos. <a href="<?= url('/admin/productos/nuevo') ?>">Crea el primero</a>.</p>
    <?php endif; ?>
<?php endif; ?>
