<?php

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Crypto;
use App\Core\GraphMailer;
use App\Core\Mailer;
use App\Models\ConfiguracionCorreo;

class ConfiguracionCorreoController extends BaseController
{
    public function edit(): void
    {
        Auth::authorize('configuracion.correo');

        $cfg = (new ConfiguracionCorreo())->ultima();
        unset($cfg['client_secret_cifrado'], $cfg['token_cache']); // nunca a la vista

        $this->render('admin/configuracion_correo', [
            'title'  => 'Correo saliente (Office 365) — Panel RYM',
            'active' => 'configuracion_correo',
            'cfg'    => $cfg,
        ]);
    }

    public function update(): void
    {
        Auth::authorize('configuracion.correo');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/configuracion-correo');
        }

        $tenantId = str_clean($_POST['tenant_id'] ?? '', 100);
        $clientId = str_clean($_POST['client_id'] ?? '', 100);
        $secret   = (string) ($_POST['client_secret'] ?? '');
        $mailbox  = str_clean($_POST['mailbox'] ?? '', 190);
        $nombre   = str_clean($_POST['remitente_nombre'] ?? '', 120) ?: 'Importadora RYM';
        $activo   = !empty($_POST['activo']);

        $errores = [];
        if ($tenantId === '') $errores[] = 'Captura el Tenant ID.';
        if ($clientId === '') $errores[] = 'Captura el Client ID.';
        if ($mailbox === '' || !filter_var($mailbox, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Captura un buzón (mailbox) remitente válido.';
        }

        $model  = new ConfiguracionCorreo();
        $actual = $model->ultima();

        // El client_secret se deja vacío en el formulario para no mostrarlo:
        // si viene vacío, se conserva el ya guardado (igual que la contraseña
        // opcional en UsuarioController::update).
        if ($secret !== '') {
            $secretCifrado = Crypto::encrypt($secret);
        } elseif ($actual) {
            $secretCifrado = $actual['client_secret_cifrado'];
        } else {
            $errores[] = 'Captura el Client Secret.';
            $secretCifrado = '';
        }

        if ($errores) {
            flash('portal_error', implode(' ', $errores));
            $this->redirect('/admin/configuracion-correo');
        }

        $model->guardar([
            'tenant_id'             => $tenantId,
            'client_id'             => $clientId,
            'client_secret_cifrado' => $secretCifrado,
            'mailbox'               => $mailbox,
            'remitente_nombre'      => $nombre,
            'activo'                => $activo,
        ], (int) $this->usuario['id']);

        Audit::cambio('actualizar', 'configuracion_correo', $actual['id'] ?? null,
            'Actualizó la configuración de correo O365 (mailbox: ' . $mailbox . ', activo: ' . ($activo ? 'sí' : 'no') . ')');

        flash('portal_ok', 'Configuración de correo guardada.');
        $this->redirect('/admin/configuracion-correo');
    }

    /** AJAX: botón "Enviar correo de prueba". */
    public function probar(): void
    {
        Auth::authorize('configuracion.correo');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'mensaje' => 'La sesión expiró, recarga la página.'], 419);
        }

        if (GraphMailer::configActiva() === null) {
            $this->json(['ok' => false, 'mensaje' => 'Guarda y activa la configuración antes de probar.']);
        }

        $destino = (string) $this->usuario['email'];

        try {
            $ok = Mailer::enviar($destino, 'Correo de prueba — Importadora RYM', 'prueba_o365', [
                'usuarioNombre' => $this->usuario['nombre'],
            ]);
            $this->json(['ok' => $ok, 'mensaje' => $ok
                ? 'Correo de prueba enviado a ' . $destino . '.'
                : 'No se pudo enviar. Revisa storage/logs/mail.log para el detalle.']);
        } catch (\Throwable $e) {
            error_log('Prueba correo O365: ' . $e->getMessage());
            $this->json(['ok' => false, 'mensaje' => 'Ocurrió un error al enviar. Revisa storage/logs/mail.log.'], 500);
        }
    }
}
