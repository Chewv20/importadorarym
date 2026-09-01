<?php
/** @var ?array $zona */
$z = $zona;
$action = $z ? url('/admin/zonas/' . (int) $z['id']) : url('/admin/zonas');
?>
<div class="admin-head"><h1><?= $z ? 'Editar' : 'Nueva' ?> zona</h1></div>

<form class="form form--admin" method="post" action="<?= $action ?>" novalidate>
    <?= csrf_field() ?>
    <div class="field">
        <label for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" value="<?= e($z['nombre'] ?? '') ?>" required>
    </div>
    <div class="checks">
        <label><input type="checkbox" name="activa" <?= ($z === null || !empty($z['activa'])) ? 'checked' : '' ?>> Activa</label>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--accent">Guardar</button>
        <a class="btn btn--outline" href="<?= url('/admin/zonas') ?>">Cancelar</a>
    </div>
</form>
