<?php
/** @var ?array $logo */
$l = $logo;
$action = $l ? url('/admin/logos-clientes/' . (int) $l['id']) : url('/admin/logos-clientes');
?>
<div class="admin-head"><h1><?= $l ? 'Editar' : 'Nuevo' ?> logo de cliente</h1></div>

<form class="form form--admin" method="post" action="<?= $action ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <div class="field">
        <label for="nombre">Nombre del cliente *</label>
        <input type="text" id="nombre" name="nombre" value="<?= e($l['nombre'] ?? '') ?>" required>
        <p class="text-muted fs-sm">Se usa como texto alternativo de la imagen.</p>
    </div>

    <div class="field">
        <label for="imagen">Logo <?= $l ? '(deja vacío para conservar el actual)' : '*' ?></label>
        <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp" <?= $l ? '' : 'required' ?>>
        <p class="text-muted fs-sm">JPG, PNG o WebP · máx. 3 MB. Se recorta el fondo y se optimiza solo.</p>
        <?php if ($l && !empty($l['imagen'])): ?>
            <span class="logo-cell logo-cell--lg preview-logo"><img src="<?= asset(e($l['imagen'])) ?>" alt="Logo actual" loading="lazy" decoding="async"></span>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="orden">Orden</label>
        <input type="number" id="orden" name="orden" value="<?= (int) ($l['orden'] ?? 0) ?>">
    </div>

    <div class="checks">
        <label><input type="checkbox" name="activo" <?= ($l === null || !empty($l['activo'])) ? 'checked' : '' ?>> Activo (se muestra en el sitio)</label>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--accent">Guardar</button>
        <a class="btn btn--outline" href="<?= url('/admin/logos-clientes') ?>">Cancelar</a>
    </div>
</form>
