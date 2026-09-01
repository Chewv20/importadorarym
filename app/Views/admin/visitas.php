<?php
/**
 * @var array $visitas
 * @var array $anfitriones
 * @var array $filtro
 * @var int $page
 * @var int $pages
 */
?>
<?php
$qFiltros = array_filter(['desde' => $filtro['desde'] ?? '', 'hasta' => $filtro['hasta'] ?? '', 'anfitrion' => $filtro['anfitrion_id'] ?? '']);
$exportUrl = url('/admin/visitas/exportar') . ($qFiltros ? '?' . http_build_query($qFiltros) : '');
?>
<div class="admin-head">
    <h1>Libreta de visitas</h1>
    <?php if ($visitas): ?>
        <a class="btn btn--primary" href="<?= e($exportUrl) ?>">Exportar a Excel</a>
    <?php endif; ?>
</div>

<div class="filters-row">
    <a class="filter-tab is-active" href="<?= url('/admin/visitas') ?>">Registros</a>
    <?php if (can('visitas.gestionar')): ?>
        <a class="filter-tab" href="<?= url('/admin/visitas/dispositivos') ?>">Dispositivos</a>
        <a class="filter-tab" href="<?= url('/admin/visitas/anfitriones') ?>">Anfitriones</a>
    <?php endif; ?>
</div>

<form class="filters-row" method="get" action="<?= url('/admin/visitas') ?>">
    <input type="date" name="desde" value="<?= e($filtro['desde'] ?? '') ?>" aria-label="Desde">
    <input type="date" name="hasta" value="<?= e($filtro['hasta'] ?? '') ?>" aria-label="Hasta">
    <select name="anfitrion" aria-label="Anfitrión">
        <option value="">Todos los anfitriones</option>
        <?php foreach ($anfitriones as $a): ?>
            <option value="<?= (int) $a['id'] ?>" <?= (int) ($filtro['anfitrion_id'] ?? 0) === (int) $a['id'] ? 'selected' : '' ?>>
                <?= e($a['nombre']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn--accent">Filtrar</button>
    <a class="btn btn--outline" href="<?= url('/admin/visitas') ?>">Limpiar</a>
</form>

<?php if ($visitas): ?>
<div class="table-wrap">
    <table class="table">
        <thead>
            <tr><th>Fecha</th><th>Visitante</th><th>Empresa</th><th>Visita a</th><th>Motivo</th><th>Pers.</th><th>Punto</th></tr>
        </thead>
        <tbody>
        <?php foreach ($visitas as $v): ?>
            <tr>
                <td><?= e(date('d/m/Y H:i', strtotime($v['created_at']))) ?></td>
                <td>
                    <?= e($v['nombre_visitante']) ?>
                    <?= !empty($v['telefono']) ? '<br><span class="text-muted fs-sm">' . e($v['telefono']) . '</span>' : '' ?>
                </td>
                <td><?= !empty($v['empresa']) ? e($v['empresa']) : '—' ?></td>
                <td>
                    <?= e($v['anfitrion_nombre'] ?? '—') ?>
                    <?= !empty($v['anfitrion_area']) ? '<br><span class="text-muted fs-sm">' . e($v['anfitrion_area']) . '</span>' : '' ?>
                </td>
                <td><?= !empty($v['motivo']) ? e($v['motivo']) : '—' ?></td>
                <td><?= (int) $v['num_personas'] ?></td>
                <td class="text-muted fs-sm"><?= !empty($v['dispositivo_nombre']) ? e($v['dispositivo_nombre']) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$baseUrl = url('/admin/visitas') . ($qFiltros ? '?' . http_build_query($qFiltros) : '');
require APP_PATH . '/Views/partials/pagination.php';
?>
<?php else: ?>
    <p class="empty-state">No hay visitas registradas con esos filtros.</p>
<?php endif; ?>
