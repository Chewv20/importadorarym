<?php /** @var array $modales */ ?>
<div class="admin-head">
    <h1>Modales del sitio</h1>
    <a class="btn btn--accent" href="<?= url('/admin/modales/nuevo') ?>">+ Nuevo modal</a>
</div>

<p class="text-muted fs-sm mb-6">Se muestran en el sitio público (no en el panel ni el portal). Si hay varios activos, se muestra el de menor orden.</p>

<?php if ($modales): ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Imagen</th><th>Título</th><th>Enlace</th><th>Orden</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($modales as $m): ?>
            <tr>
                <td><img class="thumb-modal" src="<?= asset(e($m['imagen'])) ?>" alt="" width="64" height="44" loading="lazy" decoding="async"></td>
                <td><?= e($m['titulo']) ?></td>
                <td class="text-muted fs-sm"><?= $m['enlace'] ? e($m['enlace']) : '—' ?></td>
                <td><?= (int) $m['orden'] ?></td>
                <td><span class="pill <?= (int) $m['activo'] === 1 ? 'pill--ok' : 'pill--off' ?>"><?= (int) $m['activo'] === 1 ? 'Activo' : 'Inactivo' ?></span></td>
                <td class="ta-right">
                    <div class="admin-head__actions">
                        <a class="btn btn--outline btn--sm" href="<?= url('/admin/modales/' . (int) $m['id'] . '/editar') ?>">Editar</a>
                        <form method="post" action="<?= url('/admin/modales/' . (int) $m['id'] . '/eliminar') ?>" data-confirm="¿Eliminar este modal?">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn--outline btn--sm">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <p class="empty-state">Aún no hay modales. <a href="<?= url('/admin/modales/nuevo') ?>">Crea el primero</a>.</p>
<?php endif; ?>
