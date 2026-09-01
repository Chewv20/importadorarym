<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;

/**
 * Base de los controladores del panel de administración.
 * Exige sesión activa con el permiso 'admin.acceder'.
 */
abstract class BaseController extends Controller
{
    protected array $usuario;

    public function __construct()
    {
        if (!Auth::check()) {
            flash('portal_error', 'Inicia sesión para acceder al panel.');
            $this->go('/portal/login');
        }

        $user = Auth::user();

        if (!$user || (int) $user['activo'] !== 1 || !Auth::can('admin.acceder')) {
            // Un cliente autenticado se manda a su portal; el resto, al login.
            if ($user && Auth::can('portal.acceder')) {
                $this->go('/portal');
            }
            Auth::logout();
            flash('portal_error', 'No tienes acceso al panel de administración.');
            $this->go('/portal/login');
        }

        $this->usuario = $user;
    }

    private function go(string $path): void
    {
        header('Location: ' . base_url($path));
        exit;
    }

    protected function render(string $view, array $data = []): void
    {
        $data['usuario'] = $this->usuario;
        $data['active']  = $data['active'] ?? '';
        $this->view($view, $data, 'layouts/admin');
    }

    /** Paginación: calcula página, offset y total de páginas. */
    protected function paginar(int $total, int $porPagina = 20): array
    {
        $pages  = max(1, (int) ceil($total / $porPagina));
        $page   = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);
        return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $porPagina, 'perPage' => $porPagina];
    }

    /**
     * Alcance de vendedor: si el usuario tiene el permiso 'ventas.solo_asignados'
     * devuelve su propio id (solo verá sus clientes asignados y sus pedidos/
     * cotizaciones). Si no lo tiene, devuelve null = ve todo.
     */
    protected function vendedorScope(): ?int
    {
        return Auth::can('ventas.solo_asignados') ? (int) $this->usuario['id'] : null;
    }
}
