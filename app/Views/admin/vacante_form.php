<?php
/** @var ?array $vac @var array $tipos @var string $accion */
?>
<div class="admin-head"><h1><?= $vac ? 'Editar' : 'Nueva' ?> vacante</h1></div>

<form class="form form--admin" method="post" action="<?= $accion ?>" novalidate>
    <?= csrf_field() ?>

    <div class="field">
        <label for="titulo">Título del puesto *</label>
        <input type="text" id="titulo" name="titulo" value="<?= e($vac['titulo'] ?? '') ?>" maxlength="150" required>
    </div>

    <div class="form__row">
        <div class="field">
            <label for="area">Área / departamento</label>
            <input type="text" id="area" name="area" value="<?= e($vac['area'] ?? '') ?>" maxlength="100">
        </div>
        <div class="field">
            <label for="ubicacion">Ubicación</label>
            <input type="text" id="ubicacion" name="ubicacion" value="<?= e($vac['ubicacion'] ?? '') ?>" maxlength="150">
        </div>
    </div>

    <div class="form__row">
        <div class="field">
            <label for="tipo">Tipo de contratación</label>
            <select id="tipo" name="tipo">
                <?php foreach ($tipos as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= ($vac['tipo'] ?? '') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="abierta" <?= ($vac['estado'] ?? 'abierta') === 'abierta' ? 'selected' : '' ?>>Abierta (visible en el sitio)</option>
                <option value="cerrada" <?= ($vac['estado'] ?? '') === 'cerrada' ? 'selected' : '' ?>>Cerrada (oculta)</option>
            </select>
        </div>
    </div>

    <div class="field">
        <label for="descripcion">Descripción del puesto</label>
        <textarea id="descripcion" name="descripcion" rows="5"><?= e($vac['descripcion'] ?? '') ?></textarea>
    </div>
    <div class="field">
        <label for="requisitos">Requisitos</label>
        <textarea id="requisitos" name="requisitos" rows="5"><?= e($vac['requisitos'] ?? '') ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--accent">Guardar</button>
        <a class="btn btn--outline" href="<?= url('/admin/vacantes') ?>">Cancelar</a>
    </div>
</form>
