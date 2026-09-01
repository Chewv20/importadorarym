<?php
/**
 * @var string $desde
 * @var string $hasta
 * @var array $estadosPedido
 * @var array $estadosLead
 * @var bool $soloAsignados
 */
$campoFechas = function (string $desde, string $hasta): string {
    return '<div class="report-fields">'
        . '<label>Desde<input type="date" name="desde" value="' . e($desde) . '"></label>'
        . '<label>Hasta<input type="date" name="hasta" value="' . e($hasta) . '"></label>'
        . '</div>';
};
?>
<div class="admin-head"><h1>Reportes</h1></div>

<?php if ($soloAsignados): ?>
    <p class="text-muted fs-sm mb-4">👤 Los reportes incluyen solo a tus clientes asignados.</p>
<?php endif; ?>

<p class="text-muted mb-8">Genera y descarga reportes en Excel (.xlsx) por rango de fechas.</p>

<div class="report-grid">

    <form class="card-panel" method="get" action="<?= url('/admin/reportes/descargar') ?>">
        <input type="hidden" name="tipo" value="pedidos">
        <h2 class="card-panel__title">Pedidos por periodo</h2>
        <p class="text-muted fs-sm">Folio, fecha, cliente, empresa, estado, partidas, total y folio ERP.</p>
        <div class="report-fields">
            <label>Desde<input type="date" name="desde" value="<?= e($desde) ?>"></label>
            <label>Hasta<input type="date" name="hasta" value="<?= e($hasta) ?>"></label>
            <label>Estado
                <select name="estado">
                    <option value="">Todos</option>
                    <?php foreach ($estadosPedido as $es): ?>
                        <option value="<?= e($es) ?>"><?= e(ucfirst(str_replace('_', ' ', $es))) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <button type="submit" class="btn btn--primary">Descargar Excel</button>
    </form>

    <form class="card-panel" method="get" action="<?= url('/admin/reportes/descargar') ?>">
        <input type="hidden" name="tipo" value="leads">
        <h2 class="card-panel__title">Cotizaciones / leads por periodo</h2>
        <p class="text-muted fs-sm">Folio, fecha, nombre, empresa, correo, teléfono, origen, estado y total.</p>
        <div class="report-fields">
            <label>Desde<input type="date" name="desde" value="<?= e($desde) ?>"></label>
            <label>Hasta<input type="date" name="hasta" value="<?= e($hasta) ?>"></label>
            <label>Estado
                <select name="estado">
                    <option value="">Todos</option>
                    <?php foreach ($estadosLead as $es): ?>
                        <option value="<?= e($es) ?>"><?= e(ucfirst($es)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <button type="submit" class="btn btn--primary">Descargar Excel</button>
    </form>

    <form class="card-panel" method="get" action="<?= url('/admin/reportes/descargar') ?>">
        <input type="hidden" name="tipo" value="productos">
        <h2 class="card-panel__title">Productos más pedidos</h2>
        <p class="text-muted fs-sm">Ranking por producto: SKU, nombre, cantidad total y número de pedidos.</p>
        <?= $campoFechas($desde, $hasta) ?>
        <button type="submit" class="btn btn--primary">Descargar Excel</button>
    </form>

    <form class="card-panel" method="get" action="<?= url('/admin/reportes/descargar') ?>">
        <input type="hidden" name="tipo" value="clientes">
        <h2 class="card-panel__title">Clientes por ventas</h2>
        <p class="text-muted fs-sm">Ranking de clientes por total de pedidos (excluye cancelados).</p>
        <?= $campoFechas($desde, $hasta) ?>
        <button type="submit" class="btn btn--primary">Descargar Excel</button>
    </form>

</div>
