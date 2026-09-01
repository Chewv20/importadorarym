<?php
/** @var array $rol @var array $permisosGrupos @var array $asignados */
$esAdmin = ($rol['slug'] ?? '') === 'admin';
?>
<div class="admin-head"><h1>Permisos del rol: <?= e($rol['nombre']) ?></h1></div>

<?php if ($esAdmin): ?>
    <p class="alert alert--ok mb-6">El rol <strong>Administrador</strong> siempre tiene todos los permisos; no se puede limitar.</p>
<?php endif; ?>

<p class="perm-legend mb-6"><span>Activa los permisos que tendrá este rol.</span></p>

<form class="form form--admin form--admin-wide" method="post" action="<?= url('/admin/roles/' . (int) $rol['id']) ?>">
    <?= csrf_field() ?>

    <?php foreach ($permisosGrupos as $grupo => $permisos): ?>
        <div class="checks-group">
            <div class="checks-group__title"><?= e($grupo) ?></div>
            <div class="perm-list">
                <?php foreach ($permisos as $perm): ?>
                    <div class="perm-item">
                        <div class="perm-item__info">
                            <span class="perm-item__name"><?= e($perm['nombre']) ?></span>
                            <span class="perm-item__clave"><?= e($perm['clave']) ?></span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="permisos[]" value="<?= (int) $perm['id'] ?>"
                                <?= in_array((int) $perm['id'], $asignados, true) ? 'checked' : '' ?>
                                <?= $esAdmin ? 'disabled' : '' ?>>
                            <span class="switch__track"></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="form-actions">
        <?php if (!$esAdmin): ?><button type="submit" class="btn btn--accent">Guardar permisos</button><?php endif; ?>
        <a class="btn btn--outline" href="<?= url('/admin/roles') ?>">Volver</a>
    </div>
</form>
