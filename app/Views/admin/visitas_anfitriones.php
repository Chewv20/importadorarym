<?php
/** @var array $anfitriones */
?>
<div class="admin-head"><h1>Libreta de visitas</h1></div>

<div class="filters-row">
    <a class="filter-tab" href="<?= url('/admin/visitas') ?>">Registros</a>
    <a class="filter-tab" href="<?= url('/admin/visitas/dispositivos') ?>">Dispositivos</a>
    <a class="filter-tab is-active" href="<?= url('/admin/visitas/anfitriones') ?>">Anfitriones</a>
</div>

<div class="card-panel mb-8">
    <h2 class="card-panel__title">Agregar anfitrión</h2>
    <p class="text-muted fs-sm">Personas que pueden recibir visitas. El visitante las elige en el kiosco y reciben el aviso por correo.</p>
    <form class="filters-row" method="post" action="<?= url('/admin/visitas/anfitriones') ?>">
        <?= csrf_field() ?>
        <input type="text" name="nombre" placeholder="Nombre" maxlength="120" required>
        <input type="email" name="email" placeholder="Correo" maxlength="191" required>
        <input type="text" name="area" placeholder="Área (opcional)" maxlength="100">
        <label class="check-inline"><input type="checkbox" name="activo" value="1" checked> Activo</label>
        <button type="submit" class="btn btn--accent">Agregar</button>
    </form>
</div>

<?php if ($anfitriones): ?>
<?php /* Formularios (solo CSRF) fuera de la tabla; los campos se asocian con el atributo form=. */ ?>
<?php foreach ($anfitriones as $a): ?>
    <form id="anf-<?= (int) $a['id'] ?>" method="post" action="<?= url('/admin/visitas/anfitriones/' . (int) $a['id']) ?>"><?= csrf_field() ?></form>
    <form id="anfdel-<?= (int) $a['id'] ?>" method="post" action="<?= url('/admin/visitas/anfitriones/' . (int) $a['id'] . '/eliminar') ?>"
          data-confirm="¿Eliminar a este anfitrión?"><?= csrf_field() ?></form>
<?php endforeach; ?>

<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Nombre</th><th>Correo</th><th>Área</th><th>Activo</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($anfitriones as $a): $fid = 'anf-' . (int) $a['id']; ?>
            <tr>
                <td><input class="cell-input" form="<?= $fid ?>" type="text" name="nombre" value="<?= e($a['nombre']) ?>" required></td>
                <td><input class="cell-input" form="<?= $fid ?>" type="email" name="email" value="<?= e($a['email']) ?>" required></td>
                <td><input class="cell-input" form="<?= $fid ?>" type="text" name="area" value="<?= e($a['area'] ?? '') ?>"></td>
                <td><input form="<?= $fid ?>" type="checkbox" name="activo" value="1" <?= (int) $a['activo'] === 1 ? 'checked' : '' ?>></td>
                <td class="nowrap">
                    <button type="submit" form="<?= $fid ?>" class="btn btn--outline btn--sm">Guardar</button>
                    <button type="submit" form="anfdel-<?= (int) $a['id'] ?>" class="btn btn--outline btn--sm">Eliminar</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <p class="empty-state">Aún no hay anfitriones. Agrega al menos uno para que el kiosco funcione.</p>
<?php endif; ?>
