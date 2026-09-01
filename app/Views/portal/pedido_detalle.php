<?php
/** @var array $pedido */
/** @var array $items */
$items = $items ?? [];
$stars = static function (int $n): string {
    return str_repeat('★', $n) . '<span class="stars-off">' . str_repeat('★', 5 - $n) . '</span>';
};
?>
<p class="mb-4"><a href="<?= url('/portal/pedidos') ?>">← Volver a mis pedidos</a></p>
<h1 class="portal-title">Pedido <?= e($pedido['folio']) ?></h1>

<div class="flex flex-wrap gap-4 items-center mb-8">
    <span class="status status--<?= e($pedido['estado']) ?>"><?= e(ucfirst(str_replace('_', ' ', $pedido['estado']))) ?></span>
    <span class="text-muted fs-sm">Creado el <?= e(date('d/m/Y H:i', strtotime($pedido['created_at']))) ?></span>
    <?php if (!empty($pedido['referencia_cliente'])): ?>
        <span class="text-muted fs-sm">· Tu referencia: <strong><?= e($pedido['referencia_cliente']) ?></strong></span>
    <?php endif; ?>
    <?php if (!empty($pedido['erp_folio'])): ?>
        <span class="text-muted fs-sm">· Folio ERP: <strong><?= e($pedido['erp_folio']) ?></strong></span>
    <?php endif; ?>
</div>

<form method="post" action="<?= url('/portal/pedidos/' . (int) $pedido['id'] . '/repetir') ?>" class="mb-8">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn--primary">↻ Volver a pedir</button>
    <span class="text-muted fs-sm ml-2">Carga estos productos en tu carrito para pedirlos de nuevo.</span>
</form>

<form method="post" action="<?= url('/portal/pedidos/' . (int) $pedido['id'] . '/recurrente') ?>" class="form form--inline mb-8">
    <?= csrf_field() ?>
    <div class="field">
        <label for="frecuencia_dias">Recordarme repetirlo cada</label>
        <select id="frecuencia_dias" name="frecuencia_dias">
            <?php foreach (\App\Models\PedidoRecurrente::FRECUENCIAS as $f): ?>
                <option value="<?= $f ?>" <?= $f === 30 ? 'selected' : '' ?>><?= $f ?> días</option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn--outline btn--sm">Programar como recurrente</button>
</form>

<h2 class="portal-subtitle">Productos solicitados</h2>
<div class="table-wrap mb-8">
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

<?php if (!empty($pedido['notas'])): ?>
    <h2 class="portal-subtitle">Notas</h2>
    <p class="measure mb-8"><?= nl2br(e($pedido['notas'])) ?></p>
<?php endif; ?>

<?php
/** @var array $envios @var string|null $encuestaEntregaError @var array $facturasPorEnvio */
$envios = $envios ?? [];
$facturasPorEnvio = $facturasPorEnvio ?? [];
$eventoLabel = ['en_ruta' => 'En camino', 'entregado' => 'Entregado'];
$statsMap = ['en_ruta' => 'en_proceso', 'entregado' => 'sincronizado'];
?>
<?php if ($envios): ?>
    <h2 class="portal-subtitle">Entregas de este pedido</h2>
    <?php foreach ($envios as $en): ?>
        <div class="survey-card" id="envio-<?= (int) $en['id'] ?>">
            <div class="flex flex-wrap gap-4 items-center mb-4">
                <span class="text-muted fs-sm">Envío del <?= e(date('d/m/Y', strtotime($en['created_at']))) ?> · <?= (int) $en['num_partidas'] ?> producto(s)</span>
                <span class="status status--<?= e($statsMap[$en['evento'] ?? ''] ?? 'borrador') ?>"><?= e($eventoLabel[$en['evento'] ?? ''] ?? 'Pendiente de exportar') ?></span>
            </div>

            <?php if (!empty($facturasPorEnvio[$en['id']])): ?>
                <div class="survey-done__row mb-4">
                    Factura:
                    <?php foreach ($facturasPorEnvio[$en['id']] as $f): ?>
                        <a class="btn btn--outline btn--sm ml-2" href="<?= url('/portal/pedidos/' . (int) $pedido['id'] . '/facturas/' . (int) $f['id']) ?>?tipo=pdf">PDF</a>
                        <a class="btn btn--outline btn--sm" href="<?= url('/portal/pedidos/' . (int) $pedido['id'] . '/facturas/' . (int) $f['id']) ?>?tipo=xml">XML</a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($en['evento'] !== 'entregado'): ?>
                <p class="text-muted fs-sm">Te avisaremos por correo cuando este envío salga a ruta y cuando se entregue.</p>
            <?php elseif (!empty($en['encuesta_id'])): ?>
                <p class="text-muted">¡Gracias por calificar esta entrega!</p>
                <div class="survey-done__row">¿Llegó completo? <strong><?= (int) $en['encuesta_llego_completo'] === 1 ? 'Sí' : 'No' ?></strong></div>
                <?php if (!empty($en['encuesta_que_falto'])): ?>
                    <div class="survey-done__row">Qué faltó: <?= e($en['encuesta_que_falto']) ?></div>
                <?php endif; ?>
                <div class="survey-done__row">Satisfacción: <span class="stars-readonly"><?= $stars((int) $en['encuesta_satisfaccion']) ?></span></div>
                <?php if (!empty($en['encuesta_comentario'])): ?>
                    <blockquote class="survey-comment"><?= nl2br(e($en['encuesta_comentario'])) ?></blockquote>
                <?php endif; ?>
            <?php else: ?>
                <h3 class="fs-lg fw-semibold mb-2">¿Cómo te fue con esta entrega?</h3>
                <?php if (!empty($encuestaEntregaError)): ?>
                    <div class="alert alert--error"><?= e($encuestaEntregaError) ?></div>
                <?php endif; ?>
                <form method="post" action="<?= url('/portal/pedidos/' . (int) $pedido['id'] . '/envios/' . (int) $en['id'] . '/encuesta') ?>">
                    <?= csrf_field() ?>

                    <div class="survey-field">
                        <label>¿Te llegó completo? *</label>
                        <div class="nps-scale">
                            <input type="radio" id="completo-<?= (int) $en['id'] ?>-si" name="llego_completo" value="1" required>
                            <label for="completo-<?= (int) $en['id'] ?>-si">Sí</label>
                            <input type="radio" id="completo-<?= (int) $en['id'] ?>-no" name="llego_completo" value="0">
                            <label for="completo-<?= (int) $en['id'] ?>-no">No</label>
                        </div>
                    </div>

                    <div class="survey-field">
                        <label for="falto-<?= (int) $en['id'] ?>">¿Qué faltó? <span class="text-muted fs-sm">(si aplica)</span></label>
                        <input type="text" id="falto-<?= (int) $en['id'] ?>" name="que_falto" maxlength="500">
                    </div>

                    <div class="survey-field">
                        <label>Satisfacción con la entrega *</label>
                        <div class="star-rating">
                            <?php for ($n = 5; $n >= 1; $n--): ?>
                                <input type="radio" id="sate-<?= (int) $en['id'] . $n ?>" name="satisfaccion" value="<?= $n ?>" required>
                                <label for="sate-<?= (int) $en['id'] . $n ?>" title="<?= $n ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="survey-field">
                        <label for="comentario-<?= (int) $en['id'] ?>">Comentarios</label>
                        <textarea id="comentario-<?= (int) $en['id'] ?>" name="comentario" rows="3" maxlength="1000"></textarea>
                    </div>

                    <button type="submit" class="btn btn--accent">Enviar mi opinión</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
