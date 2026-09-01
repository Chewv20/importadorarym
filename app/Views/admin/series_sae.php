<?php
/** @var array $series Cada una: ['serie'=>'L'..'V', 'ultimo_consecutivo'=>int, 'updated_at'=>...] */
$dias = ['L' => 'Lunes', 'M' => 'Martes', 'X' => 'Miércoles', 'J' => 'Jueves', 'V' => 'Viernes'];
?>
<p class="mb-4"><a href="<?= url('/admin/pedidos') ?>">← Volver a pedidos</a></p>

<div class="admin-head"><h1>Series de SAE</h1></div>

<p class="text-muted mb-6">
    Cada día hábil usa su propia serie y consecutivo: se avanzan solos al exportar a SAE
    (según la fecha de entrega capturada). Corrígelos aquí solo si se desfasan del conteo
    real en SAE (por ejemplo, una exportación que se canceló ya adentro de SAE).
</p>

<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Serie</th><th>Día</th><th>Último consecutivo</th><th>Actualizado</th><th>Corregir</th></tr></thead>
        <tbody>
        <?php foreach ($series as $s): ?>
            <tr>
                <td><strong><?= e($s['serie']) ?></strong></td>
                <td><?= e($dias[$s['serie']] ?? '') ?></td>
                <td><?= (int) $s['ultimo_consecutivo'] ?></td>
                <td><?= e(date('d/m/Y H:i', strtotime($s['updated_at']))) ?></td>
                <td>
                    <form class="inline-form" method="post" action="<?= url('/admin/series-sae') ?>"
                          data-confirm="¿Corregir el consecutivo de la serie &quot;<?= e($s['serie']) ?>&quot;? El siguiente pedido exportado con esta serie usará el número que captures aquí + 1.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="serie" value="<?= e($s['serie']) ?>">
                        <label class="sr-only" for="consecutivo-<?= e($s['serie']) ?>">Corregir consecutivo de la serie <?= e($s['serie']) ?></label>
                        <input type="number" id="consecutivo-<?= e($s['serie']) ?>" name="ultimo_consecutivo" min="0" step="1" value="<?= (int) $s['ultimo_consecutivo'] ?>" class="consecutivo-input" required>
                        <button class="btn btn--outline btn--sm" type="submit">Guardar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
