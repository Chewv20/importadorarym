<?php /** @var string $anfitrion */ ?>
<div class="kiosco-card" data-checador-ok>
    <div class="kiosco-card__body">
        <div class="kiosco-msg">
            <div class="kiosco-msg__icon kiosco-msg__icon--ok" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
            </div>
            <h1>¡Registro recibido!</h1>
            <p>Avisamos a <strong><?= e($anfitrion) ?></strong> que llegaste.<br>Por favor toma asiento; en un momento te atienden.</p>
            <a class="kiosco-btn" href="<?= url('/checador') ?>">Registrar otra visita</a>
        </div>
    </div>
</div>
