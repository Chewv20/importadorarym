<?php
/** @var ?array $producto @var array $categorias @var array $imagenes @var int $maxImagenes @var ?int $esquemaGeneral */
$p = $producto;
$imagenes = $imagenes ?? [];
$maxImagenes = $maxImagenes ?? 5;
$esquemaGeneral = $esquemaGeneral ?? null;
$restantes = max(0, $maxImagenes - count($imagenes));
$action = $p ? url('/admin/productos/' . (int) $p['id']) : url('/admin/productos');
?>
<div class="admin-head"><h1><?= $p ? 'Editar' : 'Nuevo' ?> producto</h1></div>

<div class="producto-layout <?= ($p && $imagenes) ? '' : 'producto-layout--solo' ?>">
<form class="form form--admin" method="post" action="<?= $action ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <div class="form-section">
        <h2 class="form-section__title">Identificación</h2>
        <div class="field">
            <label for="nombre">Nombre *</label>
            <input type="text" id="nombre" name="nombre" value="<?= e($p['nombre'] ?? '') ?>" required>
        </div>
        <div class="field">
            <label for="slug">Slug (opcional)</label>
            <input type="text" id="slug" name="slug" value="<?= e($p['slug'] ?? '') ?>" placeholder="Se genera automáticamente del nombre">
        </div>
        <div class="field">
            <label for="categoria_id">Categoría</label>
            <select id="categoria_id" name="categoria_id">
                <option value="">— Sin categoría —</option>
                <?php foreach ($categorias as $c): ?>
                    <?php if ($c['hijos']): ?>
                        <optgroup label="<?= e($c['nombre']) ?>">
                            <option value="<?= (int) $c['id'] ?>" <?= (int) ($p['categoria_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?> (general)</option>
                            <?php foreach ($c['hijos'] as $h): ?>
                                <option value="<?= (int) $h['id'] ?>" <?= (int) ($p['categoria_id'] ?? 0) === (int) $h['id'] ? 'selected' : '' ?>>— <?= e($h['nombre']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php else: ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) ($p['categoria_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-section">
        <h2 class="form-section__title">Presentación y SAE</h2>
        <div class="form__row">
            <div class="field">
                <label for="sku">SKU</label>
                <input type="text" id="sku" name="sku" value="<?= e($p['sku'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="clave_sae">Clave SAE</label>
                <input type="text" id="clave_sae" name="clave_sae" value="<?= e($p['clave_sae'] ?? '') ?>" placeholder="Clave del artículo en Aspel SAE">
            </div>
            <div class="field">
                <label for="esquema_impuestos">Esquema de impuestos (SAE)</label>
                <input type="number" id="esquema_impuestos" name="esquema_impuestos" min="1" step="1"
                       value="<?= e($p['esquema_impuestos'] ?? '') ?>" placeholder="Ej. 1">
                <p class="text-muted fs-sm">
                    Clave numérica del esquema en SAE. Sin ella el pedido no se puede exportar
                    <?php if ($esquemaGeneral !== null): ?>
                        (si se deja vacío se usa el general: <strong><?= (int) $esquemaGeneral ?></strong>).
                    <?php else: ?>
                        y no hay un esquema general configurado.
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="form__row">
            <div class="field">
                <label for="unidad">Presentación</label>
                <input type="text" id="unidad" name="unidad" value="<?= e($p['unidad'] ?? '') ?>" placeholder="Millar">
            </div>
            <div class="field">
                <label for="piezas_por_presentacion">Piezas por presentación</label>
                <input type="number" id="piezas_por_presentacion" name="piezas_por_presentacion" min="1" step="1"
                       value="<?= e($p['piezas_por_presentacion'] ?? '') ?>" placeholder="Ej. 50">
                <p class="text-muted fs-sm">
                    Piezas que trae cada paquete/lote (ej. 50). El sistema redondeará hacia arriba
                    cualquier cantidad pedida al múltiplo más cercano. Vacío = sin restricción.
                </p>
            </div>
        </div>
        <div class="field">
            <label for="piezas_minimas">Mínimo de piezas por pedido</label>
            <input type="number" id="piezas_minimas" name="piezas_minimas" min="1" step="1"
                   value="<?= e($p['piezas_minimas'] ?? '') ?>" placeholder="Ej. 100">
            <p class="text-muted fs-sm">
                Independiente de la presentación: si el paquete es de 50 pero el pedido mínimo son
                2 paquetes, pon 100 aquí. Se redondea al múltiplo de la presentación si no coincide.
                Vacío = sin mínimo adicional (solo aplica la presentación).
            </p>
        </div>
    </div>

    <div class="form-section">
        <h2 class="form-section__title">Contenido</h2>
        <div class="field">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion"><?= e($p['descripcion'] ?? '') ?></textarea>
        </div>
        <div class="field">
            <label for="orden">Orden</label>
            <input type="number" id="orden" name="orden" value="<?= (int) ($p['orden'] ?? 0) ?>">
        </div>
        <div class="checks">
            <label><input type="checkbox" name="destacado" <?= !empty($p['destacado']) ? 'checked' : '' ?>> Destacado</label>
            <label><input type="checkbox" name="personalizable" <?= !empty($p['personalizable']) ? 'checked' : '' ?>> Personalizable (admite impresión con logo del cliente)</label>
            <label><input type="checkbox" name="activo" <?= ($p === null || !empty($p['activo'])) ? 'checked' : '' ?>> Activo</label>
        </div>
    </div>

    <div class="form-section">
        <h2 class="form-section__title">Imágenes</h2>
        <div class="field">
            <label for="imagenes">Imágenes (hasta <?= (int) $maxImagenes ?>)</label>
            <input type="file" id="imagenes" name="imagenes[]" accept="image/jpeg,image/png,image/webp" multiple <?= $restantes === 0 ? 'disabled' : '' ?>>
            <p class="text-muted fs-sm">
                JPG, PNG o WebP · máx. 3 MB c/u.
                <?php if ($p): ?>
                    Puedes agregar <?= (int) $restantes ?> más.
                <?php else: ?>
                    Se guardan al crear el producto.
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--accent">Guardar</button>
        <a class="btn btn--outline" href="<?= url('/admin/productos') ?>">Cancelar</a>
    </div>
</form>

<?php if ($p && $imagenes): ?>
    <div class="admin-images">
        <h2 class="admin-images__title">Imágenes actuales</h2>
        <p class="text-muted fs-sm">La primera es la principal (miniatura del catálogo).</p>
        <div class="admin-images__grid">
            <?php foreach ($imagenes as $i => $img): ?>
                <figure class="admin-image">
                    <img src="<?= asset(e($img['ruta'])) ?>" alt="Imagen <?= $i + 1 ?> de <?= e($p['nombre']) ?>" loading="lazy">
                    <?php if ($i === 0): ?><figcaption class="admin-image__tag">Principal</figcaption><?php endif; ?>
                    <form method="post" action="<?= url('/admin/productos/' . (int) $p['id'] . '/imagenes/' . (int) $img['id'] . '/eliminar') ?>"
                          data-confirm="¿Eliminar esta imagen?">
                        <?= csrf_field() ?>
                        <button type="submit" class="admin-image__del" aria-label="Eliminar imagen">&times;</button>
                    </form>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
</div>
