<?php
/**
 * @var array $dispositivos
 * @var string|null $enlace
 */
$estado = static function (array $d): array {
    if ((int) $d['activo'] !== 1)            return ['Revocado', 'pill--off'];
    if (empty($d['device_token_hash']))      return ['Pendiente de activar', 'pill--warn'];
    return ['Activo', 'pill--ok'];
};
?>
<div class="admin-head"><h1>Libreta de visitas</h1></div>

<div class="filters-row">
    <a class="filter-tab" href="<?= url('/admin/visitas') ?>">Registros</a>
    <a class="filter-tab is-active" href="<?= url('/admin/visitas/dispositivos') ?>">Dispositivos</a>
    <a class="filter-tab" href="<?= url('/admin/visitas/anfitriones') ?>">Anfitriones</a>
</div>

<?php if ($enlace): ?>
    <div class="card-panel mb-8">
        <h2 class="card-panel__title">Enlace de activación</h2>
        <p class="text-muted fs-sm">Ábrelo <strong>una sola vez</strong> en la tablet correspondiente. Queda autorizada y el enlace se invalida.
        Caduca en <strong><?= (int) \App\Models\ChecadorDispositivo::ACTIVACION_HORAS ?> horas</strong>; si vence, genera uno nuevo con «Regenerar enlace».</p>
        <input class="input-copia" type="text" value="<?= e($enlace) ?>" readonly>
    </div>
<?php endif; ?>

<form class="filters-row" method="post" action="<?= url('/admin/visitas/dispositivos') ?>">
    <?= csrf_field() ?>
    <input type="text" name="nombre" placeholder="Nombre del dispositivo (ej. Recepción Planta)" maxlength="80" required>
    <button type="submit" class="btn btn--accent">Crear dispositivo</button>
</form>

<?php if ($dispositivos): ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Dispositivo</th><th>Estado</th><th>Último uso</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($dispositivos as $d): [$txt, $cls] = $estado($d); ?>
            <tr>
                <td><?= e($d['nombre']) ?></td>
                <td><span class="pill <?= $cls ?>"><?= e($txt) ?></span></td>
                <td class="text-muted fs-sm"><?= !empty($d['ultimo_uso_en']) ? e(date('d/m/Y H:i', strtotime($d['ultimo_uso_en']))) : '—' ?></td>
                <td>
                    <form method="post" action="<?= url('/admin/visitas/dispositivos/' . (int) $d['id'] . '/regenerar') ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--outline btn--sm">Regenerar enlace</button>
                    </form>
                    <?php if ((int) $d['activo'] === 1): ?>
                    <form method="post" action="<?= url('/admin/visitas/dispositivos/' . (int) $d['id'] . '/revocar') ?>" class="inline-form"
                          data-confirm="¿Revocar este dispositivo? Dejará de tener acceso al kiosco.">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--outline btn--sm">Revocar</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <p class="empty-state">Aún no hay dispositivos. Crea uno y ábrelo en la tablet de recepción.</p>
<?php endif; ?>
