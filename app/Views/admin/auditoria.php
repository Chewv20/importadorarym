<?php
/** @var array $registros @var array $filtros @var int $page @var int $pages */
$accionLabel = [
    'login'          => 'Ingreso',
    'login_fallido'  => 'Ingreso fallido',
    'logout'         => 'Salida',
    'crear'          => 'Creó',
    'actualizar'     => 'Editó',
    'eliminar'       => 'Eliminó',
    'aprobar'        => 'Aprobó',
];
// Construye una URL de /admin/auditoria preservando los filtros activos.
$mk = static function (array $over = []) use ($filtros): string {
    $q = array_filter(array_merge($filtros, $over), static fn ($v) => $v !== null && $v !== '');
    return url('/admin/auditoria') . ($q ? '?' . http_build_query($q) : '');
};
$hayFiltros = (bool) array_filter($filtros, static fn ($v) => $v !== null && $v !== '');
$tipo = $filtros['tipo'];
?>
<div class="admin-head"><h1>Auditoría</h1></div>

<div class="filters-row">
    <a class="filter-tab <?= $tipo === null ? 'is-active' : '' ?>" href="<?= e($mk(['tipo' => null])) ?>">Todo</a>
    <a class="filter-tab <?= $tipo === 'ingreso' ? 'is-active' : '' ?>" href="<?= e($mk(['tipo' => 'ingreso'])) ?>">Ingresos</a>
    <a class="filter-tab <?= $tipo === 'cambio' ? 'is-active' : '' ?>" href="<?= e($mk(['tipo' => 'cambio'])) ?>">Cambios</a>
</div>

<form class="audit-filters" method="get" action="<?= url('/admin/auditoria') ?>">
    <?php if ($tipo): ?><input type="hidden" name="tipo" value="<?= e($tipo) ?>"><?php endif; ?>
    <input class="audit-filters__search" type="search" name="usuario" value="<?= e($filtros['usuario'] ?? '') ?>" placeholder="Correo del usuario…">
    <label class="audit-filters__date">Desde <input type="date" name="desde" value="<?= e($filtros['desde'] ?? '') ?>"></label>
    <label class="audit-filters__date">Hasta <input type="date" name="hasta" value="<?= e($filtros['hasta'] ?? '') ?>"></label>
    <button class="btn btn--primary btn--sm" type="submit">Filtrar</button>
    <?php if ($hayFiltros): ?>
        <a class="btn btn--outline btn--sm" href="<?= url('/admin/auditoria') ?>">Limpiar</a>
    <?php endif; ?>
</form>

<?php if ($registros): ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Fecha</th><th>Usuario</th><th>Tipo</th><th>Acción</th><th>Detalle</th><th>IP</th></tr></thead>
        <tbody>
        <?php foreach ($registros as $r): ?>
            <?php $fallido = $r['accion'] === 'login_fallido'; ?>
            <tr>
                <td class="nowrap"><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
                <td><?= e($r['usuario_email'] ?? '—') ?></td>
                <td>
                    <span class="pill <?= $r['tipo'] === 'ingreso' ? 'pill--ok' : 'pill--warn' ?>"><?= e(ucfirst($r['tipo'])) ?></span>
                </td>
                <td>
                    <span class="pill <?= $fallido ? 'pill--danger' : 'pill--off' ?>"><?= e($accionLabel[$r['accion']] ?? $r['accion']) ?></span>
                    <?= $r['entidad'] ? '<span class="text-muted fs-sm">' . e($r['entidad']) . '</span>' : '' ?>
                </td>
                <td><?= e($r['descripcion'] ?? '—') ?></td>
                <td class="nowrap text-muted fs-sm"><?= e($r['ip'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$baseUrl = $mk([]);
require APP_PATH . '/Views/partials/pagination.php';
?>
<?php else: ?>
    <p class="empty-state">No hay registros de auditoría que coincidan con los filtros.</p>
<?php endif; ?>
