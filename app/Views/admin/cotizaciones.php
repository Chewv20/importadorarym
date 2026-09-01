<?php
/** @var array $cotizaciones @var array $estados @var ?string $estado */
$pill = [
    'nueva'      => ['Nueva', 'pill--warn'],
    'cotizada'   => ['Cotizada', 'pill--ok'],
    'aprobada'   => ['Aprobada', 'pill--ok'],
    'rechazada'  => ['Rechazada', 'pill--danger'],
    'convertida' => ['Convertida', 'pill--off'],
];
?>
<div class="admin-head"><h1>Cotizaciones</h1></div>

<?php if ($soloAsignados ?? false): ?>
    <p class="text-muted fs-sm mb-4">👤 Mostrando solo las cotizaciones de tus clientes asignados.</p>
<?php endif; ?>

<div class="filters-row">
    <a class="filter-tab <?= $estado === null ? 'is-active' : '' ?>" href="<?= url('/admin/cotizaciones') ?>">Todas</a>
    <?php foreach ($estados as $e): ?>
        <a class="filter-tab <?= $estado === $e ? 'is-active' : '' ?>" href="<?= url('/admin/cotizaciones?estado=' . $e) ?>"><?= e(ucfirst($e)) ?></a>
    <?php endforeach; ?>
</div>

<?php if ($cotizaciones): ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Folio</th><th>Fecha</th><th>Cliente</th><th>Origen</th><th>Estado</th><th class="ta-right">Total</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($cotizaciones as $c): ?>
            <?php [$txt, $cls] = $pill[$c['estado']] ?? [$c['estado'], 'pill--off']; ?>
            <tr>
                <td><strong><?= e($c['folio'] ?? '—') ?></strong></td>
                <td class="nowrap"><?= e(date('d/m/Y H:i', strtotime($c['created_at']))) ?></td>
                <td>
                    <?= e($c['nombre']) ?><?= $c['empresa'] ? '<br><span class="text-muted fs-sm">' . e($c['empresa']) . '</span>' : '' ?>
                </td>
                <td><span class="text-muted fs-sm"><?= e($c['origen']) ?></span></td>
                <td><span class="pill <?= $cls ?>"><?= e($txt) ?></span></td>
                <td class="ta-right"><?= $c['total'] !== null ? '$' . number_format((float) $c['total'], 2) : '—' ?></td>
                <td><a class="btn btn--primary btn--sm" href="<?= url('/admin/cotizaciones/' . (int) $c['id']) ?>">Abrir</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$baseUrl = url('/admin/cotizaciones') . ($estado ? '?estado=' . $estado : '');
require APP_PATH . '/Views/partials/pagination.php';
?>
<?php else: ?>
    <p class="empty-state">No hay cotizaciones<?= $estado ? ' con estado «' . e($estado) . '»' : '' ?>.</p>
<?php endif; ?>
