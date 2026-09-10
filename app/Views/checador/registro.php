<?php
/**
 * @var array $anfitriones
 * @var string $dispositivo
 * @var string|null $error
 * @var array $old
 */
?>
<div class="kiosco-card">
    <div class="kiosco-card__head">
        <img src="<?= asset('assets/img/logos/importadorarym.jpg') ?>" alt="Importadora RYM" width="284" height="92">
        <h1 class="kiosco-card__title">Registro de visitas</h1>
        <p class="kiosco-card__sub">Anota tus datos y avisaremos a quien vienes a ver.</p>
    </div>
    <div class="kiosco-card__body">
        <?php if ($error): ?>
            <div class="kiosco-alert"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (!$anfitriones): ?>
            <div class="kiosco-alert">Aún no hay anfitriones registrados. Avisa al personal de recepción.</div>
        <?php else: ?>
        <form method="post" action="<?= url('/checador') ?>" autocomplete="off">
            <?= csrf_field() ?>
            <?= honeypot_field() ?>

            <div class="kiosco-field">
                <label for="nombre">Tu nombre <span class="req">*</span></label>
                <input type="text" id="nombre" name="nombre" value="<?= e($old['nombre'] ?? '') ?>" required>
            </div>

            <div class="kiosco-field">
                <label for="anfitrion_id">¿Qué área vas a visitar? <span class="req">*</span></label>
                <select id="anfitrion_id" name="anfitrion_id" required>
                    <option value="">Selecciona…</option>
                    <?php foreach ($anfitriones as $a): ?>
                        <option value="<?= (int) $a['id'] ?>" <?= (int) ($old['anfitrion_id'] ?? 0) === (int) $a['id'] ? 'selected' : '' ?>>
                            <?= e(($a['area'] ?? '') !== '' ? $a['area'] : $a['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="kiosco-row">
                <div class="kiosco-field">
                    <label for="empresa">Empresa</label>
                    <input type="text" id="empresa" name="empresa" value="<?= e($old['empresa'] ?? '') ?>">
                </div>
                <div class="kiosco-field">
                    <label for="telefono">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" inputmode="tel" value="<?= e($old['telefono'] ?? '') ?>">
                </div>
            </div>

            <div class="kiosco-row">
                <div class="kiosco-field">
                    <label for="motivo">Motivo de la visita</label>
                    <input type="text" id="motivo" name="motivo" value="<?= e($old['motivo'] ?? '') ?>">
                </div>
                <div class="kiosco-field">
                    <label for="num_personas">N.º de personas</label>
                    <input type="number" id="num_personas" name="num_personas" min="1" max="99" value="<?= (int) ($old['num_personas'] ?? 1) ?>">
                </div>
            </div>

            <button class="kiosco-btn" type="submit">Registrar mi visita</button>
        </form>
        <?php endif; ?>

        <p class="kiosco-note">Punto de registro: <?= e($dispositivo) ?></p>
    </div>
</div>
