<?php
/** @var array $pedidos */
$pedidos = $pedidos ?? [];
?>
<h1 class="portal-title">Mis pedidos</h1>

<?php if ($pedidos): ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Folio</th><th>Fecha</th><th>Estado</th><th>ERP</th><th><span class="sr-only">Acciones</span></th></tr></thead>
            <tbody>
            <?php foreach ($pedidos as $p): ?>
                <tr>
                    <td><?= e($p['folio']) ?></td>
                    <td><?= e(date('d/m/Y', strtotime($p['created_at']))) ?></td>
                    <td><span class="status status--<?= e($p['estado']) ?>"><?= e(ucfirst(str_replace('_', ' ', $p['estado']))) ?></span></td>
                    <td><?= !empty($p['erp_folio']) ? e($p['erp_folio']) : '—' ?></td>
                    <td><a class="btn btn--outline btn--sm" href="<?= url('/portal/pedidos/' . (int) $p['id']) ?>">Ver detalle</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="empty-state">Todavía no has realizado pedidos. <a href="<?= url('/portal/pedidos/nuevo') ?>">Crea tu primer pedido</a>.</p>
<?php endif; ?>
