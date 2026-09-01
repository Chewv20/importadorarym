<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Mailer;
use App\Core\Upload;
use App\Models\Cotizacion;

class CotizacionController extends Controller
{
    /** Orígenes permitidos para el lead. */
    private const ORIGENES = ['landing', 'contacto', 'productos'];

    public function store(): void
    {
        $origen = in_array($_POST['origen'] ?? '', self::ORIGENES, true)
            ? $_POST['origen']
            : 'landing';

        // A dónde volver según desde qué página se envió.
        $back = $origen === 'contacto' ? '/contacto#form' : '/#cotiza';

        // Protección CSRF
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('cotizacion_error', 'La sesión expiró. Vuelve a intentarlo.');
            $this->redirect($back);
        }

        // Bot: se finge éxito sin guardar nada.
        if (honeypot_tripped()) {
            flash('cotizacion_ok', '¡Gracias! Recibimos tu solicitud. Un asesor te contactará muy pronto.');
            $this->redirect($back);
        }

        // Límite anti-spam por IP.
        if (!RateLimiter::attempt('cotizar:' . client_ip(), 5, 600)) {
            flash('cotizacion_error', 'Recibimos varias solicitudes tuyas. Inténtalo de nuevo en unos minutos.');
            $this->redirect($back);
        }

        $nombre            = str_clean($_POST['nombre'] ?? '', 120);
        $empresa           = str_clean($_POST['empresa'] ?? '', 150);
        $email             = str_clean($_POST['email'] ?? '', 191);
        $telefono          = str_clean($_POST['telefono'] ?? '', 30);
        $producto          = str_clean($_POST['producto_interes'] ?? '', 150);
        $mensaje           = str_clean($_POST['mensaje'] ?? '', 2000);
        $requiereImpresion = !empty($_POST['requiere_impresion']);

        $old = [
            'nombre'             => $nombre,
            'empresa'            => $empresa,
            'email'              => $email,
            'telefono'           => $telefono,
            'producto_interes'   => $producto,
            'mensaje'            => $mensaje,
            'requiere_impresion' => $requiereImpresion,
        ];

        $errores = [];
        if (!captcha_valido())                                  $errores[] = 'Resuelve correctamente la comprobación anti-bot.';
        if (!nombre_valido($nombre))                            $errores[] = 'El nombre solo puede contener letras, espacios y . - \'.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))         $errores[] = 'Captura un correo válido.';
        if ($empresa !== '' && !empresa_valida($empresa))       $errores[] = 'La empresa contiene caracteres no permitidos.';
        if ($telefono !== '' && !telefono_valido($telefono))    $errores[] = 'El teléfono solo admite dígitos y + - ( ).';

        if ($errores) {
            $_SESSION['_old'] = $old;
            flash('cotizacion_error', implode(' ', $errores));
            $this->redirect($back);
        }

        // El logo se guarda hasta este punto (todo lo demás ya es válido) para
        // no dejar un archivo huérfano en storage/ si el resto del formulario falla.
        $logoArchivo = null;
        if ($requiereImpresion) {
            $logo = Upload::logoCotizacion($_FILES['logo'] ?? null, 'cotizaciones_logos');
            if ($logo['error']) {
                $_SESSION['_old'] = $old;
                flash('cotizacion_error', $logo['error']);
                $this->redirect($back);
            }
            $logoArchivo = $logo['nombre'];
        }

        $cot = [
            'nombre'             => $nombre,
            'empresa'            => $empresa ?: null,
            'email'              => $email,
            'telefono'           => $telefono ?: null,
            'producto_interes'   => $producto ?: null,
            'mensaje'            => $mensaje ?: null,
            'requiere_impresion' => $requiereImpresion,
            'origen'             => $origen,
        ];

        (new Cotizacion())->crear($cot + [
            'logo_archivo' => $logoArchivo,
            'ip'           => client_ip(),
            'user_agent'   => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255) ?: null,
        ]);

        // Avisos por correo (no bloquean el flujo si fallan).
        $leads = config('app.mail.leads');
        if ($leads) {
            Mailer::enviar($leads, 'Nueva cotización de ' . $nombre, 'cotizacion_asesor', ['cot' => $cot]);
        }
        Mailer::enviar($email, 'Recibimos tu solicitud — Importadora RYM', 'cotizacion_cliente', ['nombre' => $nombre]);

        flash('cotizacion_ok', '¡Gracias! Recibimos tu solicitud. Un asesor te contactará muy pronto.');
        $this->redirect($back);
    }
}
