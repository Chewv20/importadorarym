<?php /** @var ?array $cfg */ ?>
<div class="admin-head"><h1>Correo saliente (Office 365)</h1></div>

<p class="text-muted fs-sm mb-6">
    Configura el envío de correo transaccional vía Microsoft Graph (Office 365),
    con autenticación de aplicación (sin usuario interactivo). Requiere un App
    Registration en Azure AD / Entra ID con el permiso de aplicación
    <code>Mail.Send</code> ya consentido por un administrador de Microsoft 365.
</p>

<form class="form form--admin" method="post" action="<?= url('/admin/configuracion-correo') ?>" novalidate>
    <?= csrf_field() ?>

    <div class="field">
        <label for="tenant_id">Tenant ID *</label>
        <input type="text" id="tenant_id" name="tenant_id" value="<?= e($cfg['tenant_id'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="client_id">Client ID *</label>
        <input type="text" id="client_id" name="client_id" value="<?= e($cfg['client_id'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="client_secret">Client Secret <?= $cfg ? '(dejar vacío para conservar el actual)' : '*' ?></label>
        <input type="password" id="client_secret" name="client_secret" autocomplete="off" <?= $cfg ? '' : 'required' ?>>
        <?php if ($cfg): ?>
            <p class="text-muted fs-sm">Ya hay un secreto guardado (configurado el <?= e(date('d/m/Y H:i', strtotime($cfg['actualizado_en']))) ?>).</p>
        <?php endif; ?>
    </div>
    <div class="field">
        <label for="mailbox">Buzón remitente *</label>
        <input type="email" id="mailbox" name="mailbox" value="<?= e($cfg['mailbox'] ?? '') ?>" required>
        <p class="text-muted fs-sm">Cuenta real y licenciada de Exchange Online (ej. no-responder@importadorarym.com).</p>
    </div>
    <div class="field">
        <label for="remitente_nombre">Nombre del remitente</label>
        <input type="text" id="remitente_nombre" name="remitente_nombre" value="<?= e($cfg['remitente_nombre'] ?? 'Importadora RYM') ?>">
    </div>

    <div class="checks">
        <label class="switch">
            <input type="checkbox" name="activo" <?= !empty($cfg['activo']) ? 'checked' : '' ?>>
            <span class="switch__track"></span>
        </label>
        <span>Usar Office 365 para el correo saliente</span>
    </div>
    <p class="text-muted fs-sm">Si se desactiva, la app vuelve a enviar por la configuración SMTP del .env.</p>

    <div class="form-actions">
        <button type="submit" class="btn btn--accent">Guardar</button>
        <button type="button" id="btnProbarCorreo" class="btn btn--outline" <?= $cfg ? '' : 'disabled' ?>>Enviar correo de prueba</button>
    </div>
    <p id="pruebaCorreoResultado" class="fs-sm mt-4" hidden></p>
</form>

<script nonce="<?= e(csp_nonce()) ?>">
(function () {
    var btn = document.getElementById('btnProbarCorreo');
    var out = document.getElementById('pruebaCorreoResultado');
    if (!btn) return;

    btn.addEventListener('click', function () {
        btn.disabled = true;
        out.hidden = true;
        out.className = 'fs-sm mt-4';

        fetch('<?= url('/admin/configuracion-correo/probar') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_csrf=' + encodeURIComponent('<?= e(csrf_token()) ?>')
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                out.textContent = data.mensaje;
                out.className = 'fs-sm mt-4 ' + (data.ok ? 'txt-grant' : 'txt-deny');
                out.hidden = false;
            })
            .catch(function () {
                out.textContent = 'No se pudo contactar al servidor.';
                out.className = 'fs-sm mt-4 txt-deny';
                out.hidden = false;
            })
            .finally(function () { btn.disabled = false; });
    });
})();
</script>
