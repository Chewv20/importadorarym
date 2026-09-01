<?php
/**
 * Banda de llamado a la acción reutilizable en páginas internas.
 * Variable opcional: $ctaTitle
 */
?>
<section class="section cta">
    <div class="container text-center">
        <h2 class="section__title"><?= e($ctaTitle ?? '¿Listo para cotizar?') ?></h2>
        <p class="cta__text mx-auto">Un asesor te atiende y arma una propuesta a la medida de tu negocio.</p>
        <div class="hero__actions justify-center mt-8">
            <a class="btn btn--accent btn--lg" href="<?= url('/contacto') ?>">Contáctanos</a>
            <a class="btn btn--ghost btn--lg" href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener">WhatsApp</a>
        </div>
    </div>
</section>
