<?php
/** Miniatura de producto para catálogo/carrito/confirmación de pedido. @var ?string $imagen */
$imagen = $imagen ?? null;
?>
<?php if ($imagen): ?>
    <img class="order-item__img" src="<?= e(asset($imagen)) ?>" alt="" loading="lazy" width="48" height="48">
<?php else: ?>
    <span class="order-item__img order-item__img--empty" aria-hidden="true"></span>
<?php endif; ?>
