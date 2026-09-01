<?php
/**
 * Grilla de logos de proveedores/marcas que distribuye RYM.
 * Se administran desde el panel (Admin > Logos de proveedores); aquí se
 * muestran los activos, ordenados.
 */
$proveedores = (new \App\Models\LogoProveedor())->activos();
if ($proveedores):
?>
<div class="brand-logos">
    <?php foreach ($proveedores as $p): ?>
        <div class="brand-logo reveal">
            <img src="<?= asset(e($p['imagen'])) ?>" alt="<?= e($p['nombre']) ?>" loading="lazy">
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
