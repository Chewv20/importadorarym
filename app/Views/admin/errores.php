<?php
/** @var array $lineas @var int $tam @var bool $existe @var bool $truncado */
?>
<div class="admin-head">
    <h1>Errores de la aplicación</h1>
    <?php if ($lineas): ?>
    <form method="post" action="<?= url('/admin/errores/limpiar') ?>" data-confirm="¿Vaciar el log de errores? Esta acción no se puede deshacer.">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--outline btn--sm">Vaciar log</button>
    </form>
    <?php endif; ?>
</div>

<p class="text-muted fs-sm mb-6">
    Últimos errores registrados en <code>storage/logs/php-error.log</code>
    <?= $existe ? '· ' . number_format($tam / 1024, 1) . ' KB' : '' ?> · más recientes primero.
    <?php if (!empty($truncado)): ?>
        <br>El archivo es grande: se muestra solo su tramo final. El log se rota solo al superar los 5 MB.
    <?php endif; ?>
</p>

<?php if ($lineas): ?>
    <div class="log-viewer">
        <?php foreach ($lineas as $linea): ?>
            <div class="log-line <?= stripos($linea, '[500]') !== false ? 'is-error' : '' ?>"><?= e($linea) ?></div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="empty-state">Sin errores registrados. 🎉</p>
<?php endif; ?>
