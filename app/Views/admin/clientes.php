<?php
/** @var array $clientes @var bool $pend @var int $totalPend */
$puedeAprobar = can('clientes.aprobar');
?>
<div class="admin-head">
    <h1>Clientes</h1>
</div>

<?php if ($soloAsignados ?? false): ?>
    <p class="text-muted fs-sm mb-4">👤 Mostrando solo los clientes asignados a ti.</p>
<?php endif; ?>

<div class="filters-row">
    <a class="filter-tab <?= !$pend ? 'is-active' : '' ?>" href="<?= url('/admin/clientes') ?>">Todos</a>
    <a class="filter-tab <?= $pend ? 'is-active' : '' ?>" href="<?= url('/admin/clientes?filtro=pendientes') ?>">
        Pendientes<?= $totalPend > 0 ? ' (' . (int) $totalPend . ')' : '' ?>
    </a>
</div>

<?php if ($clientes): ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Nombre</th><th>Empresa</th><th>Contacto</th><th>Dirección</th><th>RFC</th><th>Datos SAE</th><th>Lista de productos</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($clientes as $c): ?>
            <tr>
                <td><?= e($c['nombre']) ?></td>
                <td><?= e($c['empresa'] ?? '—') ?></td>
                <td><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a><?= $c['telefono'] ? '<br>' . e($c['telefono']) : '' ?></td>
                <td class="text-muted fs-sm"><?= e(direccion_texto($c) ?? '—') ?></td>
                <td><?= e($c['rfc'] ?? '—') ?></td>
                <td>
                    <?php if ($puedeAprobar): ?>
                        <form method="post" action="<?= url('/admin/clientes/' . (int) $c['id'] . '/datos-sae') ?>" class="sae-form">
                            <?= csrf_field() ?>
                            <input type="text" name="clave_sae" value="<?= e($c['clave_sae'] ?? '') ?>" placeholder="Clave cliente" aria-label="Clave de SAE del cliente" class="sae-form__clave">
                            <select name="vendedor_id" aria-label="Vendedor asignado" class="sae-form__vend">
                                <option value="">— Sin vendedor —</option>
                                <?php foreach ($vendedores as $v): ?>
                                    <option value="<?= (int) $v['id'] ?>" <?= (int) ($c['vendedor_id'] ?? 0) === (int) $v['id'] ? 'selected' : '' ?>>
                                        <?= e($v['nombre']) ?> (<?= e($v['clave_vendedor']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn--outline btn--sm" type="submit">Guardar</button>
                        </form>
                    <?php else: ?>
                        <?= e($c['clave_sae'] ?? '—') ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($puedeAprobar): ?>
                        <form method="post" action="<?= url('/admin/clientes/' . (int) $c['id'] . '/lista-productos') ?>" class="sae-form">
                            <?= csrf_field() ?>
                            <select name="lista_productos_id" aria-label="Lista de productos del cliente" class="sae-form__vend">
                                <option value="">— Catálogo completo —</option>
                                <?php foreach ($listasProductos as $lp): ?>
                                    <option value="<?= (int) $lp['id'] ?>" <?= (int) ($c['lista_productos_id'] ?? 0) === (int) $lp['id'] ? 'selected' : '' ?>>
                                        <?= e($lp['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn--outline btn--sm" type="submit">Guardar</button>
                        </form>
                    <?php else: ?>
                        <?= e($c['lista_productos_nombre'] ?? 'Catálogo completo') ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ((int) $c['aprobado'] === 1): ?>
                        <span class="pill pill--ok">Aprobado</span>
                    <?php else: ?>
                        <span class="pill pill--warn">Pendiente</span>
                    <?php endif; ?>
                    <?php if ((int) $c['activo'] !== 1): ?>
                        <span class="pill pill--off">Inactivo</span>
                    <?php endif; ?>
                    <?php if (empty($c['email_verificado_en'])): ?>
                        <span class="pill pill--off">Correo sin verificar</span>
                    <?php endif; ?>
                    <?php if ((int) ($c['sin_acceso_portal'] ?? 0) === 1): ?>
                        <span class="pill pill--off" title="No puede iniciar sesión en el portal; los vendedores sí pueden crear pedidos a su nombre">Sin acceso al portal</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($puedeAprobar): ?>
                        <?php if ((int) $c['aprobado'] !== 1): ?>
                            <?php if (empty($c['email_verificado_en'])): ?>
                                <button class="btn btn--primary btn--sm" type="button" disabled title="El cliente debe verificar su correo antes de aprobarlo">Aprobar</button>
                            <?php else: ?>
                                <form class="inline-form" method="post" action="<?= url('/admin/clientes/' . (int) $c['id'] . '/aprobar') ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn--primary btn--sm" type="submit">Aprobar</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                        <form class="inline-form" method="post" action="<?= url('/admin/clientes/' . (int) $c['id'] . '/activo') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn--outline btn--sm" type="submit"><?= (int) $c['activo'] === 1 ? 'Desactivar' : 'Activar' ?></button>
                        </form>
                        <form class="inline-form" method="post" action="<?= url('/admin/clientes/' . (int) $c['id'] . '/portal-acceso') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn--outline btn--sm" type="submit">
                                <?= (int) ($c['sin_acceso_portal'] ?? 0) === 1 ? 'Dar acceso al portal' : 'Quitar acceso al portal' ?>
                            </button>
                        </form>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$baseUrl = url('/admin/clientes') . ($pend ? '?filtro=pendientes' : '');
require APP_PATH . '/Views/partials/pagination.php';
?>
<?php else: ?>
    <p class="empty-state">No hay clientes<?= $pend ? ' pendientes de aprobación' : '' ?>.</p>
<?php endif; ?>
