<?php
/**
 * Encabezado reutilizable de páginas internas.
 * Variables esperadas (desde el controlador / vista):
 * @var string      $pageTitle
 * @var string|null $pageSubtitle
 * @var array       $breadcrumb  Lista de ['label' => string, 'url' => ?string]
 */
$breadcrumb = $breadcrumb ?? [];
?>
<section class="page-hero">
    <div class="container page-hero__inner">
        <?php if ($breadcrumb): ?>
        <nav class="breadcrumb" aria-label="Ruta de navegación">
            <?php $last = count($breadcrumb) - 1; foreach ($breadcrumb as $i => $item): ?>
                <?php if (!empty($item['url'])): ?>
                    <a href="<?= e($item['url']) ?>"><?= e($item['label']) ?></a>
                <?php else: ?>
                    <span aria-current="page"><?= e($item['label']) ?></span>
                <?php endif; ?>
                <?php if ($i < $last): ?><span class="breadcrumb__sep">/</span><?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <h1 class="page-hero__title"><?= e($pageTitle ?? '') ?></h1>
        <?php if (!empty($pageSubtitle)): ?>
            <p class="page-hero__subtitle"><?= e($pageSubtitle) ?></p>
        <?php endif; ?>
    </div>
</section>
