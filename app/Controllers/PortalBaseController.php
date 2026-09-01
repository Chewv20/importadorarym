<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;

/**
 * Base de los controladores del portal. Exige sesión de cliente activa y
 * aprobada; en caso contrario redirige al login.
 */
abstract class PortalBaseController extends Controller
{
    protected array $usuario;

    public function __construct()
    {
        if (!Auth::check()) {
            $this->goLogin('Inicia sesión para acceder a tu portal.');
        }

        $user = Auth::user();
        if (!$user || (int) $user['activo'] !== 1) {
            Auth::logout();
            $this->goLogin('Tu cuenta está desactivada.');
        }

        if (!Auth::can('portal.acceder')) {
            Auth::logout();
            $this->goLogin('Tu cuenta no tiene acceso al portal de clientes.');
        }

        $this->usuario = $user;
    }

    /** El cliente puede solicitar cotizaciones siempre, pero levantar pedidos
        requiere que su cuenta esté aprobada. */
    protected function requiereAprobacion(): void
    {
        if ((int) ($this->usuario['aprobado'] ?? 0) !== 1) {
            flash('portal_error', 'Tu cuenta está pendiente de aprobación. Mientras tanto puedes solicitar cotizaciones; en cuanto la activemos podrás levantar pedidos.');
            $this->redirect('/portal/cotizar');
        }
    }

    private function goLogin(string $mensaje): void
    {
        flash('portal_error', $mensaje);
        header('Location: ' . base_url('/portal/login'));
        exit;
    }

    /** Renderiza una vista dentro del layout del portal, con el usuario disponible. */
    protected function render(string $view, array $data = []): void
    {
        $data['usuario'] = $this->usuario;
        $data['active']  = $data['active'] ?? '';
        $this->view($view, $data, 'layouts/portal');
    }
}
