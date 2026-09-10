<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Mailer;
use App\Models\ChecadorDispositivo;
use App\Models\Anfitrion;
use App\Models\Visita;

/**
 * Kiosco de la libreta de visitas. Solo funciona en dispositivos autorizados
 * (cookie de dispositivo). Al registrar una visita, notifica al anfitrión.
 */
class ChecadorController extends Controller
{
    private const COOKIE = 'rym_checador';

    /** Arma la tablet con el enlace de activación de un solo uso y setea la cookie. */
    public function activar(string $token): void
    {
        $model = new ChecadorDispositivo();
        $disp  = $model->porActivacionToken(hash('sha256', $token));
        if (!$disp) {
            $this->noAutorizado('El enlace de activación no es válido o ya fue usado.');
            return;
        }

        $deviceToken = bin2hex(random_bytes(32));
        $model->activar((int) $disp['id'], hash('sha256', $deviceToken));
        $this->setDeviceCookie((int) $disp['id'], $deviceToken);

        $this->redirect('/checador');
    }

    /** Formulario de registro (o confirmación tras un envío). */
    public function index(): void
    {
        $disp = $this->dispositivoActual();
        if (!$disp) {
            $this->noAutorizado('Este dispositivo no está autorizado para el registro de visitas.');
            return;
        }
        (new ChecadorDispositivo())->marcarUso((int) $disp['id']);

        $ok = flash('checador_ok');
        if ($ok !== null) {
            $this->view('checador/confirmacion', [
                'title'     => 'Registro recibido',
                'anfitrion' => $ok,
            ], 'layouts/kiosco');
            return;
        }

        $old = $_SESSION['_old_checador'] ?? [];
        unset($_SESSION['_old_checador']);

        $oficinaId = isset($disp['oficina_id']) && $disp['oficina_id'] !== null ? (int) $disp['oficina_id'] : null;

        $this->view('checador/registro', [
            'title'       => 'Registro de visitas — Importadora RYM',
            'anfitriones' => (new Anfitrion())->activosPorOficina($oficinaId),
            'dispositivo' => $disp['nombre'],
            'error'       => flash('checador_error'),
            'old'         => $old,
        ], 'layouts/kiosco');
    }

    /** Guarda la visita y notifica al anfitrión. */
    public function store(): void
    {
        $disp = $this->dispositivoActual();
        if (!$disp) {
            $this->noAutorizado('Este dispositivo no está autorizado para el registro de visitas.');
            return;
        }

        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('checador_error', 'La sesión expiró. Intenta de nuevo.');
            $this->redirect('/checador');
        }
        // Bot: se finge éxito sin guardar ni notificar.
        if (honeypot_tripped()) {
            flash('checador_ok', 'tu anfitrión');
            $this->redirect('/checador');
        }
        if (!RateLimiter::attempt('checador:' . (int) $disp['id'], 40, 600)) {
            flash('checador_error', 'Se registraron muchas visitas seguidas. Espera un momento.');
            $this->redirect('/checador');
        }

        $nombre   = str_clean($_POST['nombre'] ?? '', 120);
        $empresa  = str_clean($_POST['empresa'] ?? '', 150);
        $telefono = str_clean($_POST['telefono'] ?? '', 30);
        $motivo   = str_clean($_POST['motivo'] ?? '', 255);
        $personas = min(99, max(1, (int) ($_POST['num_personas'] ?? 1)));
        $anfitrionId = (int) ($_POST['anfitrion_id'] ?? 0);
        $anfitrion   = $anfitrionId ? (new Anfitrion())->find($anfitrionId) : null;

        // El anfitrión debe existir, estar activo y pertenecer a la oficina del
        // dispositivo (defensa ante un POST manipulado que envíe otro id).
        $oficinaDisp = isset($disp['oficina_id']) && $disp['oficina_id'] !== null ? (int) $disp['oficina_id'] : null;
        $anfitrionValido = $anfitrion
            && (int) $anfitrion['activo'] === 1
            && ($oficinaDisp === null || (int) ($anfitrion['oficina_id'] ?? 0) === $oficinaDisp);

        $errores = [];
        if ($nombre === '' || mb_strlen($nombre) < 2)          $errores[] = 'Escribe tu nombre.';
        if (!$anfitrionValido)                                 $errores[] = 'Selecciona a quién vas a visitar.';
        if ($telefono !== '' && !telefono_valido($telefono))   $errores[] = 'El teléfono no es válido.';

        if ($errores) {
            $_SESSION['_old_checador'] = [
                'nombre' => $nombre, 'empresa' => $empresa, 'telefono' => $telefono,
                'motivo' => $motivo, 'num_personas' => $personas, 'anfitrion_id' => $anfitrionId,
            ];
            flash('checador_error', implode(' ', $errores));
            $this->redirect('/checador');
        }

        (new Visita())->crear([
            'nombre_visitante' => $nombre,
            'empresa'          => $empresa,
            'telefono'         => $telefono,
            'num_personas'     => $personas,
            'motivo'           => $motivo,
            'anfitrion_id'     => (int) $anfitrion['id'],
            'anfitrion_email'  => $anfitrion['email'],
            'dispositivo_id'   => (int) $disp['id'],
            'oficina_id'       => $disp['oficina_id'] ?? null,
            'ip'               => client_ip(),
        ]);

        $datos = [
            'anfitrion' => $anfitrion['nombre'], 'visitante' => $nombre, 'empresa' => $empresa,
            'motivo'    => $motivo, 'personas' => $personas, 'telefono' => $telefono,
            'hora'      => date('d/m/Y H:i'),
        ];
        Mailer::enviar($anfitrion['email'], 'Tienes una visita en recepción — ' . $nombre, 'visita_nueva', $datos);

        // Copia opcional a un buzón de recepción (config VISITAS_COPIA_EMAIL).
        $copia = (string) config('app.visitas.copia_email', '');
        if ($copia !== '') {
            Mailer::enviar($copia, 'Nueva visita registrada — ' . $nombre, 'visita_nueva', $datos);
        }

        // La confirmación pública muestra el área, no el nombre del anfitrión.
        $area = trim((string) ($anfitrion['area'] ?? ''));
        flash('checador_ok', $area !== '' ? $area : $anfitrion['nombre']);
        $this->redirect('/checador');
    }

    /* --------------------------------- Utilidades -------------------- */

    /** Dispositivo autorizado según la cookie, o null. */
    private function dispositivoActual(): ?array
    {
        $raw = (string) ($_COOKIE[self::COOKIE] ?? '');
        if (!str_contains($raw, ':')) {
            return null;
        }
        [$id, $token] = explode(':', $raw, 2);
        if (!ctype_digit($id) || $token === '') {
            return null;
        }
        $disp = (new ChecadorDispositivo())->porDeviceToken(hash('sha256', $token));
        return ($disp && (int) $disp['id'] === (int) $id) ? $disp : null;
    }

    private function setDeviceCookie(int $id, string $token): void
    {
        $path = (defined('BASE_PATH') && BASE_PATH !== '') ? BASE_PATH : '/';
        setcookie(self::COOKIE, $id . ':' . $token, [
            'expires'  => time() + 31536000, // 1 año
            'path'     => $path,
            'httponly' => true,
            'secure'   => is_https(),
            'samesite' => 'Lax',
        ]);
    }

    private function noAutorizado(string $motivo): void
    {
        http_response_code(403);
        $this->view('checador/no_autorizado', [
            'title'  => 'Dispositivo no autorizado',
            'motivo' => $motivo,
        ], 'layouts/kiosco');
    }
}
