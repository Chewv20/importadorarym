<?php /** @var array $zonas */ ?>
<div class="admin-head">
    <h1>Zonas de reparto</h1>
    <a class="btn btn--accent" href="<?= url('/admin/zonas/nueva') ?>">+ Nueva zona</a>
</div>

<p class="text-muted fs-sm mb-6">
    Catálogo informativo para asociar a los envíos — por ahora sin ruteo automático,
    la asignación del repartidor sigue siendo manual.
</p>

<?php if ($zonas): ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Nombre</th><th>Envíos</th><th>Activa</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($zonas as $z): ?>
            <tr>
                <td><?= e($z['nombre']) ?></td>
                <td><?= (int) $z['num_envios'] ?></td>
                <td><span class="pill <?= (int) $z['activa'] === 1 ? 'pill--ok' : 'pill--off' ?>"><?= (int) $z['activa'] === 1 ? 'Sí' : 'No' ?></span></td>
                <td>
                    <a class="btn btn--outline btn--sm" href="<?= url('/admin/zonas/' . (int) $z['id'] . '/editar') ?>">Editar</a>
                    <form class="inline-form" method="post" action="<?= url('/admin/zonas/' . (int) $z['id'] . '/eliminar') ?>" data-confirm="¿Eliminar esta zona? Sus envíos asociados se quedarán sin zona.">
                        <?= csrf_field() ?>
                        <button class="btn btn--outline btn--sm" type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <p class="empty-state">No hay zonas. <a href="<?= url('/admin/zonas/nueva') ?>">Crea la primera</a>.</p>
<?php endif; ?>
