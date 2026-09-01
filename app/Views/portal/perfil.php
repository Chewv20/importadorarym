<?php /** @var array $usuario */ ?>
<h1 class="portal-title">Mi perfil</h1>

<div class="measure">
    <form class="form" method="post" action="<?= url('/portal/perfil') ?>" novalidate data-cp-form>
        <?= csrf_field() ?>
        <div class="field">
            <label for="nombre">Nombre completo *</label>
            <input type="text" id="nombre" name="nombre" value="<?= e($usuario['nombre']) ?>" required>
        </div>
        <div class="field">
            <label for="email">Correo</label>
            <input type="email" id="email" value="<?= e($usuario['email']) ?>" readonly>
        </div>
        <div class="field">
            <label for="empresa">Empresa</label>
            <input type="text" id="empresa" name="empresa" value="<?= e($usuario['empresa'] ?? '') ?>">
        </div>
        <div class="form__row">
            <div class="field">
                <label for="telefono">Teléfono</label>
                <input type="tel" id="telefono" name="telefono" value="<?= e($usuario['telefono'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="rfc">RFC</label>
                <input type="text" id="rfc" name="rfc" value="<?= e($usuario['rfc'] ?? '') ?>">
            </div>
        </div>

        <p class="fw-semibold mt-6 mb-2">Dirección de entrega</p>
        <div class="form__row">
            <div class="field">
                <label for="calle">Calle *</label>
                <input type="text" id="calle" name="calle" value="<?= e($usuario['calle'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label for="numero_ext">Número *</label>
                <input type="text" id="numero_ext" name="numero_ext" value="<?= e($usuario['numero_ext'] ?? '') ?>" required>
            </div>
        </div>
        <div class="form__row">
            <div class="field">
                <label for="numero_int">Número interior</label>
                <input type="text" id="numero_int" name="numero_int" value="<?= e($usuario['numero_int'] ?? '') ?>">
            </div>
            <div class="field colonia-picker" data-colonia-picker>
                <label for="colonia">Colonia *</label>
                <input type="text" id="colonia" name="colonia" value="<?= e($usuario['colonia'] ?? '') ?>" autocomplete="off" required>
                <div class="colonia-picker__resultados" data-colonia-resultados hidden></div>
            </div>
        </div>
        <div class="form__row">
            <div class="field">
                <label for="codigo_postal">Código postal *</label>
                <input type="text" id="codigo_postal" name="codigo_postal" value="<?= e($usuario['codigo_postal'] ?? '') ?>" inputmode="numeric" pattern="\d{5}" maxlength="5" required>
                <p class="text-muted fs-sm">Al capturarlo, sugerimos la colonia y llenamos delegación/municipio y estado.</p>
            </div>
            <div class="field">
                <label for="delegacion_municipio">Delegación o Municipio *</label>
                <input type="text" id="delegacion_municipio" name="delegacion_municipio" value="<?= e($usuario['delegacion_municipio'] ?? '') ?>" required>
                <p class="text-muted fs-sm">Se completa con tu código postal; para corregirlo, cambia el código postal.</p>
            </div>
        </div>
        <div class="field">
            <label for="estado_direccion">Estado *</label>
            <input type="text" id="estado_direccion" name="estado_direccion" value="<?= e($usuario['estado_direccion'] ?? '') ?>" required>
        </div>
        <div class="field">
            <label for="referencias">Referencias (opcional)</label>
            <input type="text" id="referencias" name="referencias" value="<?= e($usuario['referencias'] ?? '') ?>" placeholder="Entre calles, color de fachada, etc.">
        </div>

        <div class="mb-6" data-mapa-preview>
            <button type="button" class="btn btn--outline btn--sm" data-mapa-btn>Mostrar mapa para verificar</button>
            <p class="alert alert--error mt-2" data-mapa-error hidden></p>
            <div class="mt-4" data-mapa-contenedor hidden></div>
        </div>

        <button type="submit" class="btn btn--accent btn--lg">Guardar cambios</button>
    </form>

    <?php $mapaUrl = direccion_maps_url($usuario); ?>
    <?php if ($mapaUrl): ?>
        <p class="fw-semibold mt-8 mb-2">Así ubicamos tu dirección</p>
        <div class="map-embed">
            <iframe src="<?= e($mapaUrl) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Mapa de tu dirección"></iframe>
        </div>
    <?php endif; ?>
</div>
