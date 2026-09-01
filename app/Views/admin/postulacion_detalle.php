<?php
/** @var array $p @var array $estados @var bool $tieneCv */
$id = (int) $p['id'];
$pillMap = ['recibida' => 'pill--warn', 'en_revision' => 'pill--off', 'entrevista' => 'pill--ok', 'rechazada' => 'pill--danger', 'contratada' => 'pill--ok'];
$citaValue = !empty($p['cita_at']) ? date('Y-m-d\TH:i', strtotime($p['cita_at'])) : '';
?>
<p class="mb-4"><a href="<?= url('/admin/postulaciones') ?>">← Volver a postulaciones</a></p>
<div class="admin-head">
    <h1>Postulación de <?= e($p['nombre']) ?></h1>
    <span class="pill <?= $pillMap[$p['estado']] ?? '' ?>"><?= e($estados[$p['estado']] ?? $p['estado']) ?></span>
</div>

<div class="report-grid">

    <section class="card-panel">
        <h2 class="card-panel__title">Datos del candidato</h2>
        <table class="table">
            <tr><td class="text-muted">Vacante</td><td><?= !empty($p['vacante_titulo']) ? e($p['vacante_titulo']) : '<span class="pill pill--off">Solicitud general</span>' ?></td></tr>
            <tr><td class="text-muted">Correo</td><td><a href="mailto:<?= e($p['email']) ?>"><?= e($p['email']) ?></a></td></tr>
            <tr><td class="text-muted">Teléfono</td><td><?= !empty($p['telefono']) ? e($p['telefono']) : '—' ?></td></tr>
            <?php if (!empty($p['area_interes'])): ?>
            <tr><td class="text-muted">Puesto/área de interés</td><td><?= e($p['area_interes']) ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($p['sueldo_deseado'])): ?>
            <tr><td class="text-muted">Sueldo deseado</td><td><?= e($p['sueldo_deseado']) ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($p['disponibilidad'])): ?>
            <tr><td class="text-muted">Disponibilidad</td><td><?= e($p['disponibilidad']) ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($p['escolaridad'])): ?>
            <tr><td class="text-muted">Escolaridad</td><td><?= e($p['escolaridad']) ?></td></tr>
            <?php endif; ?>
            <tr><td class="text-muted">Fecha</td><td><?= e(date('d/m/Y H:i', strtotime($p['created_at']))) ?></td></tr>
            <?php if (!empty($p['cita_at'])): ?>
            <tr><td class="text-muted">Entrevista</td><td><strong><?= e(date('d/m/Y H:i', strtotime($p['cita_at']))) ?></strong></td></tr>
            <?php endif; ?>
        </table>
        <?php if (!empty($p['mensaje'])): ?>
            <p class="mt-4"><strong>Mensaje:</strong><br><?= nl2br(e($p['mensaje'])) ?></p>
        <?php endif; ?>
        <p class="mt-4">
            <?php if ($tieneCv): ?>
                <a class="btn btn--primary" href="<?= url('/admin/postulaciones/' . $id . '/cv') ?>">Descargar CV (PDF)</a>
            <?php else: ?>
                <span class="text-muted">Sin CV adjunto.</span>
            <?php endif; ?>
        </p>
    </section>

    <section class="card-panel">
        <h2 class="card-panel__title">Gestión</h2>

        <?php if (can('postulaciones.gestionar')): ?>
        <form method="post" action="<?= url('/admin/postulaciones/' . $id . '/estado') ?>" class="mb-8">
            <?= csrf_field() ?>
            <div class="field">
                <label for="estado">Cambiar estado</label>
                <select id="estado" name="estado">
                    <?php foreach ($estados as $k => $label): ?>
                        <option value="<?= e($k) ?>" <?= $p['estado'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn--outline">Actualizar estado</button>
        </form>

        <form method="post" action="<?= url('/admin/postulaciones/' . $id . '/cita') ?>">
            <?= csrf_field() ?>
            <div class="field">
                <label for="cita">Agendar entrevista</label>
                <input type="datetime-local" id="cita" name="cita" value="<?= e($citaValue) ?>" required>
            </div>
            <button type="submit" class="btn btn--accent">Agendar y notificar al candidato</button>
            <p class="text-muted fs-sm mt-2">Se enviará un correo al candidato con la fecha y hora.</p>
        </form>
        <?php else: ?>
            <p class="text-muted">No tienes permiso para gestionar postulaciones.</p>
        <?php endif; ?>
    </section>

</div>
