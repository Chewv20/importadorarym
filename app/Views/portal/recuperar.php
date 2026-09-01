<?php
$ok  = flash('portal_ok');
$err = flash('portal_error');
?>
<div class="auth-card">
    <img class="auth-card__logo" src="<?= asset('assets/img/logos/importadorarym.jpg') ?>" alt="Importadora RYM" width="284" height="92">
    <h1 class="auth-card__title">Recuperar contraseña</h1>
    <p class="auth-card__sub">Escribe tu correo y te enviaremos un enlace para crear una nueva contraseña.</p>

    <?php if ($ok): ?><div class="alert alert--ok mb-6"><?= e($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert--error mb-6"><?= e($err) ?></div><?php endif; ?>

    <form class="form" method="post" action="<?= url('/portal/recuperar') ?>" novalidate>
        <?= csrf_field() ?>
        <?= honeypot_field() ?>
        <div class="field">
            <label for="email">Correo</label>
            <input type="email" id="email" name="email" required autofocus>
        </div>
        <button type="submit" class="btn btn--accent btn--lg btn--block">Enviar enlace</button>
    </form>

    <p class="auth-card__foot">¿La recordaste? <a href="<?= url('/portal/login') ?>">Inicia sesión</a></p>
</div>
