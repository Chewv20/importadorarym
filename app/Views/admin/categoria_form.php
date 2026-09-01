<?php
/** @var ?array $categoria @var array $raices @var bool $yaEsPadre */
$c = $categoria;
$raices = $raices ?? [];
$action = $c ? url('/admin/categorias/' . (int) $c['id']) : url('/admin/categorias');
?>
<div class="admin-head"><h1><?= $c ? 'Editar' : 'Nueva' ?> categoría</h1></div>

<form class="form form--admin" method="post" action="<?= $action ?>" novalidate>
    <?= csrf_field() ?>

    <div class="field">
        <label for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" value="<?= e($c['nombre'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="slug">Slug (opcional)</label>
        <input type="text" id="slug" name="slug" value="<?= e($c['slug'] ?? '') ?>" placeholder="Se genera automáticamente del nombre">
    </div>
    <div class="field">
        <label for="categoria_padre_id">Categoría padre (opcional)</label>
        <?php if (!empty($yaEsPadre)): ?>
            <select id="categoria_padre_id" name="categoria_padre_id" disabled>
                <option>— Ninguna (ya tiene subcategorías) —</option>
            </select>
            <p class="text-muted fs-sm">Esta categoría ya tiene subcategorías, así que no puede convertirse en subcategoría de otra (solo se admiten 2 niveles).</p>
        <?php else: ?>
            <select id="categoria_padre_id" name="categoria_padre_id">
                <option value="">— Ninguna (categoría principal) —</option>
                <?php foreach ($raices as $r): ?>
                    <option value="<?= (int) $r['id'] ?>" <?= (int) ($c['categoria_padre_id'] ?? 0) === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="text-muted fs-sm">Si eliges una, esta categoría aparecerá como subcategoría de ella (ej. Bolsas dentro de Biodegradables).</p>
        <?php endif; ?>
    </div>
    <div class="field">
        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion"><?= e($c['descripcion'] ?? '') ?></textarea>
    </div>
    <div class="field">
        <label for="orden">Orden</label>
        <input type="number" id="orden" name="orden" value="<?= (int) ($c['orden'] ?? 0) ?>">
    </div>
    <div class="checks">
        <label><input type="checkbox" name="activo" <?= ($c === null || !empty($c['activo'])) ? 'checked' : '' ?>> Activa</label>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--accent">Guardar</button>
        <a class="btn btn--outline" href="<?= url('/admin/categorias') ?>">Cancelar</a>
    </div>
</form>
