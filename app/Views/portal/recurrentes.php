<?php
/** @var array $plantillas @var array $frecuencias */
$plantillas = $plantillas ?? [];
?>
<h1 class="portal-title">Pedidos recurrentes</h1>
<p class="text-muted mb-6">Programa un pedido para que te lo recordemos cada cierto tiempo, sin tener que
    armar el carrito desde cero. Puedes programarlo desde el detalle de cualquier pedido ya realizado.</p>

<?php if ($plantillas): ?>
    <div class="recurrentes-grid">
        <?php foreach ($plantillas as $p): ?>
            <div class="recurrente-card <?= (int) $p['activo'] === 1 ? '' : 'is-paused' ?>">
                <div class="recurrente-card__head">
                    <h2 class="recurrente-card__title">
                        <a href="<?= url('/portal/recurrentes/' . (int) $p['id']) ?>">
                            <?= e($p['nombre'] ?: ('Pedido recurrente #' . $p['id'])) ?>
                        </a>
                    </h2>
                    <span class="pill <?= (int) $p['activo'] === 1 ? 'pill--ok' : 'pill--off' ?>">
                        <?= (int) $p['activo'] === 1 ? 'Activo' : 'Pausado' ?>
                    </span>
                </div>

                <p class="text-muted fs-sm">
                    <?= count($p['items']) ?> producto(s) · cada <?= (int) $p['frecuencia_dias'] ?> días
                </p>
                <p class="text-muted fs-sm">
                    <?php if ((int) $p['activo'] === 1): ?>
                        Próximo recordatorio: <strong><?= e(date('d/m/Y', strtotime($p['proximo_recordatorio_en']))) ?></strong>
                    <?php else: ?>
                        Sin recordatorios mientras esté pausado.
                    <?php endif; ?>
                </p>

                <div class="recurrente-card__actions">
                    <form method="post" action="<?= url('/portal/recurrentes/' . (int) $p['id'] . '/pedir') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--primary btn--sm">Pedir ahora</button>
                    </form>
                    <?php if ((int) $p['activo'] === 1): ?>
                        <form method="post" action="<?= url('/portal/recurrentes/' . (int) $p['id'] . '/pausar') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn--outline btn--sm">Pausar</button>
                        </form>
                    <?php else: ?>
                        <form method="post" action="<?= url('/portal/recurrentes/' . (int) $p['id'] . '/reanudar') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn--outline btn--sm">Reactivar</button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="<?= url('/portal/recurrentes/' . (int) $p['id'] . '/eliminar') ?>"
                          data-confirm="¿Eliminar este pedido recurrente?">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--ghost btn--sm">Eliminar</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="empty-state">Todavía no tienes pedidos recurrentes.
        Ábre el detalle de un pedido ya realizado y usa «Programar como recurrente».</p>
<?php endif; ?>
