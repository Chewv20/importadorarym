<?php /** Visor ampliado de imágenes de producto (catálogo). Un solo overlay reutilizado por todas las tarjetas con data-lightbox. */ ?>
<div class="lightbox" id="lightbox" hidden>
    <div class="lightbox__backdrop" data-lightbox-close></div>
    <div class="lightbox__box" role="dialog" aria-modal="true" aria-label="Imagen ampliada del producto">
        <button type="button" class="lightbox__close" data-lightbox-close aria-label="Cerrar">&times;</button>
        <button type="button" class="lightbox__nav lightbox__nav--prev" data-lightbox-prev aria-label="Imagen anterior">&#8249;</button>
        <img class="lightbox__img" id="lightboxImg" src="" alt="">
        <button type="button" class="lightbox__nav lightbox__nav--next" data-lightbox-next aria-label="Imagen siguiente">&#8250;</button>
        <div class="lightbox__dots" id="lightboxDots"></div>
    </div>
</div>
