<?php /** @var array $logos */ ?>
<div class="admin-head">
    <h1>Logos de proveedores</h1>
    <a class="btn btn--accent" href="<?= url('/admin/logos-proveedores/nuevo') ?>">+ Nuevo logo</a>
</div>

<p class="text-muted fs-sm mb-6">Aparecen en el sitio público (Inicio y Nosotros). Al subir un logo se recorta el fondo y se optimiza automáticamente.</p>

<?php if ($logos): ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Logo</th><th>Nombre</th><th>Orden</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($logos as $l): ?>
            <tr>
                <td><span class="logo-cell"><img src="<?= asset(e($l['imagen'])) ?>" alt="<?= e($l['nombre']) ?>" loading="lazy" decoding="async"></span></td>
                <td><?= e($l['nombre']) ?></td>
                <td><?= (int) $l['orden'] ?></td>
                <td><span class="pill <?= (int) $l['activo'] === 1 ? 'pill--ok' : 'pill--off' ?>"><?= (int) $l['activo'] === 1 ? 'Activo' : 'Inactivo' ?></span></td>
                <td class="ta-right">
                    <div class="admin-head__actions">
                        <a class="btn btn--outline btn--sm" href="<?= url('/admin/logos-proveedores/' . (int) $l['id'] . '/editar') ?>">Editar</a>
                        <form method="post" action="<?= url('/admin/logos-proveedores/' . (int) $l['id'] . '/eliminar') ?>" data-confirm="¿Eliminar este logo?">
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
    <p class="empty-state">Aún no hay logos. <a href="<?= url('/admin/logos-proveedores/nuevo') ?>">Agrega el primero</a>.</p>
<?php endif; ?>
