<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Models\Usuario;
use App\Models\PasswordReset;

/**
 * Restablecimiento de contraseña ("olvidé mi contraseña").
 * Respuestas neutras: nunca revela si un correo existe. Token de un solo uso,
 * hash en BD y caducidad de 60 min.
 */
class PasswordResetController extends Controller
{
    private const VIGENCIA_MIN = 60;
    private const NEUTRAL = 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña. Revisa tu bandeja de entrada.';

    /* ------------------------------------------- Solicitud ----------- */

    public function showRequest(): void
    {
        if (Auth::check()) {
            $this->redirect('/portal');
        }
        $this->view('portal/recuperar', [
            'title'  => 'Recuperar contraseña — Portal RYM',
            'robots' => 'noindex, nofollow',
        ], 'layouts/portal_auth');
    }

    public function request(): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró. Vuelve a intentarlo.');
            $this->redirect('/portal/recuperar');
        }

        // Bot: se finge éxito neutro.
        if (honeypot_tripped()) {
            flash('portal_ok', self::NEUTRAL);
            $this->redirect('/portal/login');
        }

        $email = str_clean($_POST['email'] ?? '', 191);

        // Límite por IP y por correo (no rompe la neutralidad de la respuesta).
        $ipOk   = RateLimiter::attempt('pwreset-ip:' . client_ip(), 5, 3600);
        $mailOk = RateLimiter::attempt('pwreset-mail:' . strtolower($email), 3, 3600);

        if ($ipOk && $mailOk && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = (new Usuario())->porEmail($email);
            if ($user) {
                $token = bin2hex(random_bytes(32));
                (new PasswordReset())->crear($email, hash('sha256', $token), self::VIGENCIA_MIN);

                Mailer::enviar($email, 'Restablece tu contraseña — Importadora RYM', 'restablecer_password', [
                    'nombre'        => $user['nombre'] ?? '',
                    'restablecerUrl' => rtrim((string) config('app.url'), '/') . '/portal/restablecer/' . $token,
                    'minutos'       => self::VIGENCIA_MIN,
                ]);
            }
        }

        flash('portal_ok', self::NEUTRAL);
        $this->redirect('/portal/login');
    }

    /* ------------------------------------------- Restablecer --------- */

    public function showReset(string $token): void
    {
        $email = (new PasswordReset())->emailPorToken(hash('sha256', $token));
        if ($email === null) {
            flash('portal_error', 'El enlace para restablecer no es válido o ya expiró. Solicítalo de nuevo.');
            $this->redirect('/portal/recuperar');
        }

        $this->view('portal/restablecer', [
            'title'  => 'Nueva contraseña — Portal RYM',
            'robots' => 'noindex, nofollow',
            'token'  => $token,
        ], 'layouts/portal_auth');
    }

    public function reset(): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró. Vuelve a intentarlo.');
            $this->redirect('/portal/recuperar');
        }

        $token   = (string) ($_POST['token'] ?? '');
        $pass    = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        $resetModel = new PasswordReset();
        $email = $resetModel->emailPorToken(hash('sha256', $token));
        if ($email === null) {
            flash('portal_error', 'El enlace para restablecer no es válido o ya expiró. Solicítalo de nuevo.');
            $this->redirect('/portal/recuperar');
        }

        $errores = password_errores($pass);
        if ($pass !== $confirm) {
            $errores[] = 'Las contraseñas no coinciden.';
        }
        if ($errores) {
            flash('portal_error', implode(' ', $errores));
            $this->redirect('/portal/restablecer/' . $token);
        }

        $user = (new Usuario())->porEmail($email);
        if (!$user) {
            // El usuario desapareció: invalida el token y manda a solicitar de nuevo.
            $resetModel->eliminarDe($email);
            flash('portal_error', 'No pudimos completar la operación. Solicita el enlace de nuevo.');
            $this->redirect('/portal/recuperar');
        }

        (new Usuario())->cambiarPassword((int) $user['id'], $pass);
        $resetModel->eliminarDe($email);

        flash('portal_ok', 'Tu contraseña se actualizó. Ya puedes iniciar sesión.');
        $this->redirect('/portal/login');
    }
}
