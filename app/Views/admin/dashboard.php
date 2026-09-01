<div class="admin-head"><h1>Dashboard</h1></div>

<?php if (!($puedeVer ?? false)): ?>
    <p class="empty-state">No tienes permiso para ver las estadísticas del panel.</p>
    <?php return; ?>
<?php endif; ?>

<?php if ($soloAsignados ?? false): ?>
    <p class="text-muted fs-sm mb-4">👤 Estas métricas corresponden solo a tus clientes asignados.</p>
<?php endif; ?>

<div class="metric-grid">
    <div class="metric <?= $leadsNuevos > 0 ? 'metric--alert' : '' ?>">
        <div class="metric__num"><?= (int) $leadsNuevos ?></div>
        <div class="metric__label">Cotizaciones nuevas</div>
    </div>
    <div class="metric <?= $clientesPend > 0 ? 'metric--alert' : '' ?>">
        <div class="metric__num"><?= (int) $clientesPend ?></div>
        <div class="metric__label">Clientes por aprobar</div>
    </div>
    <div class="metric">
        <div class="metric__num"><?= (int) $leadsTotal ?></div>
        <div class="metric__label">Cotizaciones totales</div>
    </div>
    <div class="metric">
        <div class="metric__num"><?= (int) $pedidosTotal ?></div>
        <div class="metric__label">Pedidos</div>
    </div>
    <div class="metric">
        <div class="metric__num"><?= (int) $productosActivos ?></div>
        <div class="metric__label">Productos activos</div>
    </div>
</div>

<?php
$estadoLabel = static fn (string $e): string => ucfirst(str_replace('_', ' ', $e));
?>
<div class="dash-charts">
    <?php if (can('pedidos.ver_todos') && !empty($pedidosPorEstado)): ?>
    <div class="chart-card">
        <h2 class="chart-card__title">Pedidos por estado</h2>
        <?php $maxP = max(1, max($pedidosPorEstado)); ?>
        <?php foreach ($pedidosPorEstado as $estado => $n): ?>
            <div class="hbar">
                <span class="hbar__label"><?= e($estadoLabel($estado)) ?></span>
                <span class="hbar__track"><span class="hbar__fill" style="--pct: <?= $n > 0 ? (int) round($n / $maxP * 100) : 0 ?>"></span></span>
                <span class="hbar__val"><?= (int) $n ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (can('cotizaciones.ver') && !empty($cotsPorEstado)): ?>
    <div class="chart-card">
        <h2 class="chart-card__title">Cotizaciones por estado</h2>
        <?php $maxC = max(1, max($cotsPorEstado)); ?>
        <?php foreach ($cotsPorEstado as $estado => $n): ?>
            <div class="hbar">
                <span class="hbar__label"><?= e($estadoLabel($estado)) ?></span>
                <span class="hbar__track"><span class="hbar__fill hbar__fill--accent" style="--pct: <?= $n > 0 ? (int) round($n / $maxC * 100) : 0 ?>"></span></span>
                <span class="hbar__val"><?= (int) $n ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (can('cotizaciones.ver') && !empty($cotsPorSemana)): ?>
    <div class="chart-card">
        <h2 class="chart-card__title">Cotizaciones por semana</h2>
        <?php $maxW = max(1, max(array_column($cotsPorSemana, 'valor'))); ?>
        <div class="vbars">
            <?php foreach ($cotsPorSemana as $w): ?>
                <div class="vbar">
                    <div class="vbar__area">
                        <span class="vbar__num"><?= (int) $w['valor'] ?></span>
                        <span class="vbar__col" style="--px: <?= $w['valor'] > 0 ? max(4, (int) round($w['valor'] / $maxW * 130)) : 2 ?>"></span>
                    </div>
                    <span class="vbar__label"><?= e($w['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (can('cotizaciones.ver')): ?>
<h2 class="portal-subtitle mt-12">Últimas cotizaciones</h2>
<?php if ($recientes): ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Nombre</th><th>Empresa</th><th>Correo</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($recientes as $c): ?>
                <tr>
                    <td><?= e(date('d/m/Y', strtotime($c['created_at']))) ?></td>
                    <td><?= e($c['nombre']) ?></td>
                    <td><?= e($c['empresa'] ?? '—') ?></td>
                    <td><?= e($c['email']) ?></td>
                    <td><span class="status status--<?= $c['estado'] === 'nueva' ? 'enviado' : 'sincronizado' ?>"><?= e(ucfirst($c['estado'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="mt-4"><a class="btn btn--outline btn--sm" href="<?= url('/admin/cotizaciones') ?>">Ver todas</a></p>
<?php else: ?>
    <p class="empty-state">Aún no hay cotizaciones.</p>
<?php endif; ?>
<?php endif; ?>
