<?php /** @var array $categorias Plana, ordenada por jerarquía; cada fila trae 'nivel' (0 o 1). */ ?>
<div class="admin-head">
    <h1>Categorías</h1>
    <a class="btn btn--accent" href="<?= url('/admin/categorias/nueva') ?>">+ Nueva categoría</a>
</div>

<p class="text-muted fs-sm mb-6">Hasta 2 niveles: una categoría principal puede tener subcategorías (ej. Biodegradables → Bolsas, Cubiertos).</p>

<?php if ($categorias): ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Nombre</th><th>Slug</th><th>Productos</th><th>Orden</th><th>Activa</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($categorias as $c): ?>
            <tr class="<?= $c['nivel'] === 1 ? 'cat-row--sub' : '' ?>">
                <td><?= $c['nivel'] === 1 ? '<span class="cat-row__indent">↳</span> ' : '' ?><?= e($c['nombre']) ?></td>
                <td><?= e($c['slug']) ?></td>
                <td><?= (int) $c['num_productos'] ?></td>
                <td><?= (int) $c['orden'] ?></td>
                <td><span class="pill <?= (int) $c['activo'] === 1 ? 'pill--ok' : 'pill--off' ?>"><?= (int) $c['activo'] === 1 ? 'Sí' : 'No' ?></span></td>
                <td>
                    <a class="btn btn--outline btn--sm" href="<?= url('/admin/categorias/' . (int) $c['id'] . '/editar') ?>">Editar</a>
                    <form class="inline-form" method="post" action="<?= url('/admin/categorias/' . (int) $c['id'] . '/eliminar') ?>">
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
    <p class="empty-state">No hay categorías. <a href="<?= url('/admin/categorias/nueva') ?>">Crea la primera</a>.</p>
<?php endif; ?>
