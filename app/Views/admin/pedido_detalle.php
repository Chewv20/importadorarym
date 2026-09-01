<?php
/**
 * @var array $pedido @var array $items @var array $estados @var array $flujo
 * @var array $envios @var int $partidasPendientes @var array $frecuencias
 * @var array $facturasPorEnvio
 */
$labels = ['borrador' => 'Borrador', 'enviado' => 'Enviado', 'en_proceso' => 'En proceso', 'parcial' => 'Parcial', 'sincronizado' => 'Sincronizado', 'cancelado' => 'Cancelado'];
$cancelado = $pedido['estado'] === 'cancelado';
// 'parcial' no es un paso fijo del pipeline (puede saltarse si todo se exporta de
// una vez) — mientras el pedido está parcial, el pipeline se ve igual que "en
// proceso"; el pill de arriba sí distingue "Parcial" con su propio color.
$idx = array_search($pedido['estado'], $flujo, true);
if ($idx === false) {
    $idx = array_search('en_proceso', $flujo, true);
}
$puedeEstado = can('pedidos.actualizar_estado');
$puedeErp = can('pedidos.sincronizar_erp');
?>
<p class="mb-4"><a href="<?= url('/admin/pedidos') ?>">← Volver a pedidos</a></p>

<div class="admin-head">
    <h1>Pedido <?= e($pedido['folio']) ?></h1>
    <span class="status status--<?= e($pedido['estado']) ?>"><?= e($labels[$pedido['estado']] ?? $pedido['estado']) ?></span>
</div>

<!-- Pipeline de estados -->
<div class="pipeline <?= $cancelado ? 'pipeline--cancel' : '' ?>">
    <?php foreach ($flujo as $i => $paso):
        $cls = $cancelado ? '' : ($i < $idx ? 'is-done' : ($i === $idx ? 'is-current' : ''));
    ?>
        <div class="pipeline__step <?= $cls ?>">
            <div class="pipeline__dot">
                <?php if ($cls === 'is-done'): ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>
                <?php else: ?>
                    <?= $i + 1 ?>
                <?php endif; ?>
            </div>
            <div class="pipeline__label"><?= e($labels[$paso]) ?></div>
        </div>
    <?php endforeach; ?>
</div>
<?php if ($cancelado): ?>
    <p class="alert alert--error mb-6">Este pedido fue cancelado.</p>
<?php endif; ?>

