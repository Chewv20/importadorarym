<?php
/** @var array $vacantes @var array $tipos */
$breadcrumb = [['label' => 'Inicio', 'url' => url('/')], ['label' => 'Bolsa de trabajo']];
require APP_PATH . '/Views/partials/page_hero.php';
?>

<section class="section">
    <div class="container">
        <?php if ($vacantes): ?>
            <div class="vacancy-list">
                <?php foreach ($vacantes as $v): ?>
                    <a class="vacancy-card" href="<?= url('/bolsa-de-trabajo/' . e($v['slug'])) ?>">
                        <h2 class="vacancy-card__title"><?= e($v['titulo']) ?></h2>
                        <div class="vacancy-meta">
                            <span class="vacancy-tag"><?= e($tipos[$v['tipo']] ?? $v['tipo']) ?></span>
                            <?php if (!empty($v['area'])): ?><span class="vacancy-tag vacancy-tag--soft"><?= e($v['area']) ?></span><?php endif; ?>
                            <?php if (!empty($v['ubicacion'])): ?><span class="vacancy-loc">📍 <?= e($v['ubicacion']) ?></span><?php endif; ?>
                        </div>
                        <?php if (!empty($v['descripcion'])): ?>
                            <p class="vacancy-card__desc"><?= e(mb_strimwidth(trim($v['descripcion']), 0, 160, '…')) ?></p>
                        <?php endif; ?>
                        <span class="vacancy-card__cta">Ver y postular →</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty-state">Por ahora no tenemos vacantes abiertas.</p>
        <?php endif; ?>

        <div class="vacancy-cta">
            <h2 class="vacancy-cta__title">¿No encuentras una vacante para ti?</h2>
            <p class="vacancy-cta__text">Envíanos tu CV de todas formas. Lo revisamos y te contactamos si surge algo que coincida con tu perfil.</p>
            <a class="btn btn--outline" href="<?= url('/bolsa-de-trabajo/solicitud') ?>">Enviar solicitud general</a>
        </div>
    </div>
</section>