/** @var array|null $encuesta @var bool $calificable @var string|null $encuestaError */
$encuesta = $encuesta ?? null;
$pid = (int) $pedido['id'];
?>

<?php if ($encuesta): ?>
    <div class="survey-card survey-card--done">
        <h2 class="portal-subtitle">Tu opinión sobre este pedido</h2>
        <p class="text-muted">¡Gracias por calificar tu experiencia!</p>
        <div class="survey-done__row">Satisfacción general: <span class="stars-readonly"><?= $stars((int) $encuesta['satisfaccion']) ?></span></div>
        <div class="survey-done__row">Facilidad del proceso: <span class="stars-readonly"><?= $stars((int) $encuesta['facilidad']) ?></span></div>
        <?php if ($encuesta['nps'] !== null): ?>
            <div class="survey-done__row">Recomendación: <strong><?= (int) $encuesta['nps'] ?>/10</strong></div>
        <?php endif; ?>
        <?php if (!empty($encuesta['comentario'])): ?>
            <blockquote class="survey-comment"><?= nl2br(e($encuesta['comentario'])) ?></blockquote>
        <?php endif; ?>
    </div>
<?php elseif ($calificable): ?>
    <div class="survey-card">
        <h2 class="portal-subtitle">¿Cómo fue tu experiencia?</h2>
        <p class="text-muted">Tu opinión sobre el proceso de este pedido nos ayuda a mejorar.</p>
        <?php if (!empty($encuestaError)): ?>
            <div class="alert alert--error"><?= e($encuestaError) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= url('/portal/pedidos/' . $pid . '/encuesta') ?>">
            <?= csrf_field() ?>

            <div class="survey-field">
                <label>Satisfacción general *</label>
                <div class="star-rating">
                    <?php for ($n = 5; $n >= 1; $n--): ?>
                        <input type="radio" id="sat<?= $n ?>" name="satisfaccion" value="<?= $n ?>" required>
                        <label for="sat<?= $n ?>" title="<?= $n ?>">★</label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="survey-field">
                <label>Facilidad del proceso *</label>
                <div class="star-rating">
                    <?php for ($n = 5; $n >= 1; $n--): ?>
                        <input type="radio" id="fac<?= $n ?>" name="facilidad" value="<?= $n ?>" required>
                        <label for="fac<?= $n ?>" title="<?= $n ?>">★</label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="survey-field">
                <label>¿Qué tan probable es que nos recomiendes? <span class="text-muted fs-sm">(0 a 10)</span></label>
                <div class="nps-scale">
                    <?php for ($n = 0; $n <= 10; $n++): ?>
                        <input type="radio" id="nps<?= $n ?>" name="nps" value="<?= $n ?>">
                        <label for="nps<?= $n ?>"><?= $n ?></label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="survey-field">
                <label for="comentario">¿Qué mejorarías del proceso?</label>
                <textarea id="comentario" name="comentario" rows="3" maxlength="1000"></textarea>
            </div>

            <button type="submit" class="btn btn--accent">Enviar mi opinión</button>
        </form>
    </div>
<?php endif; ?>

<p class="mt-8">
    <a class="btn btn--whatsapp" target="_blank" rel="noopener"
       href="<?= e(whatsapp_url('Hola, tengo una duda sobre mi pedido ' . $pedido['folio'])) ?>">
        ¿Dudas de este pedido? Escríbenos por WhatsApp
    </a>
</p>
