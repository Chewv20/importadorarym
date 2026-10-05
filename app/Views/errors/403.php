<?php /** @var string $volverUrl @var string $volverTexto */ ?>
<section class="hero hero--center">
    <div class="container hero__stage">
        <span class="hero__eyebrow">Error 403</span>
        <h1 class="hero__title">No tienes acceso a esta sección</h1>
        <p class="hero__text">Tu cuenta no tiene permiso para esta acción. Si crees que deberías tenerlo, pide a un administrador que lo revise.</p>
        <div class="hero__actions">
            <a class="btn btn--accent btn--lg" href="<?= e($volverUrl) ?>"><?= e($volverTexto) ?></a>
        </div>
    </div>
</section>
