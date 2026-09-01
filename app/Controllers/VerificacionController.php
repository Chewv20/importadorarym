<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Models\Usuario;

/**
 * Verificación de correo del cliente. El enlace es público (el usuario puede
 * no tener sesión al hacer clic); el reenvío requiere sesión.
 */
class VerificacionController extends Controller
{
    public function verificar(string $token): void
    {
        $model = new Usuario();
        $user  = $model->porTokenVerificacion(hash('sha256', $token));

        if (!$user) {
            flash('portal_error', 'El enlace de verificación no es válido, ya fue usado o caducó ('
                . Usuario::VERIFICACION_HORAS . ' h). Ingresa y pide que te lo reenviemos.');
        } else {
            $model->marcarVerificado((int) $user['id']);
            flash('portal_ok', '¡Correo verificado! Ya puedes ingresar. Un asesor podrá activar tu cuenta para levantar pedidos.');
        }

        $this->redirect(Auth::check() ? '/portal' : '/portal/login');
    }

    public function reenviar(): void
    {
        if (!Auth::check()) {
            $this->redirect('/portal/login');
        }
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró. Vuelve a intentarlo.');
            $this->redirect('/portal');
        }

        $user = Auth::user();
        if (!$user) {
            $this->redirect('/portal/login');
        }

        if (!empty($user['email_verificado_en'])) {
            flash('portal_ok', 'Tu correo ya está verificado.');
            $this->redirect('/portal');
        }

        if (!RateLimiter::attempt('verificar-reenviar:' . $user['id'], 3, 900)) {
            flash('portal_error', 'Ya enviamos el correo hace poco. Revisa tu bandeja o espera unos minutos.');
            $this->redirect('/portal');
        }

        $token = bin2hex(random_bytes(32));
        (new Usuario())->setTokenVerificacion((int) $user['id'], hash('sha256', $token));

        Mailer::enviar($user['email'], 'Verifica tu correo — Importadora RYM', 'verificar_correo', [
            'nombre'      => $user['nombre'],
            'verificaUrl' => rtrim((string) config('app.url'), '/') . '/portal/verificar/' . $token,
        ]);

        flash('portal_ok', 'Te reenviamos el correo de verificación a ' . $user['email'] . '.');
        $this->redirect('/portal');
    }
}
