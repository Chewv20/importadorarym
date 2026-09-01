<?php
/** @var array $metricas @var array $encuestas @var array $filtro @var int $page @var int $pages */
$m = $metricas;
$maxDist = max(1, max($m['distribucion']));
$estrellas = static fn(int $n): string => str_repeat('★', $n) . str_repeat('☆', 5 - $n);
$q = array_filter(['desde' => $filtro['desde'] ?? '', 'hasta' => $filtro['hasta'] ?? '']);
$exportUrl = url('/admin/encuestas/exportar') . ($q ? '?' . http_build_query($q) : '');
?>
<div class="admin-head">
    <h1>Encuestas de experiencia</h1>
    <?php if ($m['total'] > 0): ?>
        <a class="btn btn--primary" href="<?= e($exportUrl) ?>">Exportar a Excel</a>
    <?php endif; ?>
</div>

<div class="filters-row">
    <a class="filter-tab is-active" href="<?= url('/admin/encuestas') ?>">Proceso de compra</a>
    <a class="filter-tab" href="<?= url('/admin/encuestas/entregas') ?>">Satisfacción de entrega</a>
</div>

<form class="filters-row" method="get" action="<?= url('/admin/encuestas') ?>">
    <input type="date" name="desde" value="<?= e($filtro['desde'] ?? '') ?>" aria-label="Desde">
    <input type="date" name="hasta" value="<?= e($filtro['hasta'] ?? '') ?>" aria-label="Hasta">
    <button type="submit" class="btn btn--accent">Filtrar</button>
    <a class="btn btn--outline" href="<?= url('/admin/encuestas') ?>">Limpiar</a>
</form>

<?php if ($m['total'] === 0): ?>
    <p class="empty-state">Aún no hay respuestas de encuesta con esos filtros.</p>
<?php else: ?>

<div class="metric-grid">
    <div class="metric">
        <div class="metric__num"><?= number_format($m['prom_satisfaccion'], 1) ?><span class="metric__unit">/5</span></div>
        <div class="metric__label">Satisfacción promedio</div>
    </div>
    <div class="metric">
        <div class="metric__num"><?= number_format($m['prom_facilidad'], 1) ?><span class="metric__unit">/5</span></div>
        <div class="metric__label">Facilidad promedio</div>
    </div>
    <div class="metric">
        <div class="metric__num"><?= $m['nps'] !== null ? (int) $m['nps'] : '—' ?></div>
        <div class="metric__label">NPS <?= $m['con_nps'] > 0 ? '(' . (int) $m['con_nps'] . ' resp.)' : '' ?></div>
    </div>
    <div class="metric">
        <div class="metric__num"><?= (int) $m['total'] ?></div>
        <div class="metric__label">Respuestas</div>
    </div>
</div>

<div class="card-panel mb-8">
    <h2 class="card-panel__title">Distribución de satisfacción</h2>
    <?php for ($n = 5; $n >= 1; $n--): $c = $m['distribucion'][$n]; ?>
        <div class="hbar">
            <span class="hbar__label"><?= $estrellas($n) ?></span>
            <span class="hbar__track"><span class="hbar__fill" style="--pct: <?= (int) round($c / $maxDist * 100) ?>"></span></span>
            <span class="hbar__val"><?= (int) $c ?></span>
        </div>
    <?php endfor; ?>
</div>

<h2 class="portal-subtitle">Comentarios</h2>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Fecha</th><th>Pedido</th><th>Cliente</th><th>Satisf.</th><th>Facil.</th><th>NPS</th><th>Comentario</th></tr></thead>
        <tbody>
        <?php foreach ($encuestas as $e): ?>
            <tr>
                <td class="nowrap"><?= e(date('d/m/Y', strtotime($e['created_at']))) ?></td>
                <td><?= !empty($e['pedido_folio']) ? e($e['pedido_folio']) : '—' ?></td>
                <td><?= e($e['cliente_nombre'] ?? '—') ?><?= !empty($e['cliente_empresa']) ? '<br><span class="text-muted fs-sm">' . e($e['cliente_empresa']) . '</span>' : '' ?></td>
                <td class="nowrap stars-readonly"><?= $estrellas((int) $e['satisfaccion']) ?></td>
                <td class="nowrap stars-readonly"><?= $estrellas((int) $e['facilidad']) ?></td>
                <td><?= $e['nps'] !== null ? (int) $e['nps'] : '—' ?></td>
                <td><?= !empty($e['comentario']) ? e($e['comentario']) : '<span class="text-muted">—</span>' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$baseUrl = url('/admin/encuestas') . ($q ? '?' . http_build_query($q) : '');
require APP_PATH . '/Views/partials/pagination.php';
?>

<?php endif; ?>
