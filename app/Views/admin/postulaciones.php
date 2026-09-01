<?php
/** @var array $postulaciones @var array $vacantes @var array $estados @var array $filtro @var int $page @var int $pages */
$pill = [
    'recibida'    => 'pill--warn',
    'en_revision' => 'pill--off',
    'entrevista'  => 'pill--ok',
    'rechazada'   => 'pill--danger',
    'contratada'  => 'pill--ok',
];
?>
<div class="admin-head"><h1>Postulaciones</h1></div>

<form class="filters-row" method="get" action="<?= url('/admin/postulaciones') ?>">
    <select name="vacante" aria-label="Vacante">
        <option value="">Todas las vacantes</option>
        <option value="general" <?= ($filtro['vacante_id'] ?? '') === 'general' ? 'selected' : '' ?>>Solicitudes generales</option>
        <?php foreach ($vacantes as $v): ?>
            <option value="<?= (int) $v['id'] ?>" <?= (int) ($filtro['vacante_id'] ?? 0) === (int) $v['id'] ? 'selected' : '' ?>><?= e($v['titulo']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="estado" aria-label="Estado">
        <option value="">Todos los estados</option>
        <?php foreach ($estados as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= ($filtro['estado'] ?? '') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn--accent">Filtrar</button>
    <a class="btn btn--outline" href="<?= url('/admin/postulaciones') ?>">Limpiar</a>
</form>

<?php if ($postulaciones): ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Fecha</th><th>Candidato</th><th>Vacante</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($postulaciones as $p): ?>
            <tr>
                <td><?= e(date('d/m/Y H:i', strtotime($p['created_at']))) ?></td>
                <td><?= e($p['nombre']) ?><br><span class="text-muted fs-sm"><?= e($p['email']) ?></span></td>
                <td>
                    <?php if (!empty($p['vacante_titulo'])): ?>
                        <?= e($p['vacante_titulo']) ?>
                    <?php elseif (!empty($p['area_interes'])): ?>
                        <span class="pill pill--off">Solicitud general</span><br><span class="text-muted fs-sm"><?= e($p['area_interes']) ?></span>
                    <?php else: ?>
                        <span class="pill pill--off">Solicitud general</span>
                    <?php endif; ?>
                </td>
                <td><span class="pill <?= $pill[$p['estado']] ?? '' ?>"><?= e($estados[$p['estado']] ?? $p['estado']) ?></span></td>
                <td><a class="btn btn--outline btn--sm" href="<?= url('/admin/postulaciones/' . (int) $p['id']) ?>">Ver</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$q = array_filter(['vacante' => $filtro['vacante_id'] ?? '', 'estado' => $filtro['estado'] ?? '']);
$baseUrl = url('/admin/postulaciones') . ($q ? '?' . http_build_query($q) : '');
require APP_PATH . '/Views/partials/pagination.php';
?>
<?php else: ?>
    <p class="empty-state">No hay postulaciones con esos filtros.</p>
<?php endif; ?>
