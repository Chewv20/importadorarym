<?php
/** @var array $pedidos @var array $estados @var ?string $estado @var ?string $busqueda */
$labels = ['borrador' => 'Borrador', 'enviado' => 'Enviado', 'en_proceso' => 'En proceso', 'parcial' => 'Parcial', 'sincronizado' => 'Sincronizado', 'cancelado' => 'Cancelado'];
?>
<div class="admin-head">
    <h1>Pedidos</h1>
    <div class="admin-head__actions">
        <?php if (can('pedidos.crear_para_cliente')): ?>
            <a class="btn btn--outline" href="<?= url('/admin/pedidos/nuevo') ?>">+ Nuevo pedido para un cliente</a>
        <?php endif; ?>
        <?php if (can('pedidos.sincronizar_erp') && !($soloAsignados ?? false)): ?>
            <a class="btn btn--outline" href="<?= url('/admin/series-sae') ?>">Series de SAE</a>
            <a class="btn btn--primary" href="<?= url('/admin/pedidos/exportar') ?>">Exportar pendientes a SAE</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($soloAsignados ?? false): ?>
    <p class="text-muted fs-sm mb-4">👤 Mostrando solo los pedidos de tus clientes asignados.</p>
<?php endif; ?>

<form class="filters-row" method="get" action="<?= url('/admin/pedidos') ?>">
    <a class="filter-tab <?= $estado === null ? 'is-active' : '' ?>" href="<?= url('/admin/pedidos') ?>">Todos</a>
    <?php foreach ($estados as $e): ?>
        <a class="filter-tab <?= $estado === $e ? 'is-active' : '' ?>" href="<?= url('/admin/pedidos?estado=' . $e) ?>"><?= e($labels[$e] ?? $e) ?></a>
    <?php endforeach; ?>
    <input type="search" name="q" value="<?= e($busqueda ?? '') ?>" placeholder="Buscar folio o cliente…" class="filter-search">
    <button class="btn btn--primary btn--sm" type="submit">Buscar</button>
</form>

<?php if ($pedidos): ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Folio</th><th>Cliente</th><th>Fecha</th><th>Artículos</th><th>Estado</th><th>ERP</th><th><span class="sr-only">Acciones</span></th></tr></thead>
        <tbody>
        <?php foreach ($pedidos as $p): ?>
            <tr>
                <td><?= e($p['folio']) ?></td>
                <td><?= e($p['cliente_nombre']) ?><?= $p['cliente_empresa'] ? '<br><span class="text-muted">' . e($p['cliente_empresa']) . '</span>' : '' ?></td>
                <td><?= e(date('d/m/Y', strtotime($p['created_at']))) ?></td>
                <td><?= (int) $p['num_items'] ?></td>
                <td><span class="status status--<?= e($p['estado']) ?>"><?= e($labels[$p['estado']] ?? $p['estado']) ?></span></td>
                <td><?= !empty($p['erp_folio']) ? e($p['erp_folio']) : '—' ?></td>
                <td><a class="btn btn--outline btn--sm" href="<?= url('/admin/pedidos/' . (int) $p['id']) ?>">Ver</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$baseUrl = url('/admin/pedidos') . ($estado ? '?estado=' . $estado : ($busqueda ? '?q=' . rawurlencode($busqueda) : ''));
require APP_PATH . '/Views/partials/pagination.php';
?>
<?php else: ?>
    <p class="empty-state">No hay pedidos<?= $estado || $busqueda ? ' con ese criterio' : '' ?>.</p>
<?php endif; ?>
