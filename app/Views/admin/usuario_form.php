<?php
/** @var ?array $usuarioEdit @var array $roles @var array $oficinas @var array $permisosGrupos @var array $overrides
    @var array $rolPermisoIds @var array $permisosPropios @var bool $esSiMismo */
$u = $usuarioEdit;
$action = $u ? url('/admin/usuarios/' . (int) $u['id']) : url('/admin/usuarios');
$propio = $esSiMismo ?? false;
?>
<div class="admin-head"><h1><?= $u ? 'Editar' : 'Nuevo' ?> usuario interno</h1></div>

<?php if ($propio): ?>
    <p class="text-muted fs-sm mb-4">Estás editando tu propia cuenta: puedes cambiar tu nombre
    y tu contraseña, pero no tu rol ni tus permisos.</p>
<?php endif; ?>

<form class="form form--admin" method="post" action="<?= $action ?>" novalidate>
    <?= csrf_field() ?>

    <div class="field">
        <label for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" value="<?= e($u['nombre'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="email">Correo *</label>
        <input type="email" id="email" name="email" value="<?= e($u['email'] ?? '') ?>" <?= $u ? 'readonly' : 'required' ?>>
    </div>
    <div class="field">
        <label for="rol_id">Rol *</label>
        <select id="rol_id" name="rol_id" required <?= $propio ? 'disabled' : '' ?>>
            <option value="">— Selecciona —</option>
            <?php foreach ($roles as $r): ?>
                <option value="<?= (int) $r['id'] ?>" data-slug="<?= e($r['slug']) ?>" <?= (int) ($u['rol_id'] ?? 0) === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!$propio): ?>
            <p class="text-muted fs-sm">Solo aparecen los roles cuyos permisos ya tienes.</p>
        <?php endif; ?>
    </div>
    <div class="field">
        <label for="oficina_id">Oficina</label>
        <select id="oficina_id" name="oficina_id" <?= $propio ? 'disabled' : '' ?>>
            <option value="">— Sin oficina —</option>
            <?php foreach ($oficinas as $of): ?>
                <option value="<?= (int) $of['id'] ?>" <?= (int) ($u['oficina_id'] ?? 0) === (int) $of['id'] ? 'selected' : '' ?>>
                    <?= e($of['nombre']) ?><?= (int) $of['activa'] !== 1 ? ' (inactiva)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="text-muted fs-sm">Sin el permiso «Ver la bitácora de todas las oficinas», el usuario solo verá las visitas de esta oficina.</p>
    </div>
    <div class="field">
        <label for="password"><?= $u ? 'Nueva contraseña (dejar vacío para no cambiar)' : 'Contraseña *' ?></label>
        <input type="password" id="password" name="password" <?= $u ? '' : 'required minlength="8"' ?>>
        <p class="text-muted fs-sm">Mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo (ej. ! @ # $).</p>
    </div>

    <div id="vendedorFields">
        <div class="form__row">
            <div class="field">
                <label for="clave_vendedor">Clave de vendedor (SAE)</label>
                <input type="text" id="clave_vendedor" name="clave_vendedor" maxlength="20"
                       value="<?= e($u['clave_vendedor'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="comision">Comisión (%)</label>
                <input type="number" id="comision" name="comision" step="0.01" min="0" max="100"
                       value="<?= $u && $u['comision'] !== null ? e($u['comision']) : '' ?>">
            </div>
        </div>
        <p class="text-muted fs-sm">Disponible solo para el rol <strong>Ventas / Cotizaciones</strong>.</p>
    </div>
    <?php if ($u && !$propio): ?>
        <div class="checks">
            <label><input type="checkbox" name="activo" <?= (int) $u['activo'] === 1 ? 'checked' : '' ?>> Activo</label>
        </div>

        <h2 class="portal-subtitle mt-8">Permisos del usuario</h2>
        <div class="perm-legend">
            <span><b>Según rol</b>: usa lo que define su rol</span>
            <span><b class="txt-grant">Sí</b>: conceder aparte</span>
            <span><b class="txt-deny">No</b>: revocar aparte</span>
        </div>

        <?php foreach ($permisosGrupos as $grupo => $permisos): ?>
            <div class="checks-group">
                <div class="checks-group__title"><?= e($grupo) ?></div>
                <div class="perm-list">
                    <?php foreach ($permisos as $perm):
                        $pid = (int) $perm['id'];
                        $enRol = in_array($pid, $rolPermisoIds, true);
                        $ov = $overrides[$pid] ?? null;
                        $cur = $ov === 1 ? 'grant' : ($ov === 0 ? 'deny' : 'inherit');
                        // Nadie concede un permiso que no tiene (el back-end lo revalida).
                        $puedeConceder = in_array($perm['clave'], $permisosPropios ?? [], true);
                    ?>
                        <div class="perm-item">
                            <div class="perm-item__info">
                                <span class="perm-item__name"><?= e($perm['nombre']) ?></span>
                                <span class="perm-item__role perm-item__role--<?= $enRol ? 'yes' : 'no' ?>">rol: <?= $enRol ? 'sí' : 'no' ?></span>
                                <?php if (!$puedeConceder): ?>
                                    <span class="perm-item__role perm-item__role--no">no lo tienes</span>
                                <?php endif; ?>
                            </div>
                            <div class="seg" role="group" aria-label="<?= e($perm['nombre']) ?>">
                                <input class="seg-inherit" type="radio" id="p<?= $pid ?>-i" name="override[<?= $pid ?>]" value="inherit" <?= $cur === 'inherit' ? 'checked' : '' ?>>
                                <label for="p<?= $pid ?>-i">Según rol</label>
                                <input class="seg-grant" type="radio" id="p<?= $pid ?>-g" name="override[<?= $pid ?>]" value="grant" <?= $cur === 'grant' ? 'checked' : '' ?> <?= $puedeConceder ? '' : 'disabled' ?>>
                                <label for="p<?= $pid ?>-g">Sí</label>
                                <input class="seg-deny" type="radio" id="p<?= $pid ?>-d" name="override[<?= $pid ?>]" value="deny" <?= $cur === 'deny' ? 'checked' : '' ?>>
                                <label for="p<?= $pid ?>-d">No</label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="btn btn--accent">Guardar</button>
        <a class="btn btn--outline" href="<?= url('/admin/usuarios') ?>">Cancelar</a>
    </div>
</form>
