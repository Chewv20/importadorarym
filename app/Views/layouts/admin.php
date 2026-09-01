<?php
$active  = $active ?? '';
$usuario = $usuario ?? [];
function admin_link(string $name, string $current): string
{
    return $name === $current ? 'is-active' : '';
}
?>
<!DOCTYPE html>
<html lang="es" data-base="<?= e(base_url('/')) ?>">
<head>
    <?php require APP_PATH . '/Views/partials/head_basic.php'; ?>
    <?= css_bundle('admin') ?>
</head>
<body>
<div class="admin">
    <aside class="admin-side" id="adminSide">
        <a class="admin-brand" href="<?= url('/admin') ?>" title="Administración">
            <img src="<?= asset('assets/img/logos/importadorarym.jpg') ?>" alt="RYM" width="284" height="92">
        </a>
        <nav class="admin-nav" aria-label="Menú del panel">
            <a class="admin-nav__link <?= admin_link('dashboard', $active) ?>" href="<?= url('/admin') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                <span class="admin-nav__label">Dashboard</span>
            </a>
            <?php if (can('cotizaciones.ver')): ?>
            <a class="admin-nav__link <?= admin_link('cotizaciones', $active) ?>" href="<?= url('/admin/cotizaciones') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span class="admin-nav__label">Cotizaciones</span>
            </a>
            <?php endif; ?>
            <?php if (can('clientes.ver')): ?>
            <a class="admin-nav__link <?= admin_link('clientes', $active) ?>" href="<?= url('/admin/clientes') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span class="admin-nav__label">Clientes</span>
            </a>
            <?php endif; ?>
            <?php if (can('pedidos.ver_todos')): ?>
            <a class="admin-nav__link <?= admin_link('pedidos', $active) ?>" href="<?= url('/admin/pedidos') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0"/></svg>
                <span class="admin-nav__label">Pedidos</span>
            </a>
            <?php endif; ?>
            <?php if (can('productos.ver')): ?>
            <a class="admin-nav__link <?= admin_link('productos', $active) ?>" href="<?= url('/admin/productos') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h12l-1 20H7L6 2zM6 7h12"/></svg>
                <span class="admin-nav__label">Productos</span>
            </a>
            <?php endif; ?>
            <?php if (can('categorias.gestionar')): ?>
            <a class="admin-nav__link <?= admin_link('categorias', $active) ?>" href="<?= url('/admin/categorias') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span class="admin-nav__label">Categorías</span>
            </a>
            <?php endif; ?>
            <?php if (can('listas.gestionar')): ?>
            <a class="admin-nav__link <?= admin_link('listas_productos', $active) ?>" href="<?= url('/admin/listas-productos') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6h11M9 12h11M9 18h11"/><path d="M4 6h.01M4 12h.01M4 18h.01"/></svg>
                <span class="admin-nav__label">Listas de productos</span>
            </a>
            <?php endif; ?>
            <?php if (can('zonas.gestionar')): ?>
            <a class="admin-nav__link <?= admin_link('zonas', $active) ?>" href="<?= url('/admin/zonas') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.4 14.5 16 10 4 20"/><circle cx="18" cy="6" r="3"/></svg>
                <span class="admin-nav__label">Zonas de reparto</span>
            </a>
            <?php endif; ?>
            <?php if (can('pedidos.tracking')): ?>
            <a class="admin-nav__link" href="<?= url('/reparto') ?>" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                <span class="admin-nav__label">Reparto (móvil)</span>
            </a>
            <?php endif; ?>
            <?php if (can('usuarios.ver')): ?>
            <a class="admin-nav__link <?= admin_link('usuarios', $active) ?>" href="<?= url('/admin/usuarios') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span class="admin-nav__label">Usuarios internos</span>
            </a>
            <?php endif; ?>
            <?php if (can('roles.gestionar')): ?>
            <a class="admin-nav__link <?= admin_link('roles', $active) ?>" href="<?= url('/admin/roles') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 4 5v6c0 5 3.4 9.4 8 11 4.6-1.6 8-6 8-11V5z"/></svg>
                <span class="admin-nav__label">Roles y permisos</span>
            </a>
            <?php endif; ?>
            <?php if (can('clientes_logos.gestionar')): ?>
            <a class="admin-nav__link <?= admin_link('logos_clientes', $active) ?>" href="<?= url('/admin/logos-clientes') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                <span class="admin-nav__label">Logos de clientes</span>
            </a>
            <?php endif; ?>
            <?php if (can('logos_proveedores.gestionar')): ?>
            <a class="admin-nav__link <?= admin_link('logos_proveedores', $active) ?>" href="<?= url('/admin/logos-proveedores') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
                <span class="admin-nav__label">Logos de proveedores</span>
            </a>
            <?php endif; ?>
            <?php if (can('modales.gestionar')): ?>
            <a class="admin-nav__link <?= admin_link('modales', $active) ?>" href="<?= url('/admin/modales') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 21h8M12 18v3"/></svg>
                <span class="admin-nav__label">Modales</span>
            </a>
            <?php endif; ?>
            <?php if (can('vacantes.gestionar')): ?>
            <a class="admin-nav__link <?= admin_link('vacantes', $active) ?>" href="<?= url('/admin/vacantes') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                <span class="admin-nav__label">Vacantes</span>
            </a>
            <?php endif; ?>
            <?php if (can('postulaciones.ver')): ?>
            <a class="admin-nav__link <?= admin_link('postulaciones', $active) ?>" href="<?= url('/admin/postulaciones') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 15l2 2 4-4"/></svg>
                <span class="admin-nav__label">Postulaciones</span>
            </a>
            <?php endif; ?>
            <?php if (can('reportes.ver')): ?>
            <a class="admin-nav__link <?= admin_link('reportes', $active) ?>" href="<?= url('/admin/reportes') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 15l3-4 3 3 4-6"/></svg>
                <span class="admin-nav__label">Reportes</span>
            </a>
            <?php endif; ?>
            <?php if (can('encuestas.ver')): ?>
            <a class="admin-nav__link <?= admin_link('encuestas', $active) ?>" href="<?= url('/admin/encuestas') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 15 8l6 .5-4.5 4 1.5 6-6-3.5L6 18.5 7.5 12.5 3 8.5 9 8z"/></svg>
                <span class="admin-nav__label">Encuestas</span>
            </a>
            <?php endif; ?>
            <?php if (can('visitas.ver')): ?>
            <a class="admin-nav__link <?= admin_link('visitas', $active) ?>" href="<?= url('/admin/visitas') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span class="admin-nav__label">Visitas</span>
            </a>
            <?php endif; ?>
            <?php if (can('auditoria.ver')): ?>
            <a class="admin-nav__link <?= admin_link('auditoria', $active) ?>" href="<?= url('/admin/auditoria') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
                <span class="admin-nav__label">Auditoría</span>
            </a>
            <a class="admin-nav__link <?= admin_link('errores', $active) ?>" href="<?= url('/admin/errores') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                <span class="admin-nav__label">Errores</span>
            </a>
            <?php endif; ?>
        </nav>
        <div class="admin-side__foot">
            <a href="<?= url('/') ?>" target="_blank" rel="noopener">Ver sitio →</a>
            <form method="post" action="<?= url('/portal/logout') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--ghost btn--sm btn--block">Cerrar sesión</button>
            </form>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-top">
            <button class="admin-burger" id="adminBurger" aria-label="Menú">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <span class="admin-top__title">Panel de administración</span>
            <span class="admin-user">Hola, <?= e($usuario['nombre'] ?? '') ?><?= !empty($usuario['rol_nombre']) ? ' · ' . e($usuario['rol_nombre']) : '' ?></span>
        </header>
        <main class="admin-content <?= !empty($contentWide) ? 'admin-content--wide' : '' ?>">
            <?php require APP_PATH . '/Views/partials/flash.php'; ?>
            <?= $content ?? '' ?>
        </main>
    </div>
</div>

<script src="<?= asset('assets/js/admin.js') ?>" defer></script>
<script src="<?= asset('assets/js/pwa.js') ?>" defer></script>
</body>
</html>
