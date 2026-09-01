<?php
/** @var string|null $error @var string|null $exito @var array $old */
$breadcrumb = [
    ['label' => 'Inicio', 'url' => url('/')],
    ['label' => 'Bolsa de trabajo', 'url' => url('/bolsa-de-trabajo')],
    ['label' => 'Solicitud de empleo'],
];
$pageTitle = 'Solicitud de empleo';
$pageSubtitle = '¿No encontraste una vacante para ti? Cuéntanos de ti y te contactaremos si surge algo que coincida con tu perfil.';
require APP_PATH . '/Views/partials/page_hero.php';
?>

<section class="section">
    <div class="container container--narrow">
        <form class="form vacancy-form" method="post" action="<?= url('/bolsa-de-trabajo/solicitud') ?>" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <?= honeypot_field() ?>

            <?php if ($exito): ?>
                <div class="alert alert--ok"><?= e($exito) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert--error"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="form__row">
                <div class="field">
                    <label for="nombre">Nombre completo *</label>
                    <input type="text" id="nombre" name="nombre" value="<?= e($old['nombre'] ?? '') ?>" required>
                </div>
                <div class="field">
                    <label for="email">Correo *</label>
                    <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form__row">
                <div class="field">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" value="<?= e($old['telefono'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="area_interes">Puesto o área de tu interés *</label>
                    <input type="text" id="area_interes" name="area_interes" placeholder="Ej. Ventas, almacén, administración…" value="<?= e($old['area_interes'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form__row">
                <div class="field">
                    <label for="sueldo_deseado">Sueldo mensual deseado</label>
                    <input type="text" id="sueldo_deseado" name="sueldo_deseado" placeholder="Ej. $12,000 o a convenir" value="<?= e($old['sueldo_deseado'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="disponibilidad">Disponibilidad para iniciar</label>
                    <input type="text" id="disponibilidad" name="disponibilidad" placeholder="Ej. Inmediata, en 2 semanas…" value="<?= e($old['disponibilidad'] ?? '') ?>">
                </div>
            </div>
            <div class="field">
                <label for="escolaridad">Escolaridad</label>
                <input type="text" id="escolaridad" name="escolaridad" placeholder="Ej. Preparatoria concluida, Licenciatura en…" value="<?= e($old['escolaridad'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="mensaje">Cuéntanos tu experiencia laboral</label>
                <textarea id="mensaje" name="mensaje" rows="4"><?= e($old['mensaje'] ?? '') ?></textarea>
            </div>
            <div class="field">
                <label for="cv">Tu CV (PDF, máx. 5 MB) *</label>
                <input type="file" id="cv" name="cv" accept="application/pdf" required>
            </div>

            <?= captcha_field() ?>

            <button type="submit" class="btn btn--accent btn--block">Enviar solicitud</button>
        </form>
    </div>
</section>
