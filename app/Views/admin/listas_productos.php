<?php /** @var array $listas */ ?>
<div class="admin-head">
    <h1>Listas de productos</h1>
    <a class="btn btn--accent" href="<?= url('/admin/listas-productos/nueva') ?>">+ Nueva lista</a>
</div>

<p class="text-muted fs-sm mb-6">
    Acotan qué productos puede ver/pedir un cliente (no a qué precio). Un cliente sin
    lista asignada sigue viendo el catálogo completo.
</p>

<?php if ($listas): ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Nombre</th><th>Descripción</th><th>Productos</th><th>Clientes</th><th>Activa</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($listas as $l): ?>
            <tr>
                <td><?= e($l['nombre']) ?></td>
                <td><?= e($l['descripcion'] ?? '—') ?></td>
                <td><?= (int) $l['num_productos'] ?></td>
                <td><?= (int) $l['num_clientes'] ?></td>
                <td><span class="pill <?= (int) $l['activa'] === 1 ? 'pill--ok' : 'pill--off' ?>"><?= (int) $l['activa'] === 1 ? 'Sí' : 'No' ?></span></td>
                <td>
                    <a class="btn btn--outline btn--sm" href="<?= url('/admin/listas-productos/' . (int) $l['id'] . '/editar') ?>">Editar</a>
                    <form class="inline-form" method="post" action="<?= url('/admin/listas-productos/' . (int) $l['id'] . '/eliminar') ?>" data-confirm="¿Eliminar esta lista? Sus clientes asignados volverán a ver el catálogo completo.">
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
    <p class="empty-state">No hay listas. <a href="<?= url('/admin/listas-productos/nueva') ?>">Crea la primera</a>.</p>
<?php endif; ?>
