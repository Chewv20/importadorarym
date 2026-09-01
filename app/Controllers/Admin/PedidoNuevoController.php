<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Audit;
use App\Core\CarritoPedido;
use App\Core\Mailer;
use App\Controllers\PedidoController;
use App\Models\Categoria;
use App\Models\Usuario;
use App\Models\Pedido;
use App\Models\ListaProductos;

/**
 * Fase 7.9: el vendedor (o el gerente de ventas, sin la restricción de alcance)
 * crea un pedido a nombre de un cliente. Mismo catálogo/carrito que el portal,
 * pero el dueño del pedido es el cliente elegido, no el actor autenticado.
 */
class PedidoNuevoController extends BaseController
{
    private const POR_PAGINA = 12;

    /** Cliente elegido y carrito de esta sesión de captura (independientes del portal). */
    private const SESSION_CLIENTE = 'admin_pedido_cliente_id';
    private const SESSION_CARRITO = 'admin_pedido_carrito';

    private function carrito(): CarritoPedido
    {
        return new CarritoPedido(self::SESSION_CARRITO, PedidoController::MAX_PARTIDAS);
    }

    public function index(): void
    {
        Auth::authorize('pedidos.crear_para_cliente');

        $cliente = $this->clienteEnSesion();

        if (!$cliente) {
            $this->render('admin/pedido_nuevo', [
                'title'    => 'Nuevo pedido para un cliente — Panel RYM',
                'active'   => 'pedidos',
                'cliente'  => null,
                'clientes' => (new Usuario())->clientesElegibles($this->vendedorScope()),
            ]);
            return;
        }

        $categoriaModel = new Categoria();

        $q = str_clean($_GET['q'] ?? '', 60) ?: null;
        $slug = str_clean($_GET['categoria'] ?? '', 180);
        $cat = $slug !== '' ? $categoriaModel->porSlug($slug) : null;
        $categoriaIds = $cat ? $categoriaModel->descendientesIds((int) $cat['id']) : null;
        $productoIds = (new ListaProductos())->idsParaCliente($cliente['lista_productos_id'] ?? null);

        $catalogo = CarritoPedido::catalogo(self::POR_PAGINA, (int) ($_GET['page'] ?? 1), $categoriaIds, $q, $productoIds);

        $this->render('admin/pedido_nuevo', [
            'title'      => 'Nuevo pedido para ' . $cliente['nombre'] . ' — Panel RYM',
            'active'     => 'pedidos',
            'cliente'    => $cliente,
            'productos'  => $catalogo['productos'],
            'carrito'    => $this->carrito()->detallado(),
            'categorias' => $categoriaModel->activasArbol(),
            'q'          => $q ?? '',
            'catActual'  => $cat['slug'] ?? '',
            'accion'     => '/admin/pedidos/nuevo',
            'page'       => $catalogo['page'],
            'pages'      => $catalogo['pages'],
        ]);
    }

