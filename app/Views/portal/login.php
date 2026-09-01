<?php
$ok  = flash('portal_ok');
$err = flash('portal_error');
$old = $_SESSION['_old'] ?? [];
unset($_SESSION['_old']);
?>
<div class="auth-card">
    <img class="auth-card__logo" src="<?= asset('assets/img/logos/importadorarym.jpg') ?>" alt="Importadora RYM" width="284" height="92">
    <h1 class="auth-card__title">Portal de clientes</h1>
    <p class="auth-card__sub">Ingresa para levantar y consultar tus pedidos.</p>

    <?php if ($ok): ?><div class="alert alert--ok mb-6"><?= e($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert--error mb-6"><?= e($err) ?></div><?php endif; ?>

    <form class="form" method="post" action="<?= url('/portal/login') ?>" novalidate>
        <?= csrf_field() ?>
        <?= honeypot_field() ?>
        <div class="field">
            <label for="email">Correo</label>
            <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" required autofocus>
        </div>
        <div class="field">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn--accent btn--lg btn--block">Entrar</button>
    </form>

    <p class="auth-card__foot"><a href="<?= url('/portal/recuperar') ?>">¿Olvidaste tu contraseña?</a></p>
    <p class="auth-card__foot">¿No tienes cuenta? <a href="<?= url('/portal/registro') ?>">Créala aquí</a></p>
    <p class="auth-card__foot"><a href="<?= url('/') ?>">← Volver al sitio</a></p>
</div>
