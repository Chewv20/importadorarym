<?php /** @var array $roles */ ?>
<div class="admin-head"><h1>Roles y permisos</h1></div>

<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Rol</th><th>Descripción</th><th>Permisos</th><th>Usuarios</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($roles as $r): ?>
            <tr>
                <td><strong><?= e($r['nombre']) ?></strong></td>
                <td><?= e($r['descripcion'] ?? '—') ?></td>
                <td><?= (int) $r['num_permisos'] ?></td>
                <td><?= (int) $r['num_usuarios'] ?></td>
                <td><a class="btn btn--outline btn--sm" href="<?= url('/admin/roles/' . (int) $r['id'] . '/editar') ?>">Editar permisos</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