    /** Autocompletar de clientes (JSON), acotado por vendedor si aplica. */
    public function buscarClientes(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!Auth::can('pedidos.crear_para_cliente')) {
            http_response_code(403);
            echo json_encode(['error' => 'forbidden']);
            return;
        }
        $q = str_clean($_GET['q'] ?? '', 60);
        if (mb_strlen($q) < 2) {
            echo json_encode([]);
            return;
        }
        $items = array_map(static fn ($u) => [
            'id'       => (int) $u['id'],
            'nombre'   => $u['nombre'],
            'empresa'  => $u['empresa'],
            'aprobado' => (int) $u['aprobado'] === 1,
        ], (new Usuario())->buscarClientes($q, 15, $this->vendedorScope()));
        echo json_encode($items);
    }

    public function seleccionarCliente(): void
    {
        $this->guardCsrf();
        Auth::authorize('pedidos.crear_para_cliente');

        $id = (int) ($_POST['cliente_id'] ?? 0);
        $cliente = $id ? (new Usuario())->find($id) : null;

        if (!$this->clienteEsElegible($cliente)) {
            flash('portal_error', 'Selecciona un cliente aprobado y activo.');
            $this->redirect('/admin/pedidos/nuevo');
        }

        $_SESSION[self::SESSION_CLIENTE] = $id;
        unset($_SESSION[self::SESSION_CARRITO]);
        $this->redirect('/admin/pedidos/nuevo');
    }

    public function cambiarCliente(): void
    {
        $this->guardCsrf();
        unset($_SESSION[self::SESSION_CLIENTE], $_SESSION[self::SESSION_CARRITO]);
        $this->redirect('/admin/pedidos/nuevo');
    }

    public function agregar(): void
    {
        $this->guardCsrf();
        Auth::authorize('pedidos.crear_para_cliente');
        $this->exigirCliente();

        $cliente = $this->clienteEnSesion();
        $pid = (int) ($_POST['producto_id'] ?? 0);
        $cantSolicitada = min(9999, max(1, (int) ($_POST['cantidad'] ?? 1)));
        $productoIds = (new ListaProductos())->idsParaCliente($cliente['lista_productos_id'] ?? null);

        $r = $this->carrito()->agregar($pid, $cantSolicitada, $productoIds);
        if (!$r['ok']) {
            flash('portal_error', $r['motivo'] === 'limite'
                ? 'El pedido ya tiene ' . PedidoController::MAX_PARTIDAS . ' productos distintos, el máximo por pedido.'
                : 'Ese producto no está disponible.');
        } elseif ($r['ajustado']) {
            flash('portal_ok', 'Se ajustó a ' . $r['cantidad'] . ' piezas para completar la presentación de "' . $r['producto']['nombre'] . '".');
        } else {
            flash('portal_ok', 'Producto agregado.');
        }
        $this->redirect('/admin/pedidos/nuevo');
    }

    /**
     * Quita un producto del carrito. Se usa desde el catálogo y desde la pantalla
     * de confirmación; "origen" indica a cuál de las dos regresar.
     */
    public function quitar(): void
    {
        $back = ($_POST['origen'] ?? '') === 'confirmar' ? '/admin/pedidos/nuevo/confirmar' : '/admin/pedidos/nuevo';
        $this->guardCsrf($back);
        $this->exigirCliente();
        $this->carrito()->quitar((int) ($_POST['producto_id'] ?? 0));
        $this->redirect($back);
    }

    /** Fija (no suma) la cantidad de una partida — se usa desde "Revisar y confirmar". */
    public function actualizarCantidad(): void
    {
        $this->guardCsrf('/admin/pedidos/nuevo/confirmar');
        $this->exigirCliente();

        $pid = (int) ($_POST['producto_id'] ?? 0);
        $cantSolicitada = (int) ($_POST['cantidad'] ?? 0);
        $r = $this->carrito()->actualizarCantidad($pid, $cantSolicitada);

        if (!$r['ok']) {
            flash('portal_error', 'La cantidad debe ser entre 1 y 9999.');
        } elseif ($r['ajustado']) {
            flash('portal_ok', 'Se ajustó a ' . $r['cantidad'] . ' piezas para completar la presentación'
                . ($r['producto'] ? ' de "' . $r['producto']['nombre'] . '"' : '') . '.');
        }
        $this->redirect('/admin/pedidos/nuevo/confirmar');
    }

    /** Pantalla de revisión: cantidades editables antes de crear el pedido. */
    public function confirmar(): void
    {
        Auth::authorize('pedidos.crear_para_cliente');
        $cliente = $this->clienteEnSesion();
        if (!$cliente) {
            flash('portal_error', 'Primero selecciona un cliente.');
            $this->redirect('/admin/pedidos/nuevo');
        }

        $carrito = $this->carrito()->detallado();
        if (!$carrito) {
            flash('portal_error', 'El pedido está vacío.');
            $this->redirect('/admin/pedidos/nuevo');
        }

        $this->render('admin/pedido_confirmar', [
            'title'   => 'Revisar pedido para ' . $cliente['nombre'] . ' — Panel RYM',
            'active'  => 'pedidos',
            'cliente' => $cliente,
            'carrito' => $carrito,
        ]);
    }

    public function store(): void
    {
        $this->guardCsrf('/admin/pedidos/nuevo/confirmar');
        Auth::authorize('pedidos.crear_para_cliente');
        $this->exigirCliente();

        // Se revalida por si el cliente cambió de estado entre que se eligió y se envía.
        $cliente = (new Usuario())->find((int) $_SESSION[self::SESSION_CLIENTE]);
        if (!$this->clienteEsElegible($cliente)) {
            flash('portal_error', 'Ese cliente ya no está disponible para levantar pedidos.');
            unset($_SESSION[self::SESSION_CLIENTE], $_SESSION[self::SESSION_CARRITO]);
            $this->redirect('/admin/pedidos/nuevo');
        }

        $items = $this->carrito()->armarItems();
        if (!$items) {
            flash('portal_error', 'El pedido está vacío.');
            $this->redirect('/admin/pedidos/nuevo');
        }

        $notas = str_clean($_POST['notas'] ?? '', 1000) ?: null;
        $referencia = str_clean($_POST['referencia'] ?? '', 60) ?: null;

        $pedidoModel = new Pedido();
        $id = $pedidoModel->crear((int) $cliente['id'], $items, $notas, 'enviado', $referencia);

        unset($_SESSION[self::SESSION_CLIENTE]);
        $this->carrito()->vaciar();

        Audit::cambio('crear', 'pedido', $id,
            'Creó el pedido a nombre de ' . $cliente['nombre'] . ' (' . $cliente['email'] . ')');

        // Aviso interno (no bloquea si falla) — mismo destino y plantilla que el portal.
        $leads = config('app.mail.leads');
        if ($leads) {
            $nuevo = $pedidoModel->find($id);
            Mailer::enviar($leads, 'Nuevo pedido ' . ($nuevo['folio'] ?? ('#' . $id)) . ' — ' . $cliente['nombre'], 'pedido_nuevo', [
                'folio'      => $nuevo['folio'] ?? ('#' . $id),
                'cliente'    => $cliente['nombre'],
                'empresa'    => (string) ($cliente['empresa'] ?? ''),
                'referencia' => (string) ($referencia ?? ''),
                'notas'      => (string) ($notas ?? ''),
                'items'      => $items,
                'pedidoId'   => $id,
                'creadoPor'  => $this->usuario['nombre'],
            ]);
        }

        flash('portal_ok', 'Pedido creado para ' . $cliente['nombre'] . '.');
        $this->redirect('/admin/pedidos/' . $id);
    }

    /* --------------------------------- Utilidades --------------------- */

    /** Cliente elegido en esta sesión de captura, o null si no hay uno válido. */
    private function clienteEnSesion(): ?array
    {
        $id = $_SESSION[self::SESSION_CLIENTE] ?? null;
        if (!$id) {
            return null;
        }
        $cliente = (new Usuario())->find((int) $id);
        if (!$this->clienteEsElegible($cliente)) {
            // Sesión inconsistente (cliente eliminado/desactivado entre tanto): se limpia.
            unset($_SESSION[self::SESSION_CLIENTE], $_SESSION[self::SESSION_CARRITO]);
            return null;
        }
        return $cliente;
    }

    /**
     * Activo y (si el actor es vendedor con alcance) asignado a él. NO se exige
     * aprobado: esa aprobación solo controla que el cliente levante pedidos por su
     * cuenta en el portal, no que un vendedor le levante uno a nombre suyo.
     */
    private function clienteEsElegible(?array $cliente): bool
    {
        if (!$cliente || (int) ($cliente['activo'] ?? 0) !== 1) {
            return false;
        }
        $scope = $this->vendedorScope();
        return $scope === null || (int) ($cliente['vendedor_id'] ?? 0) === $scope;
    }

    private function exigirCliente(): void
    {
        if (!$this->clienteEnSesion()) {
            flash('portal_error', 'Primero selecciona un cliente.');
            $this->redirect('/admin/pedidos/nuevo');
        }
    }

    private function guardCsrf(string $back = '/admin/pedidos/nuevo'): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró. Vuelve a intentarlo.');
            $this->redirect($back);
        }
    }
}
