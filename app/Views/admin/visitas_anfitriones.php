<?php
/** @var array $anfitriones @var array $oficinas */
?>
<div class="admin-head"><h1>Libreta de visitas</h1></div>

<div class="filters-row">
    <a class="filter-tab" href="<?= url('/admin/visitas') ?>">Registros</a>
    <a class="filter-tab" href="<?= url('/admin/visitas/oficinas') ?>">Oficinas</a>
    <a class="filter-tab" href="<?= url('/admin/visitas/dispositivos') ?>">Dispositivos</a>
    <a class="filter-tab is-active" href="<?= url('/admin/visitas/anfitriones') ?>">Anfitriones</a>
</div>

<div class="card-panel mb-8">
    <h2 class="card-panel__title">Agregar anfitrión</h2>
    <p class="text-muted fs-sm">El visitante elige el <strong>área</strong> en el kiosco de la oficina del anfitrión; el aviso por correo llega al anfitrión con su nombre y a su correo.</p>
    <?php if (!$oficinas): ?>
        <p class="text-muted fs-sm">Primero crea al menos una oficina en la pestaña <a href="<?= url('/admin/visitas/oficinas') ?>">Oficinas</a>.</p>
    <?php else: ?>
    <form class="filters-row" method="post" action="<?= url('/admin/visitas/anfitriones') ?>">
        <?= csrf_field() ?>
        <input type="text" name="nombre" placeholder="Nombre" maxlength="120" required>
        <input type="email" name="email" placeholder="Correo" maxlength="191" required>
        <input type="text" name="area" placeholder="Área (visible en el kiosco)" maxlength="100" required>
        <select name="oficina_id" aria-label="Oficina" required>
            <option value="">— Oficina —</option>
            <?php foreach ($oficinas as $o): ?>
                <option value="<?= (int) $o['id'] ?>"><?= e($o['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <label class="check-inline"><input type="checkbox" name="activo" value="1" checked> Activo</label>
        <button type="submit" class="btn btn--accent">Agregar</button>
    </form>
    <?php endif; ?>
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
        <thead><tr><th>Nombre</th><th>Correo</th><th>Área</th><th>Oficina</th><th>Activo</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($anfitriones as $a): $fid = 'anf-' . (int) $a['id']; ?>
            <tr>
                <td><input class="cell-input" form="<?= $fid ?>" type="text" name="nombre" value="<?= e($a['nombre']) ?>" required></td>
                <td><input class="cell-input" form="<?= $fid ?>" type="email" name="email" value="<?= e($a['email']) ?>" required></td>
                <td><input class="cell-input" form="<?= $fid ?>" type="text" name="area" value="<?= e($a['area'] ?? '') ?>" required></td>
                <td>
                    <select class="cell-input" form="<?= $fid ?>" name="oficina_id" required>
                        <option value="">— Oficina —</option>
                        <?php foreach ($oficinas as $o): ?>
                            <option value="<?= (int) $o['id'] ?>" <?= (int) ($a['oficina_id'] ?? 0) === (int) $o['id'] ? 'selected' : '' ?>><?= e($o['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
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
