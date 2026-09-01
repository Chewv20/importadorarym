<?php
/** @var ?array $modal */
$m = $modal;
$action = $m ? url('/admin/modales/' . (int) $m['id']) : url('/admin/modales');
?>
<div class="admin-head"><h1><?= $m ? 'Editar' : 'Nuevo' ?> modal</h1></div>

<form class="form form--admin" method="post" action="<?= $action ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <div class="field">
        <label for="titulo">Título *</label>
        <input type="text" id="titulo" name="titulo" value="<?= e($m['titulo'] ?? '') ?>" required>
        <p class="text-muted fs-sm">Uso interno / texto alternativo de la imagen.</p>
    </div>

    <div class="field">
        <label for="imagen">Imagen <?= $m ? '(deja vacío para conservar la actual)' : '*' ?></label>
        <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp" <?= $m ? '' : 'required' ?>>
        <p class="text-muted fs-sm">JPG, PNG o WebP · máx. 3 MB. Se optimiza automáticamente.</p>
        <?php if ($m && !empty($m['imagen'])): ?>
            <img class="preview-img" src="<?= asset(e($m['imagen'])) ?>" alt="Imagen actual" loading="lazy" decoding="async">
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="enlace">Enlace al hacer clic (opcional)</label>
        <input type="url" id="enlace" name="enlace" value="<?= e($m['enlace'] ?? '') ?>" placeholder="https://...">
    </div>

    <div class="field">
        <label for="orden">Orden</label>
        <input type="number" id="orden" name="orden" value="<?= (int) ($m['orden'] ?? 0) ?>">
    </div>

    <div class="checks">
        <label><input type="checkbox" name="activo" <?= ($m === null || !empty($m['activo'])) ? 'checked' : '' ?>> Activo (se muestra en el sitio)</label>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--accent">Guardar</button>
        <a class="btn btn--outline" href="<?= url('/admin/modales') ?>">Cancelar</a>
    </div>
</form>
