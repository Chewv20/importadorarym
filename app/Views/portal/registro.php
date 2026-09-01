<?php
$err = flash('portal_error');
$old = $_SESSION['_old'] ?? [];
unset($_SESSION['_old']);
?>
<div class="auth-card auth-card--wide">
    <img class="auth-card__logo" src="<?= asset('assets/img/logos/importadorarym.jpg') ?>" alt="Importadora RYM" width="284" height="92">
    <h1 class="auth-card__title">Crear cuenta</h1>
    <p class="auth-card__sub">Regístrate para pedir en línea. Tu cuenta se activará tras la aprobación de un asesor.</p>

    <?php if ($err): ?><div class="alert alert--error mb-6"><?= e($err) ?></div><?php endif; ?>

    <form class="form" method="post" action="<?= url('/portal/registro') ?>" novalidate data-cp-form>
        <?= csrf_field() ?>
        <?= honeypot_field() ?>

        <div class="form-section">
            <p class="form-section__title">Datos de contacto</p>
            <div class="field">
                <label for="nombre">Nombre completo *</label>
                <input type="text" id="nombre" name="nombre" value="<?= e($old['nombre'] ?? '') ?>" required autofocus>
            </div>
            <div class="field">
                <label for="empresa">Empresa</label>
                <input type="text" id="empresa" name="empresa" value="<?= e($old['empresa'] ?? '') ?>">
            </div>
            <div class="form__row">
                <div class="field">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" value="<?= e($old['telefono'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="rfc">RFC</label>
                    <input type="text" id="rfc" name="rfc" value="<?= e($old['rfc'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <p class="form-section__title">Dirección de entrega</p>
            <div class="form__row">
                <div class="field">
                    <label for="calle">Calle *</label>
                    <input type="text" id="calle" name="calle" value="<?= e($old['calle'] ?? '') ?>" required>
                </div>
                <div class="field">
                    <label for="numero_ext">Número *</label>
                    <input type="text" id="numero_ext" name="numero_ext" value="<?= e($old['numero_ext'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form__row">
                <div class="field">
                    <label for="numero_int">Número interior</label>
                    <input type="text" id="numero_int" name="numero_int" value="<?= e($old['numero_int'] ?? '') ?>">
                </div>
                <div class="field colonia-picker" data-colonia-picker>
                    <label for="colonia">Colonia *</label>
                    <input type="text" id="colonia" name="colonia" value="<?= e($old['colonia'] ?? '') ?>" autocomplete="off" required>
                    <div class="colonia-picker__resultados" data-colonia-resultados hidden></div>
                </div>
            </div>
            <div class="form__row">
                <div class="field">
                    <label for="codigo_postal">Código postal *</label>
                    <input type="text" id="codigo_postal" name="codigo_postal" value="<?= e($old['codigo_postal'] ?? '') ?>" inputmode="numeric" pattern="\d{5}" maxlength="5" required>
                </div>
                <div class="field">
                    <label for="delegacion_municipio">Delegación o Municipio *</label>
                    <input type="text" id="delegacion_municipio" name="delegacion_municipio" value="<?= e($old['delegacion_municipio'] ?? '') ?>" required>
                </div>
            </div>
            <div class="field">
                <label for="estado_direccion">Estado *</label>
                <input type="text" id="estado_direccion" name="estado_direccion" value="<?= e($old['estado_direccion'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label for="referencias">Referencias (opcional)</label>
                <input type="text" id="referencias" name="referencias" value="<?= e($old['referencias'] ?? '') ?>" placeholder="Entre calles, color de fachada, etc.">
            </div>
            <div data-mapa-preview>
                <button type="button" class="btn btn--outline btn--sm" data-mapa-btn>Mostrar mapa para verificar</button>
                <p class="alert alert--error mt-2" data-mapa-error hidden></p>
                <div class="mt-4" data-mapa-contenedor hidden></div>
            </div>
        </div>

        <div class="form-section">
            <p class="form-section__title">Acceso a tu cuenta</p>
            <div class="field">
                <label for="email">Correo *</label>
                <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" required>
            </div>
            <div class="form__row">
                <div class="field">
                    <label for="password">Contraseña *</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                <div class="field">
                    <label for="password_confirm">Confirmar *</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
                </div>
            </div>
            <p class="text-muted fs-sm">Mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo (ej. ! @ # $).</p>
            <?= captcha_field() ?>
            <button type="submit" class="btn btn--accent btn--lg btn--block">Crear cuenta</button>
        </div>
    </form>

    <p class="auth-card__foot">¿Ya tienes cuenta? <a href="<?= url('/portal/login') ?>">Inicia sesión</a></p>
</div>
