<?php
/** @var array $cotizaciones */
$labels = [
    'nueva'      => ['En revisión', 'status--enviado'],
    'cotizada'   => ['Lista para aprobar', 'status--en_proceso'],
    'aprobada'   => ['Aprobada', 'status--sincronizado'],
    'rechazada'  => ['Rechazada', 'status--borrador'],
    'convertida' => ['Convertida a pedido', 'status--sincronizado'],
];
?>
<div class="portal-head">
    <h1 class="portal-title">Mis cotizaciones</h1>
    <a class="btn btn--accent btn--sm" href="<?= url('/portal/cotizar') ?>">+ Nueva solicitud</a>
</div>

<?php if ($cotizaciones): ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Folio</th><th>Fecha</th><th>Estado</th><th>Total</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($cotizaciones as $c): ?>
            <?php [$txt, $cls] = $labels[$c['estado']] ?? [$c['estado'], 'status--borrador']; ?>
            <tr>
                <td><strong><?= e($c['folio'] ?? '—') ?></strong></td>
                <td class="nowrap"><?= e(date('d/m/Y', strtotime($c['created_at']))) ?></td>
                <td><span class="status <?= $cls ?>"><?= e($txt) ?></span></td>
                <td><?= $c['total'] !== null ? '$' . number_format((float) $c['total'], 2) : '—' ?></td>
                <td><a class="btn btn--outline btn--sm" href="<?= url('/portal/cotizaciones/' . (int) $c['id']) ?>">Ver</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <p class="empty-state">Aún no tienes cotizaciones. <a href="<?= url('/portal/cotizar') ?>">Solicita una aquí</a>.</p>
<?php endif; ?>
