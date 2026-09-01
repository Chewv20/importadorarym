<?php /** @var array $usuarios */ ?>
<div class="admin-head">
    <h1>Usuarios internos</h1>
    <?php if (can('usuarios.gestionar')): ?>
        <a class="btn btn--accent" href="<?= url('/admin/usuarios/nuevo') ?>">+ Nuevo usuario</a>
    <?php endif; ?>
</div>

<?php if ($usuarios): ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Activo</th><th>Último acceso</th><?php if (can('usuarios.gestionar')): ?><th>Acciones</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($usuarios as $u): ?>
            <tr>
                <td><?= e($u['nombre']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= e($u['rol_nombre']) ?></td>
                <td><span class="pill <?= (int) $u['activo'] === 1 ? 'pill--ok' : 'pill--off' ?>"><?= (int) $u['activo'] === 1 ? 'Sí' : 'No' ?></span></td>
                <td><?= $u['last_login_at'] ? e(date('d/m/Y H:i', strtotime($u['last_login_at']))) : '—' ?></td>
                <?php if (can('usuarios.gestionar')): ?>
                    <td><a class="btn btn--outline btn--sm" href="<?= url('/admin/usuarios/' . (int) $u['id'] . '/editar') ?>">Editar</a></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <p class="empty-state">No hay usuarios internos. <a href="<?= url('/admin/usuarios/nuevo') ?>">Crea el primero</a>.</p>
<?php endif; ?>
