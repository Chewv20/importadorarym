<?php
/** @var array $pendientes @var array $entregados */
$entregadoOk = flash('reparto_entregado_ok');
$zonasDistintas = array_unique(array_map(static fn ($e) => $e['zona_nombre'] ?? '', $pendientes));
$agruparPorZona = count($zonasDistintas) > 1;
$zonaActual = '__inicio__';
?>
<div class="reparto-head"<?= $entregadoOk ? ' data-entregado-ok' : '' ?>>
    <h1 class="reparto-title">Tus envíos</h1>
    <a class="reparto-refresh" href="<?= url('/reparto') ?>" title="Actualizar">↻ Actualizar</a>
</div>

<?php if (!$pendientes): ?>
    <p class="reparto-empty">No tienes envíos pendientes de repartir por ahora.</p>
<?php else: ?>
    <p class="reparto-count"><?= count($pendientes) ?> envío(s) pendiente(s).</p>
    <?php foreach ($pendientes as $en):
        $direccion = direccion_texto($en);
        $mapsUrl = $direccion !== null ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($direccion) : null;
        $enRuta = $en['evento'] === 'en_ruta';
        $zonaNombre = $en['zona_nombre'] ?? '';
        if ($agruparPorZona && $zonaNombre !== $zonaActual):
            $zonaActual = $zonaNombre;
    ?>
        <h2 class="reparto-zona-head"><?= $zonaNombre !== '' ? e($zonaNombre) : 'Sin zona' ?></h2>
    <?php endif; ?>
        <div class="reparto-card <?= $enRuta ? 'reparto-card--enruta' : '' ?>">
            <div class="reparto-card__folio">
                <?= e($en['folio']) ?>
                <?php if (!$agruparPorZona && !empty($en['zona_nombre'])): ?><span class="reparto-card__zona"><?= e($en['zona_nombre']) ?></span><?php endif; ?>
            </div>
            <div class="reparto-card__cliente"><?= e($en['cliente_nombre']) ?></div>
            <?php if ($direccion): ?>
                <p class="reparto-card__dir">
                    <?= e($direccion) ?>
                    <a href="<?= e($mapsUrl) ?>" target="_blank" rel="noopener">Ver en mapa →</a>
                </p>
            <?php endif; ?>
            <?php if (!empty($en['cliente_telefono'])): ?>
                <p class="reparto-card__tel"><a href="tel:<?= e($en['cliente_telefono']) ?>">📞 <?= e($en['cliente_telefono']) ?></a></p>
            <?php endif; ?>
            <?php if (!empty($en['notas'])): ?>
                <p class="reparto-card__notas">📝 <?= e($en['notas']) ?></p>
            <?php endif; ?>

            <?php if (!$enRuta): ?>
                <form method="post" action="<?= url('/reparto/' . (int) $en['id'] . '/en-ruta') ?>" class="reparto-card__form">
                    <?= csrf_field() ?>
                    <input type="datetime-local" name="eta" aria-label="Hora estimada de llegada (opcional)">
                    <button type="submit" class="reparto-btn reparto-btn--ruta">Salió a ruta</button>
                </form>
            <?php else: ?>
                <?php if (!empty($en['eta'])): ?>
                    <p class="reparto-card__eta">ETA: <?= e(date('d/m/Y H:i', strtotime($en['eta']))) ?></p>
                <?php endif; ?>
                <form method="post" action="<?= url('/reparto/' . (int) $en['id'] . '/entregado') ?>" data-confirm="¿Confirmar que este pedido ya se entregó?">
                    <?= csrf_field() ?>
                    <button type="submit" class="reparto-btn reparto-btn--entregado">Entregado</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($entregados): ?>
    <h2 class="reparto-subtitle">Entregados recientemente</h2>
    <ul class="reparto-history">
        <?php foreach ($entregados as $en): ?>
            <li>
                <span><?= e($en['folio']) ?> — <?= e($en['cliente_nombre']) ?></span>
                <span class="reparto-history__fecha"><?= e(date('d/m/Y H:i', strtotime($en['updated_at']))) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (can('admin.acceder')): ?>
    <a class="btn btn--outline btn--block mt-8" href="<?= url('/admin') ?>">← Volver al panel de administración</a>
<?php endif; ?>
