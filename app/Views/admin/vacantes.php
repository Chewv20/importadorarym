<?php
/** @var array $vacantes @var array $tipos */
?>
<div class="admin-head">
    <h1>Vacantes</h1>
    <a class="btn btn--accent" href="<?= url('/admin/vacantes/nueva') ?>">+ Nueva vacante</a>
</div>

<?php if ($vacantes): ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Puesto</th><th>Tipo</th><th>Estado</th><th>Postulaciones</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($vacantes as $v): ?>
            <tr>
                <td>
                    <?= e($v['titulo']) ?>
                    <?= !empty($v['area']) ? '<br><span class="text-muted fs-sm">' . e($v['area']) . '</span>' : '' ?>
                </td>
                <td><?= e($tipos[$v['tipo']] ?? $v['tipo']) ?></td>
                <td><span class="pill <?= $v['estado'] === 'abierta' ? 'pill--ok' : 'pill--off' ?>"><?= $v['estado'] === 'abierta' ? 'Abierta' : 'Cerrada' ?></span></td>
                <td>
                    <?php if ((int) $v['num_postulaciones'] > 0): ?>
                        <a href="<?= url('/admin/postulaciones?vacante=' . (int) $v['id']) ?>"><?= (int) $v['num_postulaciones'] ?></a>
                    <?php else: ?>
                        0
                    <?php endif; ?>
                </td>
                <td class="nowrap">
                    <a class="btn btn--outline btn--sm" href="<?= url('/admin/vacantes/' . (int) $v['id'] . '/editar') ?>">Editar</a>
                    <form method="post" action="<?= url('/admin/vacantes/' . (int) $v['id'] . '/eliminar') ?>" class="inline-form"
                          data-confirm="¿Eliminar esta vacante? Las postulaciones se conservan.">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--outline btn--sm">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <p class="empty-state">Aún no hay vacantes. Crea la primera con "Nueva vacante".</p>
<?php endif; ?>
