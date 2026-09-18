<?php
/** @var array $categorias */
/** @var array $productos */
/** @var array $imagenesPorProducto */
/** @var string|null $actualSlug */
/** @var string $q */
$categorias = $categorias ?? [];
$productos  = $productos ?? [];
$imagenesPorProducto = $imagenesPorProducto ?? [];
$actualSlug = $actualSlug ?? null;
$q = $q ?? '';
// URL de la categoría conservando la búsqueda actual.
$catUrl = static function (?string $slug) use ($q): string {
    $u = url('/productos' . ($slug ? '/' . $slug : ''));
    return $q !== '' ? $u . '?q=' . urlencode($q) : $u;
};
$accionActual = url('/productos' . ($actualSlug ? '/' . $actualSlug : ''));
require APP_PATH . '/Views/partials/page_hero.php';
?>

<section class="section">
    <div class="container">
        <form class="catalog-search" method="get" action="<?= e($accionActual) ?>" role="search">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar producto por nombre o SKU…" aria-label="Buscar producto">
            <button type="submit" class="btn btn--accent">Buscar</button>
            <?php if ($q !== ''): ?>
                <a class="btn btn--outline" href="<?= e($accionActual) ?>">Limpiar</a>
            <?php endif; ?>
        </form>

        <?php
        // La raíz "activa" es la que contiene al slug actual (ella misma o
        // alguna de sus subcategorías); determina qué fila de sub-tabs mostrar.
        $raizActiva = null;
        foreach ($categorias as $cat) {
            if ($actualSlug === $cat['slug'] || in_array($actualSlug, array_column($cat['hijos'], 'slug'), true)) {
                $raizActiva = $cat;
                break;
            }
        }
        ?>
        <?php if ($categorias): ?>
        <div class="catalog-filters">
            <span class="catalog-filters__label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
                Filtrar por categoría
            </span>
            <nav class="filter-tabs" aria-label="Categorías">
                <a class="filter-tab <?= $actualSlug === null ? 'is-active' : '' ?>" href="<?= e($catUrl(null)) ?>">Todos</a>
                <?php foreach ($categorias as $cat): ?>
                    <a class="filter-tab <?= ($raizActiva && $raizActiva['id'] === $cat['id']) ? 'is-active' : '' ?>"
                       href="<?= e($catUrl($cat['slug'])) ?>"><?= e($cat['nombre']) ?></a>
                <?php endforeach; ?>
            </nav>
            <?php if ($raizActiva && $raizActiva['hijos']): ?>
                <nav class="filter-tabs filter-tabs--sub" aria-label="Subcategorías de <?= e($raizActiva['nombre']) ?>">
                    <a class="filter-tab filter-tab--sub <?= $actualSlug === $raizActiva['slug'] ? 'is-active' : '' ?>"
                       href="<?= e($catUrl($raizActiva['slug'])) ?>">Todo en <?= e($raizActiva['nombre']) ?></a>
                    <?php foreach ($raizActiva['hijos'] as $sub): ?>
                        <a class="filter-tab filter-tab--sub <?= $actualSlug === $sub['slug'] ? 'is-active' : '' ?>"
                           href="<?= e($catUrl($sub['slug'])) ?>"><?= e($sub['nombre']) ?></a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($q !== ''): ?>
            <p class="catalog-search__info text-muted"><?= (int) $total ?> resultado(s) para «<?= e($q) ?>»<?= $actualSlug ? ' en esta categoría' : '' ?>.</p>
        <?php endif; ?>

        <?php if ($productos): ?>
        <div class="grid grid--4">
            <?php foreach ($productos as $p): ?>
                <?php
                $cotizarUrl = url('/contacto') . '?producto=' . urlencode($p['nombre']) . '#form';
                $imgs = $imagenesPorProducto[(int) $p['id']] ?? [];
                ?>
                <article class="prod-card reveal">
                    <?php if ($imgs): ?>
                    <div class="prod-card__media" <?= count($imgs) > 1 ? 'data-gallery' : '' ?>
                         data-lightbox tabindex="0" role="button" aria-label="Ver imágenes de <?= e($p['nombre']) ?> en grande">
                        <div class="prod-gallery">
                            <?php foreach ($imgs as $gi => $ruta): ?>
                                <img class="prod-gallery__img <?= $gi === 0 ? 'is-active' : '' ?>"
                                     src="<?= asset(e($ruta)) ?>" alt="<?= e($p['nombre']) ?>"
                                     loading="lazy" decoding="async">
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($imgs) > 1): ?>
                            <div class="prod-gallery__dots" aria-hidden="true">
                                <?php foreach ($imgs as $gi => $ruta): ?>
                                    <span class="prod-gallery__dot <?= $gi === 0 ? 'is-active' : '' ?>"></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="prod-card__media prod-card__media--empty">
                        <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2h12l-1 20H7L6 2zM6 7h12"/></svg>
                    </div>
                    <?php endif; ?>
                    <div class="prod-card__body">
                        <?php if (!empty($p['destacado'])): ?>
                            <span class="badge-destacado">Destacado</span>
                        <?php endif; ?>
                        <?php if (!empty($p['personalizable'])): ?>
                            <span class="badge-personalizable">Personalizable</span>
                        <?php endif; ?>
                        <?php if (($p['disponibilidad'] ?? null) === 'agotado'): ?>
                            <span class="badge-agotado">Agotado</span>
                        <?php elseif (($p['disponibilidad'] ?? null) === 'bajo_pedido'): ?>
                            <span class="badge-bajopedido">Bajo pedido</span>
                        <?php endif; ?>
                        <div class="prod-card__name"><?= e($p['nombre']) ?></div>
                        <?php if (!empty($p['unidad'])): ?>
                            <div class="prod-card__meta"><?= e($p['unidad']) ?></div>
                        <?php endif; ?>
                        <a class="btn btn--outline btn--block mt-4" href="<?= e($cotizarUrl) ?>">Cotizar</a>
                        <a class="btn btn--whatsapp btn--block mt-2" target="_blank" rel="noopener"
                           href="<?= e(whatsapp_url('Hola, quiero información sobre: ' . $p['nombre'])) ?>">Consultar por WhatsApp</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <?php if ($q !== ''): ?>
                <p class="empty-state">No encontramos productos para «<?= e($q) ?>». Prueba con otro término o <a href="<?= e($accionActual) ?>">ver todo</a>.</p>
            <?php else: ?>
                <p class="empty-state">Pronto agregaremos productos a esta categoría. Mientras tanto, <a href="<?= url('/contacto') ?>">contáctanos</a> y con gusto te cotizamos.</p>
            <?php endif; ?>
        <?php endif; ?>

        <?php
        $baseUrl = $accionActual . ($q !== '' ? '?q=' . urlencode($q) : '');
        require APP_PATH . '/Views/partials/pagination.php';
        ?>
    </div>
</section>

<?php require APP_PATH . '/Views/partials/cta_band.php'; ?>
