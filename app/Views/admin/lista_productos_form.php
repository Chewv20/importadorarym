<?php
/** @var ?array $lista @var array $productos */
$l = $lista;
$productos = $productos ?? [];
$action = $l ? url('/admin/listas-productos/' . (int) $l['id']) : url('/admin/listas-productos');
?>
<div class="admin-head"><h1><?= $l ? 'Editar' : 'Nueva' ?> lista de productos</h1></div>

<form class="form form--admin" method="post" action="<?= $action ?>" novalidate>
    <?= csrf_field() ?>
    <div class="field">
        <label for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" value="<?= e($l['nombre'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="descripcion">Descripción</label>
        <input type="text" id="descripcion" name="descripcion" value="<?= e($l['descripcion'] ?? '') ?>">
    </div>
    <div class="checks">
        <label><input type="checkbox" name="activa" <?= ($l === null || !empty($l['activa'])) ? 'checked' : '' ?>> Activa</label>
    </div>
    <p class="text-muted fs-sm">Si la desactivas, sus clientes asignados vuelven a ver el catálogo completo mientras esté inactiva.</p>

    <div class="form-actions">
        <button type="submit" class="btn btn--accent">Guardar</button>
        <a class="btn btn--outline" href="<?= url('/admin/listas-productos') ?>">Cancelar</a>
    </div>
</form>

<?php if ($l): ?>
    <section class="card-panel mt-6">
        <h2 class="card-panel__title">Productos de esta lista (<?= count($productos) ?>)</h2>

        <?php if ($productos): ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Nombre</th><th>SKU</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($productos as $p): ?>
                        <tr>
                            <td><?= e($p['nombre']) ?></td>
                            <td><?= e($p['sku'] ?? '—') ?></td>
                            <td>
                                <form class="inline-form" method="post" action="<?= url('/admin/listas-productos/' . (int) $l['id'] . '/productos/' . (int) $p['id'] . '/eliminar') ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn--outline btn--sm" type="submit">Quitar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted fs-sm">Sin productos todavía. Mientras esté vacía y activa, sus clientes asignados no verán ningún producto.</p>
        <?php endif; ?>

        <form method="post" action="<?= url('/admin/listas-productos/' . (int) $l['id'] . '/productos') ?>" class="cot-additem mt-4" data-prodpicker>
            <?= csrf_field() ?>
            <div class="prodpicker">
                <input type="text" class="prodpicker__input" placeholder="Busca por nombre o SKU…" autocomplete="off" aria-label="Buscar producto">
                <input type="hidden" name="producto_id" value="">
                <div class="prodpicker__results" hidden></div>
            </div>
            <button type="submit" class="btn btn--primary btn--sm">Agregar</button>
        </form>
    </section>
<?php endif; ?>
