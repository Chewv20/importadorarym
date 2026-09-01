<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Mailer;
use App\Models\Usuario;
use App\Models\ListaProductos;

class ClienteController extends BaseController
{
    public function index(): void
    {
        Auth::authorize('clientes.ver');

        $model = new Usuario();
        $scope = $this->vendedorScope();
        $pend  = ($_GET['filtro'] ?? '') === 'pendientes';
        $pg    = $this->paginar($model->contarClientes($pend, $scope), 20);

        $this->render('admin/clientes', [
            'title'         => 'Clientes — Panel RYM',
            'active'        => 'clientes',
            'contentWide'   => true,
            'clientes'      => $model->clientesPaginado($pg['perPage'], $pg['offset'], $pend, $scope),
            'vendedores'    => $model->vendedores(),
            'listasProductos' => (new ListaProductos())->activas(),
            'pend'          => $pend,
            'totalPend'     => $model->contarClientes(true, $scope),
            'soloAsignados' => $scope !== null,
            'page'          => $pg['page'],
            'pages'         => $pg['pages'],
        ]);
    }

    public function aprobar(string $id): void
    {
        Auth::authorize('clientes.aprobar');
        $this->guard();
        $this->verificarAcceso((int) $id);

        $model = new Usuario();

        // La verificación de correo es requisito para poder levantar pedidos.
        $cliente = $model->find((int) $id);
        if ($cliente && empty($cliente['email_verificado_en'])) {
            flash('portal_error', 'No puedes aprobar a este cliente: aún no verifica su correo.');
            $this->redirect('/admin/clientes');
        }

        $model->aprobar((int) $id);
        \App\Core\Audit::cambio('aprobar', 'cliente', (int) $id, 'Aprobó al cliente ' . ($cliente['email'] ?? ('#' . $id)));

        // Avisa al cliente que ya puede levantar pedidos (no bloquea si falla).
        $u = $model->find((int) $id);
        if ($u && !empty($u['email'])) {
            Mailer::enviar($u['email'], 'Tu cuenta ya está activa — Importadora RYM', 'cuenta_aprobada', [
                'nombre' => $u['nombre'] ?? '',
            ]);
        }

        flash('portal_ok', 'Cliente aprobado. Ya puede ingresar al portal.');
        $this->redirect('/admin/clientes');
    }

    public function toggleActivo(string $id): void
    {
        Auth::authorize('clientes.aprobar');
        $this->guard();
        $this->verificarAcceso((int) $id);

        $model = new Usuario();
        $u = $model->find((int) $id);
        if ($u) {
            $activar = (int) $u['activo'] !== 1;
            $model->setActivo((int) $id, $activar);
            \App\Core\Audit::cambio('actualizar', 'cliente', (int) $id,
                ($activar ? 'Activó' : 'Desactivó') . ' al cliente ' . ($u['email'] ?? ('#' . $id)));
            flash('portal_ok', 'Estado del cliente actualizado.');
        }
        $this->redirect('/admin/clientes');
    }

    /**
     * Activa/desactiva el acceso de un cliente al portal (para que levante sus
     * propios pedidos). Sin acceso al portal, un vendedor puede seguir creando
     * pedidos a su nombre (Fase 7.9) — son dos cosas independientes.
     */
    public function togglePortalAcceso(string $id): void
    {
        Auth::authorize('clientes.aprobar');
        $this->guard();
        $this->verificarAcceso((int) $id);

        $model = new Usuario();
        $u = $model->find((int) $id);
        if ($u) {
            $sinAcceso = $model->sinAccesoPortal((int) $id);
            $model->setAccesoPortal((int) $id, $sinAcceso); // si no tenía, se lo da; si tenía, se lo quita
            \App\Core\Audit::cambio('actualizar', 'cliente', (int) $id,
                ($sinAcceso ? 'Restauró' : 'Quitó') . ' el acceso al portal del cliente ' . ($u['email'] ?? ('#' . $id)));
            flash('portal_ok', 'Acceso al portal actualizado.');
        }
        $this->redirect('/admin/clientes');
    }

    public function datosSae(string $id): void
    {
        Auth::authorize('clientes.aprobar');
        $this->guard();
        $this->verificarAcceso((int) $id);
        $vendedorId = (int) ($_POST['vendedor_id'] ?? 0) ?: null;
        (new Usuario())->setDatosSae((int) $id, str_clean($_POST['clave_sae'] ?? '', 30), $vendedorId);
        \App\Core\Audit::cambio('actualizar', 'cliente', (int) $id, 'Actualizó los datos de SAE del cliente #' . (int) $id);
        flash('portal_ok', 'Datos de SAE del cliente actualizados.');
        $this->redirect('/admin/clientes');
    }

    /**
     * Asigna (o quita) la lista de productos del cliente (Fase 7.3). Requiere
     * solo 'clientes.aprobar' (no 'listas.gestionar'): el vendedor puede asignar
     * una lista ya creada por administración, no crear/editar su contenido.
     */
    public function listaProductos(string $id): void
    {
        Auth::authorize('clientes.aprobar');
        $this->guard();
        $this->verificarAcceso((int) $id);
        $listaId = (int) ($_POST['lista_productos_id'] ?? 0) ?: null;
        (new Usuario())->setListaProductos((int) $id, $listaId);
        \App\Core\Audit::cambio('actualizar', 'cliente', (int) $id, 'Actualizó la lista de productos del cliente #' . (int) $id);
        flash('portal_ok', 'Lista de productos del cliente actualizada.');
        $this->redirect('/admin/clientes');
    }

    private function guard(): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/clientes');
        }
    }

    /** Si el usuario es un vendedor con alcance limitado, exige que el cliente sea suyo. */
    private function verificarAcceso(int $id): void
    {
        $scope = $this->vendedorScope();
        if ($scope !== null && !(new Usuario())->esClienteDeVendedor($id, $scope)) {
            flash('portal_error', 'No tienes acceso a ese cliente.');
            $this->redirect('/admin/clientes');
        }
    }
}
