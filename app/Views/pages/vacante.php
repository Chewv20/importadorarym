<?php
/** @var array $vacante @var array $tipos @var string|null $error @var string|null $exito @var array $old */
$pageTitle = $vacante['titulo'];
$pageSubtitle = $tipos[$vacante['tipo']] ?? $vacante['tipo'];
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('/')],
    ['label' => 'Bolsa de trabajo', 'url' => url('/bolsa-de-trabajo')],
    ['label' => $vacante['titulo']],
];
require APP_PATH . '/Views/partials/page_hero.php';
?>

<section class="section">
    <div class="container vacancy-detail-grid">

        <article class="vacancy-detail">
            <div class="vacancy-meta">
                <span class="vacancy-tag"><?= e($tipos[$vacante['tipo']] ?? $vacante['tipo']) ?></span>
                <?php if (!empty($vacante['area'])): ?><span class="vacancy-tag vacancy-tag--soft"><?= e($vacante['area']) ?></span><?php endif; ?>
                <?php if (!empty($vacante['ubicacion'])): ?><span class="vacancy-loc">📍 <?= e($vacante['ubicacion']) ?></span><?php endif; ?>
            </div>

            <?php if (!empty($vacante['descripcion'])): ?>
                <h2 class="vacancy-detail__h">Descripción</h2>
                <div class="prose"><?= nl2br(e($vacante['descripcion'])) ?></div>
            <?php endif; ?>

            <?php if (!empty($vacante['requisitos'])): ?>
                <h2 class="vacancy-detail__h">Requisitos</h2>
                <div class="prose"><?= nl2br(e($vacante['requisitos'])) ?></div>
            <?php endif; ?>
        </article>

        <form class="form vacancy-form" method="post" action="<?= url('/bolsa-de-trabajo/' . e($vacante['slug'])) ?>" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <?= honeypot_field() ?>
            <h2 class="section__title text-left">Postúlate</h2>

            <?php if ($exito): ?>
                <div class="alert alert--ok"><?= e($exito) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert--error"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="field">
                <label for="nombre">Nombre completo *</label>
                <input type="text" id="nombre" name="nombre" value="<?= e($old['nombre'] ?? '') ?>" required>
            </div>
            <div class="form__row">
                <div class="field">
                    <label for="email">Correo *</label>
                    <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" required>
                </div>
                <div class="field">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" value="<?= e($old['telefono'] ?? '') ?>">
                </div>
            </div>
            <div class="field">
                <label for="mensaje">¿Por qué te interesa este puesto?</label>
                <textarea id="mensaje" name="mensaje" rows="4"><?= e($old['mensaje'] ?? '') ?></textarea>
            </div>
            <div class="field">
                <label for="cv">Tu CV (PDF, máx. 5 MB) *</label>
                <input type="file" id="cv" name="cv" accept="application/pdf" required>
            </div>

            <?= captcha_field() ?>

            <button type="submit" class="btn btn--accent btn--block">Enviar postulación</button>
        </form>

    </div>
</section>
