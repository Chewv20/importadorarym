<?php
/** @var array $oficinas */
?>
<div class="admin-head"><h1>Libreta de visitas</h1></div>

<div class="filters-row">
    <a class="filter-tab" href="<?= url('/admin/visitas') ?>">Registros</a>
    <a class="filter-tab is-active" href="<?= url('/admin/visitas/oficinas') ?>">Oficinas</a>
    <a class="filter-tab" href="<?= url('/admin/visitas/dispositivos') ?>">Dispositivos</a>
    <a class="filter-tab" href="<?= url('/admin/visitas/anfitriones') ?>">Anfitriones</a>
</div>

<div class="card-panel mb-8">
    <h2 class="card-panel__title">Agregar oficina</h2>
    <p class="text-muted fs-sm">Cada dispositivo de recepción se asocia a una oficina y las visitas quedan separadas por ella.
    Los usuarios ven solo la bitácora de su oficina, salvo que tengan el permiso «Ver la bitácora de todas las oficinas».</p>
    <form class="filters-row" method="post" action="<?= url('/admin/visitas/oficinas') ?>">
        <?= csrf_field() ?>
        <input type="text" name="nombre" placeholder="Nombre de la oficina" maxlength="120" required>
        <label class="check-inline"><input type="checkbox" name="activa" value="1" checked> Activa</label>
        <button type="submit" class="btn btn--accent">Agregar</button>
    </form>
</div>

<?php if ($oficinas): ?>
<?php /* Formularios (solo CSRF) fuera de la tabla; los campos se asocian con el atributo form=. */ ?>
<?php foreach ($oficinas as $o): ?>
    <form id="ofi-<?= (int) $o['id'] ?>" method="post" action="<?= url('/admin/visitas/oficinas/' . (int) $o['id']) ?>"><?= csrf_field() ?></form>
    <form id="ofidel-<?= (int) $o['id'] ?>" method="post" action="<?= url('/admin/visitas/oficinas/' . (int) $o['id'] . '/eliminar') ?>"
          data-confirm="¿Eliminar esta oficina? Sus dispositivos y visitas quedarán sin oficina."><?= csrf_field() ?></form>
<?php endforeach; ?>

<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Nombre</th><th>Activa</th><th>Dispositivos</th><th>Visitas</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($oficinas as $o): $fid = 'ofi-' . (int) $o['id']; ?>
            <tr>
                <td><input class="cell-input" form="<?= $fid ?>" type="text" name="nombre" value="<?= e($o['nombre']) ?>" required></td>
                <td><input form="<?= $fid ?>" type="checkbox" name="activa" value="1" <?= (int) $o['activa'] === 1 ? 'checked' : '' ?>></td>
                <td class="text-muted fs-sm"><?= (int) $o['num_dispositivos'] ?></td>
                <td class="text-muted fs-sm"><?= (int) $o['num_visitas'] ?></td>
                <td class="nowrap">
                    <button type="submit" form="<?= $fid ?>" class="btn btn--outline btn--sm">Guardar</button>
                    <button type="submit" form="ofidel-<?= (int) $o['id'] ?>" class="btn btn--outline btn--sm">Eliminar</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <p class="empty-state">Aún no hay oficinas. Agrega al menos una para separar la bitácora por oficina.</p>
<?php endif; ?>
