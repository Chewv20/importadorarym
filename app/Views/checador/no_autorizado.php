<?php /** @var string $motivo */ ?>
<div class="kiosco-card">
    <div class="kiosco-card__body">
        <div class="kiosco-msg">
            <div class="kiosco-msg__icon kiosco-msg__icon--warn" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
            </div>
            <h1>Dispositivo no autorizado</h1>
            <p><?= e($motivo) ?><br>Si crees que es un error, contacta al personal de recepción o sistemas.</p>
        </div>
    </div>
</div>
