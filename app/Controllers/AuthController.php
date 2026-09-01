<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\RateLimiter;
use App\Models\Usuario;

class AuthController extends Controller
{
    /* -------------------------------------------------- Login -------- */

    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::can('admin.acceder') ? '/admin' : '/portal');
        }
        $this->view('portal/login', [
            'title'  => 'Iniciar sesión — Portal RYM',
            'robots' => 'noindex, nofollow',
        ], 'layouts/portal_auth');
    }

    public function login(): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró. Vuelve a intentarlo.');
            $this->redirect('/portal/login');
        }

        // Bot detectado por honeypot: se trata como credenciales inválidas.
        if (honeypot_tripped()) {
            flash('portal_error', 'Correo o contraseña incorrectos.');
            $this->redirect('/portal/login');
        }

        // Límite de intentos por IP.
        $key = 'login:' . client_ip();
        if (!RateLimiter::attempt($key, 8, 300)) {
            flash('portal_error', 'Demasiados intentos. Espera unos minutos e inténtalo de nuevo.');
            $this->redirect('/portal/login');
        }

        $email = str_clean($_POST['email'] ?? '', 191);
        $pass  = (string) ($_POST['password'] ?? '');

        $user = Auth::attempt($email, $pass);
        if (!$user) {
            \App\Core\Audit::ingreso('login_fallido', null, $email, 'Correo o contraseña incorrectos');
            $_SESSION['_old'] = ['email' => $email];
            flash('portal_error', 'Correo o contraseña incorrectos.');
            $this->redirect('/portal/login');
        }

        if ((int) $user['activo'] !== 1) {
            \App\Core\Audit::ingreso('login_fallido', (int) $user['id'], $email, 'Cuenta desactivada');
            $_SESSION['_old'] = ['email' => $email];
            flash('portal_error', 'Tu cuenta está desactivada. Contáctanos para más información.');
            $this->redirect('/portal/login');
        }

        RateLimiter::clear($key);
        Auth::login($user);
        (new Usuario())->marcarLogin((int) $user['id']);
        \App\Core\Audit::ingreso('login', (int) $user['id'], (string) $user['email'], 'Inicio de sesión');

        // Redirección según permisos: staff al panel, clientes al portal.
        $this->redirect(Auth::can('admin.acceder') ? '/admin' : '/portal');
    }

    /* ---------------------------------------------- Registro --------- */

    public function showRegister(): void
    {
        if (Auth::check()) {
            $this->redirect('/portal');
        }
        $this->view('portal/registro', [
            'title'  => 'Crear cuenta — Portal RYM',
            'robots' => 'noindex, nofollow',
        ], 'layouts/portal_auth');
    }

    public function register(): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró. Vuelve a intentarlo.');
            $this->redirect('/portal/registro');
        }

        // Bot: se finge éxito sin crear nada.
        if (honeypot_tripped()) {
            flash('portal_ok', '¡Cuenta creada! Ya puedes ingresar y solicitar cotizaciones.');
            $this->redirect('/portal/login');
        }

        if (!RateLimiter::attempt('register:' . client_ip(), 5, 3600)) {
            flash('portal_error', 'Demasiadas solicitudes. Inténtalo más tarde.');
            $this->redirect('/portal/registro');
        }

        $data = [
            'nombre'   => str_clean($_POST['nombre'] ?? '', 120),
            'empresa'  => str_clean($_POST['empresa'] ?? '', 150),
            'email'    => str_clean($_POST['email'] ?? '', 191),
            'telefono' => str_clean($_POST['telefono'] ?? '', 30),
            'rfc'      => str_clean($_POST['rfc'] ?? '', 20),
            // Dirección (para logística/entrega): calle, número y CP son el mínimo útil;
            // número interior y referencias quedan opcionales.
            'calle'            => str_clean($_POST['calle'] ?? '', 150),
            'numero_ext'       => str_clean($_POST['numero_ext'] ?? '', 20),
            'numero_int'       => str_clean($_POST['numero_int'] ?? '', 20),
            'colonia'          => str_clean($_POST['colonia'] ?? '', 100),
            'codigo_postal'    => str_clean($_POST['codigo_postal'] ?? '', 5),
            'delegacion_municipio' => str_clean($_POST['delegacion_municipio'] ?? '', 100),
            'estado_direccion' => str_clean($_POST['estado_direccion'] ?? '', 100),
            'referencias'      => str_clean($_POST['referencias'] ?? '', 255),
        ];
        $pass    = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        $usuarioModel = new Usuario();
        $errores = [];

        if (!captcha_valido())                                   $errores[] = 'Resuelve correctamente la comprobación anti-bot.';
        if (!nombre_valido($data['nombre']))                     $errores[] = 'El nombre solo puede contener letras, espacios y . - \'.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))  $errores[] = 'Captura un correo válido.';
        elseif ($usuarioModel->emailExiste($data['email']))      $errores[] = 'Ese correo ya está registrado.';
        if ($data['empresa'] !== '' && !empresa_valida($data['empresa'])) $errores[] = 'La empresa contiene caracteres no permitidos.';
        if ($data['telefono'] !== '' && !telefono_valido($data['telefono'])) $errores[] = 'El teléfono solo admite dígitos y + - ( ).';
        if ($data['rfc'] !== '' && !rfc_valido($data['rfc']))    $errores[] = 'El RFC no tiene un formato válido.';
        if ($data['calle'] === '' || !direccion_valida($data['calle']))       $errores[] = 'Captura una calle válida.';
        if ($data['numero_ext'] === '')                          $errores[] = 'Captura el número exterior.';
        if ($data['colonia'] === '' || !direccion_valida($data['colonia']))   $errores[] = 'Captura una colonia válida.';
        if (!codigo_postal_valido($data['codigo_postal']))       $errores[] = 'El código postal debe tener 5 dígitos.';
        if ($data['delegacion_municipio'] === '' || !direccion_valida($data['delegacion_municipio'])) $errores[] = 'Captura una delegación o municipio válido.';
        if ($data['estado_direccion'] === '' || !direccion_valida($data['estado_direccion'])) $errores[] = 'Captura un estado válido.';
        $errores = array_merge($errores, password_errores($pass));
        if ($pass !== $confirm)                                  $errores[] = 'Las contraseñas no coinciden.';

        if ($errores) {
            $_SESSION['_old'] = $data;
            flash('portal_error', implode(' ', $errores));
            $this->redirect('/portal/registro');
        }

        // Token de verificación de correo (se guarda el hash; se envía el token en claro).
        $token = bin2hex(random_bytes(32));

        $usuarioModel->crear([
            'nombre'   => $data['nombre'],
            'email'    => $data['email'],
            'password' => $pass,
            'rol_slug' => 'cliente',
            'empresa'  => $data['empresa'] ?: null,
            'telefono' => $data['telefono'] ?: null,
            'rfc'      => $data['rfc'] ?: null,
            'calle'            => $data['calle'],
            'numero_ext'       => $data['numero_ext'],
            'numero_int'       => $data['numero_int'] ?: null,
            'colonia'          => $data['colonia'],
            'codigo_postal'    => $data['codigo_postal'],
            'delegacion_municipio' => $data['delegacion_municipio'],
            'estado_direccion' => $data['estado_direccion'],
            'referencias'      => $data['referencias'] ?: null,
            'verificacion_token' => hash('sha256', $token),
        ]);

        \App\Core\Mailer::enviar($data['email'], 'Verifica tu correo — Importadora RYM', 'verificar_correo', [
            'nombre'      => $data['nombre'],
            'verificaUrl' => rtrim((string) config('app.url'), '/') . '/portal/verificar/' . $token,
        ]);

        flash('portal_ok', '¡Cuenta creada! Te enviamos un correo para verificar tu cuenta. Ya puedes ingresar y solicitar cotizaciones.');
        $this->redirect('/portal/login');
    }

    /* ------------------------------------------------ Logout --------- */

    public function logout(): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            $this->redirect('/portal');
        }
        if ($u = Auth::user()) {
            \App\Core\Audit::ingreso('logout', (int) $u['id'], (string) $u['email'], 'Cierre de sesión');
        }
        Auth::logout();
        $this->redirect('/');
    }
}
