<?php
/** Modal promocional del sitio público. Se muestra el modal activo de menor orden. */
$modalPromo = (new \App\Models\Modal())->activoParaMostrar();
if ($modalPromo):
    $ts = strtotime($modalPromo['updated_at'] ?? 'now');
?>
<div class="promo-modal" data-promo data-promo-id="<?= (int) $modalPromo['id'] ?>" data-promo-v="<?= $ts ?>" hidden>
    <div class="promo-modal__backdrop" data-promo-close></div>
    <div class="promo-modal__box" role="dialog" aria-modal="true" aria-label="<?= e($modalPromo['titulo']) ?>">
        <button type="button" class="promo-modal__close" data-promo-close aria-label="Cerrar">&times;</button>
        <?php if (!empty($modalPromo['enlace'])): ?>
            <a href="<?= e($modalPromo['enlace']) ?>" target="_blank" rel="noopener">
                <img class="promo-modal__img" src="<?= asset(e($modalPromo['imagen'])) ?>" alt="<?= e($modalPromo['titulo']) ?>" loading="lazy" decoding="async">
            </a>
        <?php else: ?>
            <img class="promo-modal__img" src="<?= asset(e($modalPromo['imagen'])) ?>" alt="<?= e($modalPromo['titulo']) ?>" loading="lazy" decoding="async">
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
