<?php
/**
 * Marquesina de logos de clientes. Se administran desde el panel
 * (Admin > Logos de clientes); aquí se muestran los activos, ordenados.
 */
$logos = (new \App\Models\ClienteLogo())->activos();
if ($logos):
?>
<div class="clients-marquee">
    <div class="clients-track">
        <?php for ($copy = 0; $copy < 2; $copy++): ?>
            <?php foreach ($logos as $logo): ?>
                <div class="clients-logo">
                    <img src="<?= asset(e($logo['imagen'])) ?>" alt="<?= e($logo['nombre']) ?>"
                         loading="lazy" <?= $copy === 1 ? 'aria-hidden="true"' : '' ?>>
                </div>
            <?php endforeach; ?>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>
