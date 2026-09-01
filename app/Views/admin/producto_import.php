<?php
/** @var array|null $resultado */
?>
<div class="admin-head">
    <h1>Importar catálogo</h1>
    <a class="btn btn--outline btn--sm" href="<?= url('/admin/productos') ?>">← Volver a productos</a>
</div>

<?php if ($resultado): ?>
    <div class="import-result">
        <h2 class="import-result__title">
            <?= !empty($resultado['dry']) ? 'Resultado de la simulación' : 'Resultado de la importación' ?>
        </h2>
        <div class="import-stats">
            <span class="import-stat"><strong><?= (int) $resultado['creados'] ?></strong> creados</span>
            <span class="import-stat"><strong><?= (int) $resultado['actualizados'] ?></strong> actualizados</span>
            <span class="import-stat"><strong><?= (int) $resultado['categorias'] ?></strong> categorías nuevas</span>
            <span class="import-stat"><strong><?= (int) $resultado['omitidos'] ?></strong> omitidos</span>
        </div>
        <?php if (!empty($resultado['errores'])): ?>
            <ul class="import-avisos">
                <?php foreach ($resultado['errores'] as $av): ?>
                    <li><?= e($av) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="import-grid">
    <section class="card-panel">
        <h2 class="card-panel__title">1. Descarga la plantilla</h2>
        <p class="text-muted fs-sm">
            Un archivo CSV de ejemplo con las columnas correctas. Ábrelo en Excel o Google Sheets,
            reemplaza las filas con tus productos y guárdalo como CSV.
        </p>
        <a class="btn btn--outline" href="<?= url('/admin/productos/importar/plantilla') ?>">Descargar plantilla CSV</a>
    </section>

    <section class="card-panel">
        <h2 class="card-panel__title">2. Sube tu archivo</h2>
        <form class="form form--admin" method="post" action="<?= url('/admin/productos/importar') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="field">
                <label for="csv">Archivo CSV (máx. 2 MB)</label>
                <input type="file" id="csv" name="csv" accept=".csv,text/csv" required>
            </div>
            <label class="check-inline">
                <input type="checkbox" name="simular" value="1"> Solo simular (no guarda; muestra qué pasaría)
            </label>
            <div class="form-actions">
                <button type="submit" class="btn btn--accent">Importar</button>
            </div>
        </form>
    </section>
</div>

<section class="card-panel mt-6">
    <h2 class="card-panel__title">Columnas</h2>
    <p class="text-muted fs-sm">Solo <strong>nombre</strong> es obligatorio. El orden de las columnas no importa.</p>
    <ul class="import-cols">
        <li><code>nombre</code> — nombre del producto (obligatorio)</li>
        <li><code>categoria</code> — se crea sola si no existe</li>
        <li><code>subcategoria</code> — opcional; requiere <code>categoria</code> en la misma fila (se crea sola bajo
            esa categoría si no existe). Si la omites, el producto queda en la categoría general, no en una
            subcategoría. Dos subcategorías pueden llamarse igual si están bajo categorías distintas
            (ej. «Cubiertos» en «Biodegradables» y en «Bambú» son categorías diferentes).</li>
        <li><code>sku</code> — código interno; se usa para no duplicar (upsert)</li>
        <li><code>clave_sae</code> — clave del artículo en Aspel SAE (también sirve para el upsert)</li>
        <li><code>esquema_impuestos</code> — clave numérica del esquema de impuestos en SAE.
            Necesaria para exportar pedidos. Si <strong>omites la columna</strong>, los productos
            que ya la tengan capturada la conservan.</li>
        <li><code>unidad</code> — presentación (ej. «Paquete 50 pzas»)</li>
        <li><code>descripcion</code>, <code>precio</code>, <code>orden</code></li>
        <li><code>destacado</code>, <code>personalizable</code>, <code>activo</code> — 1/0 (o sí/no).
            Si se omiten, destacado y personalizable quedan en no, y activo en sí.</li>
    </ul>
    <p class="text-muted fs-sm">Las <strong>imágenes</strong> se suben después, desde la ficha de cada producto.</p>
</section>
