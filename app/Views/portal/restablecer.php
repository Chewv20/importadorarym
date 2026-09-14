<?php
/** @var string $token */
$err = flash('portal_error');
?>
<div class="auth-card">
    <img class="auth-card__logo" src="<?= asset('assets/img/logos/importadorarym.png') ?>" alt="Importadora RYM" width="235" height="92">
    <h1 class="auth-card__title">Nueva contraseña</h1>
    <p class="auth-card__sub">Elige una contraseña segura para tu cuenta.</p>

    <?php if ($err): ?><div class="alert alert--error mb-6"><?= e($err) ?></div><?php endif; ?>

    <form class="form" method="post" action="<?= url('/portal/restablecer') ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="field">
            <label for="password">Nueva contraseña</label>
            <input type="password" id="password" name="password" required minlength="8" autofocus>
        </div>
        <div class="field">
            <label for="password_confirm">Confirmar contraseña</label>
            <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
        </div>
        <p class="text-muted fs-sm">Mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo (ej. ! @ # $).</p>
        <button type="submit" class="btn btn--accent btn--lg btn--block">Guardar contraseña</button>
    </form>

    <p class="auth-card__foot"><a href="<?= url('/portal/login') ?>">← Volver a iniciar sesión</a></p>
</div>
