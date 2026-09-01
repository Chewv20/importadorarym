<?php
/** @var array $usuario */
/** @var array $recientes */
/** @var int $totalPedidos */
$aprobado = (int) ($usuario['aprobado'] ?? 0) === 1;
$verificado = !empty($usuario['email_verificado_en']);
?>
<h1 class="portal-title">Hola, <?= e($usuario['nombre']) ?></h1>

<?php if (!$verificado): ?>
    <div class="alert alert--error mb-8">
        Tu correo <strong><?= e($usuario['email']) ?></strong> aún no está verificado.
        Revisa tu bandeja de entrada; es necesario para que podamos activar tus pedidos.
        <form class="inline-form mt-2" method="post" action="<?= url('/portal/verificar/reenviar') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--outline btn--sm">Reenviar correo de verificación</button>
        </form>
    </div>
<?php endif; ?>

<?php if (!$aprobado): ?>
    <div class="alert alert--ok mb-8">
        Tu cuenta está <strong>pendiente de aprobación</strong>. Ya puedes
        <strong>solicitar cotizaciones</strong>; en cuanto un asesor active tu cuenta
        podrás levantar pedidos en línea.
    </div>
<?php endif; ?>

<div class="grid grid--3 mb-12">
    <a class="card" href="<?= url('/portal/cotizar') ?>">
        <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
        <h2 class="card__title">Solicitar cotización</h2>
        <p class="card__text">Pide una cotización a la medida de tu negocio.</p>
    </a>

    <?php if ($aprobado): ?>
        <a class="card" href="<?= url('/portal/pedidos/nuevo') ?>">
            <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></div>
            <h2 class="card__title">Nuevo pedido</h2>
            <p class="card__text">Arma tu pedido en minutos.</p>
        </a>
    <?php endif; ?>

    <a class="card" href="<?= url('/portal/perfil') ?>">
        <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
        <h2 class="card__title">Mi perfil</h2>
        <p class="card__text">Actualiza tus datos de contacto y facturación.</p>
    </a>
</div>

<?php if ($aprobado): ?>
    <h2 class="portal-subtitle">Pedidos recientes</h2>
    <?php if ($recientes): ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Folio</th><th>Fecha</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr></thead>
                <tbody>
                <?php foreach ($recientes as $p): ?>
                    <tr>
                        <td><?= e($p['folio']) ?></td>
                        <td><?= e(date('d/m/Y', strtotime($p['created_at']))) ?></td>
                        <td><span class="status status--<?= e($p['estado']) ?>"><?= e(ucfirst(str_replace('_', ' ', $p['estado']))) ?></span></td>
                        <td><a class="btn btn--outline btn--sm" href="<?= url('/portal/pedidos/' . (int) $p['id']) ?>">Ver</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty-state">Aún no tienes pedidos. <a href="<?= url('/portal/pedidos/nuevo') ?>">Crea tu primer pedido</a>.</p>
    <?php endif; ?>
<?php endif; ?>