<div class="detail-grid">
    <!-- Columna principal -->
    <div>
        <div class="panel">
            <div class="panel__title">Cliente</div>
            <dl class="data-list">
                <div><dt>Nombre</dt><dd><?= e($pedido['cliente_nombre']) ?></dd></div>
                <div><dt>Empresa</dt><dd><?= e($pedido['cliente_empresa'] ?? '—') ?></dd></div>
                <div><dt>Correo</dt><dd><a href="mailto:<?= e($pedido['cliente_email']) ?>"><?= e($pedido['cliente_email']) ?></a></dd></div>
                <div><dt>Teléfono</dt><dd><?= e($pedido['cliente_telefono'] ?? '—') ?></dd></div>
                <div><dt>RFC</dt><dd><?= e($pedido['cliente_rfc'] ?? '—') ?></dd></div>
                <div><dt>Su pedido (ref. cliente)</dt><dd><?= e($pedido['referencia_cliente'] ?? '—') ?></dd></div>
                <div><dt>Fecha</dt><dd><?= e(date('d/m/Y H:i', strtotime($pedido['created_at']))) ?></dd></div>
            </dl>
        </div>

        <div class="panel">
            <div class="panel__title">Productos solicitados</div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Producto</th><th>SKU</th><th>Cantidad</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= e($it['nombre']) ?></td>
                            <td><?= !empty($it['sku']) ? e($it['sku']) : '—' ?></td>
                            <td><?= (int) $it['cantidad'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($pedido['notas'])): ?>
            <div class="panel">
                <div class="panel__title">Notas del cliente</div>
                <p><?= nl2br(e($pedido['notas'])) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Columna de acciones -->
    <div>
        <?php if ($puedeEstado): ?>
            <div class="panel">
                <div class="panel__title">Cambiar estado</div>
                <form method="post" action="<?= url('/admin/pedidos/' . (int) $pedido['id'] . '/estado') ?>">
                    <?= csrf_field() ?>
                    <div class="field mb-4">
                        <select name="estado" aria-label="Nuevo estado del pedido">
                            <?php foreach ($estados as $e): ?>
                                <option value="<?= e($e) ?>" <?= $pedido['estado'] === $e ? 'selected' : '' ?>><?= e($labels[$e] ?? $e) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn--accent btn--block">Guardar estado</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($puedeErp): ?>
            <div class="panel">
                <div class="panel__title">Sincronización ERP</div>
                <?php if (!empty($pedido['erp_folio'])): ?>
                    <p class="mb-4">Folio ERP: <strong><?= e($pedido['erp_folio']) ?></strong>
                        <?php if (!empty($pedido['erp_sincronizado_at'])): ?>
                            <br><span class="text-muted fs-sm">Sincronizado el <?= e(date('d/m/Y H:i', strtotime($pedido['erp_sincronizado_at']))) ?></span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                <?php $yaExportado = !empty($exportaciones); ?>
                <?php if ($partidasPendientes > 0): ?>
                    <a class="btn btn--outline btn--block mb-2" href="<?= url('/admin/pedidos/' . (int) $pedido['id'] . '/exportar') ?>">
                        <?= $yaExportado ? 'Exportar partidas pendientes' : 'Exportar a SAE (Excel)' ?>
                        (<?= (int) $partidasPendientes ?>)
                    </a>
                    <p class="text-muted fs-sm mb-4"><?= (int) $partidasPendientes ?> partida(s) sin exportar todavía.</p>
                <?php else: ?>
                    <p class="text-muted fs-sm mb-4">Ya se exportaron todas las partidas de este pedido.</p>
                <?php endif; ?>
                <form method="post" action="<?= url('/admin/pedidos/' . (int) $pedido['id'] . '/erp') ?>">
                    <?= csrf_field() ?>
                    <div class="field mb-4">
                        <label for="erp_folio">Folio del ERP</label>
                        <input type="text" id="erp_folio" name="erp_folio" value="<?= e($pedido['erp_folio'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn--primary btn--block">Marcar como sincronizado</button>
                </form>

                <?php if ($partidasPendientes > 0): ?>
                    <form method="post" action="<?= url('/admin/pedidos/' . (int) $pedido['id'] . '/recordatorio-sae') ?>" class="form form--inline mt-6">
                        <?= csrf_field() ?>
                        <div class="field">
                            <label for="dias">Recuérdame las partidas pendientes en</label>
                            <select id="dias" name="dias">
                                <?php foreach ($frecuencias as $f): ?>
                                    <option value="<?= (int) $f ?>" <?= $f === 7 ? 'selected' : '' ?>><?= (int) $f ?> días</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn--outline btn--sm">Programar recordatorio</button>
                    </form>
                <?php endif; ?>

                <?php if (!$envios && $yaExportado): ?>
                    <div class="mt-6">
                        <div class="panel__title">Historial de exportaciones</div>
                        <ul class="export-log">
                            <?php foreach ($exportaciones as $ex): ?>
                                <li>
                                    <span class="nowrap"><?= e(date('d/m/Y H:i', strtotime($ex['created_at']))) ?></span>
                                    <span class="pill pill--off"><?= $ex['tipo'] === 'lote' ? 'Lote' : 'Individual' ?></span>
                                    <span class="text-muted fs-sm"><?= e($ex['usuario_email'] ?? '—') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($envios): ?>
            <?php
            $eventoLabels = ['en_ruta' => 'En ruta', 'entregado' => 'Entregado'];
            ?>
            <div class="panel">
                <div class="panel__title">Envíos y reparto</div>
                <?php foreach ($envios as $en): ?>
                    <div class="envio-card">
                        <div class="envio-card__head">
                            <span class="nowrap"><?= e(date('d/m/Y H:i', strtotime($en['created_at']))) ?></span>
                            <span class="pill pill--off"><?= (int) $en['num_partidas'] ?> partida(s)</span>
                            <?php if (!empty($en['evento'])): ?>
                                <span class="status status--<?= $en['evento'] === 'entregado' ? 'sincronizado' : 'en_proceso' ?>"><?= e($eventoLabels[$en['evento']] ?? $en['evento']) ?></span>
                            <?php else: ?>
                                <span class="status status--borrador">Sin repartir</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($en['sae_serie']) && $en['sae_consecutivo'] !== null): ?>
                            <p class="text-muted fs-sm mb-2">
                                Clave SAE: <strong><?= e($en['sae_serie'] . $en['sae_consecutivo']) ?></strong>
                                <?php if (!empty($en['fecha_entrega'])): ?>
                                    · Entrega: <?= e(date('d/m/Y', strtotime($en['fecha_entrega']))) ?>
                                <?php endif; ?>
                                <?php if ($puedeErp): ?>
                                    · <a href="<?= url('/admin/envios/' . (int) $en['id'] . '/exportacion') ?>">Volver a descargar Excel</a>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($en['eta'])): ?>
                            <p class="text-muted fs-sm mb-2">ETA: <?= e(date('d/m/Y H:i', strtotime($en['eta']))) ?></p>
                        <?php endif; ?>

                        <?php if ($puedeEstado): ?>
                            <form method="post" action="<?= url('/admin/envios/' . (int) $en['id'] . '/repartidor') ?>" class="envio-card__form">
                                <?= csrf_field() ?>
                                <select name="repartidor_id" aria-label="Repartidor asignado">
                                    <option value="">— Sin repartidor —</option>
                                    <?php foreach ($repartidores as $r): ?>
                                        <option value="<?= (int) $r['id'] ?>" <?= (int) ($en['repartidor_id'] ?? 0) === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="zona_id" aria-label="Zona de reparto">
                                    <option value="">— Sin zona —</option>
                                    <?php foreach ($zonas as $z): ?>
                                        <option value="<?= (int) $z['id'] ?>" <?= (int) ($en['zona_id'] ?? 0) === (int) $z['id'] ? 'selected' : '' ?>><?= e($z['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn--outline btn--sm">Guardar</button>
                            </form>
                        <?php elseif (!empty($en['repartidor_nombre'])): ?>
                            <p class="text-muted fs-sm">Repartidor: <?= e($en['repartidor_nombre']) ?><?= !empty($en['zona_nombre']) ? ' · ' . e($en['zona_nombre']) : '' ?></p>
                        <?php endif; ?>

                        <?php if ($puedeErp): ?>
                            <div class="envio-card__facturas">
                                <?php foreach (($facturasPorEnvio[$en['id']] ?? []) as $f): ?>
                                    <div class="factura-row">
                                        <span class="text-muted fs-sm"><?= e(date('d/m/Y', strtotime($f['created_at']))) ?></span>
                                        <a class="btn btn--outline btn--sm" href="<?= url('/admin/facturas/' . (int) $f['id']) ?>?tipo=pdf">PDF</a>
                                        <a class="btn btn--outline btn--sm" href="<?= url('/admin/facturas/' . (int) $f['id']) ?>?tipo=xml">XML</a>
                                        <form class="inline-form" method="post" action="<?= url('/admin/facturas/' . (int) $f['id'] . '/eliminar') ?>" data-confirm="¿Eliminar esta factura?">
                                            <?= csrf_field() ?>
                                            <button class="btn btn--outline btn--sm" type="submit">Eliminar</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                                <form method="post" action="<?= url('/admin/envios/' . (int) $en['id'] . '/factura') ?>" enctype="multipart/form-data" class="envio-card__form mt-2">
                                    <?= csrf_field() ?>
                                    <input type="file" name="archivo_pdf" accept="application/pdf" aria-label="PDF de la factura">
                                    <input type="file" name="archivo_xml" accept=".xml,text/xml,application/xml" aria-label="XML de la factura">
                                    <button type="submit" class="btn btn--outline btn--sm">Subir factura</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
