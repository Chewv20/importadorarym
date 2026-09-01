<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\RateLimiter;
use App\Core\Mailer;
use App\Core\Upload;
use App\Models\Vacante;
use App\Models\Postulacion;

/**
 * Bolsa de trabajo (sitio público): lista de vacantes y postulación con CV.
 */
class CarreraController extends Controller
{
    public function index(): void
    {
        $this->view('pages/bolsa', [
            'title'           => 'Bolsa de trabajo — Importadora RYM',
            'metaDescription' => 'Únete al equipo de Importadora RYM. Consulta nuestras vacantes y postúlate en línea.',
            'pageTitle'       => 'Bolsa de trabajo',
            'pageSubtitle'    => 'Crece con nosotros. Estas son nuestras vacantes disponibles.',
            'vacantes'        => (new Vacante())->abiertas(),
            'tipos'           => Vacante::TIPOS,
        ]);
    }

    public function vacante(string $slug): void
    {
        $vac = (new Vacante())->abiertaPorSlug($slug);
        if (!$vac) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Vacante no encontrada', 'robots' => 'noindex']);
            return;
        }

        $old = $_SESSION['_old_bolsa'] ?? [];
        unset($_SESSION['_old_bolsa']);

        $this->view('pages/vacante', [
            'title'           => $vac['titulo'] . ' — Bolsa de trabajo · Importadora RYM',
            'metaDescription' => 'Vacante: ' . $vac['titulo'] . '. Postúlate en línea con tu CV.',
            'vacante'         => $vac,
            'tipos'           => Vacante::TIPOS,
            'error'           => flash('bolsa_error'),
            'exito'           => flash('bolsa_ok'),
            'old'             => $old,
        ]);
    }

    /** Solicitud de empleo general, para cuando ninguna vacante publicada le interesa al candidato. */
    public function solicitud(): void
    {
        $old = $_SESSION['_old_bolsa'] ?? [];
        unset($_SESSION['_old_bolsa']);

        $this->view('pages/solicitud_general', [
            'title'           => 'Solicitud de empleo — Importadora RYM',
            'metaDescription' => 'Envíanos tu CV aunque no haya una vacante publicada que coincida con tu perfil.',
            'error'           => flash('bolsa_error'),
            'exito'           => flash('bolsa_ok'),
            'old'             => $old,
        ]);
    }

    public function enviarSolicitud(): void
    {
        $back = '/bolsa-de-trabajo/solicitud';
        $this->guardPostulacion($back, 'postular-general:' . client_ip(), '¡Gracias! Recibimos tu solicitud.');

        $nombre         = str_clean($_POST['nombre'] ?? '', 120);
        $email          = str_clean($_POST['email'] ?? '', 191);
        $telefono       = str_clean($_POST['telefono'] ?? '', 30);
        $areaInteres    = str_clean($_POST['area_interes'] ?? '', 150);
        $sueldoDeseado  = str_clean($_POST['sueldo_deseado'] ?? '', 60);
        $disponibilidad = str_clean($_POST['disponibilidad'] ?? '', 100);
        $escolaridad    = str_clean($_POST['escolaridad'] ?? '', 150);
        $mensaje        = str_clean($_POST['mensaje'] ?? '', 2000);

        $errores = $this->erroresComunes($nombre, $email, $telefono);
        if ($areaInteres === '') $errores[] = 'Cuéntanos qué puesto o área te interesa.';
        $cv = $this->subirCv($errores);

        if ($errores) {
            if (!empty($cv['nombre'])) {
                Upload::borrarDocumento('cvs', $cv['nombre']); // no dejar CV huérfano
            }
            $_SESSION['_old_bolsa'] = [
                'nombre' => $nombre, 'email' => $email, 'telefono' => $telefono,
                'area_interes' => $areaInteres, 'sueldo_deseado' => $sueldoDeseado,
                'disponibilidad' => $disponibilidad, 'escolaridad' => $escolaridad, 'mensaje' => $mensaje,
            ];
            flash('bolsa_error', implode(' ', $errores));
            $this->redirect($back);
        }

        $id = (new Postulacion())->crear([
            'vacante_id' => null, 'nombre' => $nombre, 'email' => $email, 'telefono' => $telefono,
            'area_interes' => $areaInteres, 'sueldo_deseado' => $sueldoDeseado,
            'disponibilidad' => $disponibilidad, 'escolaridad' => $escolaridad,
            'mensaje' => $mensaje, 'cv_archivo' => $cv['nombre'], 'ip' => client_ip(),
        ]);

        $this->avisarPostulacion($id, '', $nombre, $email, $telefono,
            trim($areaInteres . ($mensaje !== '' ? "\n\n" . $mensaje : '')));

        flash('bolsa_ok', '¡Gracias! Recibimos tu solicitud. La revisaremos y te contactaremos si surge algo para ti.');
        $this->redirect($back);
    }

    public function postular(string $slug): void
    {
        $vac  = (new Vacante())->abiertaPorSlug($slug);
        $back = '/bolsa-de-trabajo/' . $slug;
        if (!$vac) {
            $this->redirect('/bolsa-de-trabajo');
        }

        $this->guardPostulacion($back, 'postular-vacante:' . client_ip(), '¡Gracias! Recibimos tu postulación.');

        $nombre   = str_clean($_POST['nombre'] ?? '', 120);
        $email    = str_clean($_POST['email'] ?? '', 191);
        $telefono = str_clean($_POST['telefono'] ?? '', 30);
        $mensaje  = str_clean($_POST['mensaje'] ?? '', 2000);

        $errores = $this->erroresComunes($nombre, $email, $telefono);
        $cv = $this->subirCv($errores);

        if ($errores) {
            if (!empty($cv['nombre'])) {
                Upload::borrarDocumento('cvs', $cv['nombre']); // no dejar CV huérfano
            }
            $_SESSION['_old_bolsa'] = ['nombre' => $nombre, 'email' => $email, 'telefono' => $telefono, 'mensaje' => $mensaje];
            flash('bolsa_error', implode(' ', $errores));
            $this->redirect($back);
        }

        $id = (new Postulacion())->crear([
            'vacante_id' => (int) $vac['id'], 'nombre' => $nombre, 'email' => $email,
            'telefono' => $telefono, 'mensaje' => $mensaje, 'cv_archivo' => $cv['nombre'], 'ip' => client_ip(),
        ]);

        $this->avisarPostulacion($id, $vac['titulo'], $nombre, $email, $telefono, $mensaje);

        flash('bolsa_ok', '¡Gracias! Recibimos tu postulación. Si tu perfil coincide, te contactaremos.');
        $this->redirect($back);
    }

    /** CSRF + honeypot + límite de envíos, antes de procesar una postulación. Redirige si algo falla. */
    private function guardPostulacion(string $back, string $rateLimitKey, string $mensajeExitoBot): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('bolsa_error', 'La sesión expiró. Vuelve a intentarlo.');
            $this->redirect($back);
        }
        // Bot: se finge éxito sin guardar nada.
        if (honeypot_tripped()) {
            flash('bolsa_ok', $mensajeExitoBot);
            $this->redirect($back);
        }
        if (!RateLimiter::attempt($rateLimitKey, 5, 3600)) {
            flash('bolsa_error', 'Recibimos varias solicitudes tuyas. Inténtalo más tarde.');
            $this->redirect($back);
        }
    }

    /** Validaciones compartidas por la solicitud general y la postulación a vacante. */
    private function erroresComunes(string $nombre, string $email, string $telefono): array
    {
        $errores = [];
        if (!captcha_valido())                                $errores[] = 'Resuelve correctamente la comprobación anti-bot.';
        if (!nombre_valido($nombre))                          $errores[] = 'Captura tu nombre (solo letras).';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))       $errores[] = 'Captura un correo válido.';
        if ($telefono !== '' && !telefono_valido($telefono))  $errores[] = 'El teléfono no es válido.';
        return $errores;
    }

    /** Sube el CV (obligatorio, PDF); agrega el error a la lista si falla. */
    private function subirCv(array &$errores): array
    {
        $cv = Upload::documento($_FILES['cv'] ?? null, 'cvs');
        if ($cv['error'] !== null)      $errores[] = $cv['error'];
        elseif ($cv['nombre'] === null) $errores[] = 'Adjunta tu CV en formato PDF (máx. 5 MB).';
        return $cv;
    }

    /** Avisa a RRHH y envía el acuse de recibo al candidato (ninguno bloquea el flujo si falla). */
    private function avisarPostulacion(int $id, string $vacanteTitulo, string $nombre, string $email, string $telefono, string $mensaje): void
    {
        $rrhh = (string) (config('app.careers.rrhh_email') ?: config('app.mail.leads'));
        if ($rrhh !== '') {
            $asunto = $vacanteTitulo !== ''
                ? 'Nueva postulación: ' . $vacanteTitulo . ' — ' . $nombre
                : 'Nueva solicitud de empleo general — ' . $nombre;
            Mailer::enviar($rrhh, $asunto, 'postulacion_rrhh', [
                'vacante' => $vacanteTitulo, 'nombre' => $nombre, 'email' => $email, 'telefono' => $telefono,
                'mensaje' => $mensaje, 'fecha' => date('d/m/Y H:i'),
                'panelUrl' => rtrim((string) config('app.url'), '/') . '/admin/postulaciones/' . $id,
            ]);
        }
        Mailer::enviar($email, 'Recibimos tu ' . ($vacanteTitulo !== '' ? 'postulación' : 'solicitud') . ' — Importadora RYM', 'postulacion_recibida', [
            'nombre' => $nombre, 'vacante' => $vacanteTitulo,
        ]);
    }
}
