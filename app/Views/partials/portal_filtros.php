<?php
/**
 * Barra de búsqueda + filtro por categoría para el portal.
 * @var array $categorias Árbol: raíces con clave 'hijos' (Categoria::activasArbol()).
 * @var string $q @var string $catActual @var string $accion
 */
$categorias = $categorias ?? [];
$q = $q ?? '';
$catActual = $catActual ?? '';
?>
<form class="portal-filters" method="get" action="<?= url($accion) ?>">
    <input class="portal-filters__search" type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar producto por nombre o SKU…" aria-label="Buscar producto">
    <?php if ($categorias): ?>
        <select class="portal-filters__cat" name="categoria" aria-label="Filtrar por categoría">
            <option value="">Todas las categorías</option>
            <?php foreach ($categorias as $c): ?>
                <?php if ($c['hijos']): ?>
                    <optgroup label="<?= e($c['nombre']) ?>">
                        <option value="<?= e($c['slug']) ?>" <?= $catActual === $c['slug'] ? 'selected' : '' ?>>Todo en <?= e($c['nombre']) ?></option>
                        <?php foreach ($c['hijos'] as $h): ?>
                            <option value="<?= e($h['slug']) ?>" <?= $catActual === $h['slug'] ? 'selected' : '' ?>>— <?= e($h['nombre']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php else: ?>
                    <option value="<?= e($c['slug']) ?>" <?= $catActual === $c['slug'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <button type="submit" class="btn btn--primary btn--sm">Buscar</button>
    <?php if ($q !== '' || $catActual !== ''): ?>
        <a class="btn btn--outline btn--sm" href="<?= url($accion) ?>">Limpiar</a>
    <?php endif; ?>
</form>
