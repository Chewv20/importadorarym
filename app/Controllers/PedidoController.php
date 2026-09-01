<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\CarritoPedido;
use App\Core\Mailer;
use App\Models\Producto;
use App\Models\Pedido;
use App\Models\Categoria;
use App\Models\EncuestaPedido;
use App\Models\EnvioEncuesta;
use App\Models\ListaProductos;
use App\Models\PedidoFactura;

class PedidoController extends PortalBaseController
{
    private const POR_PAGINA = 12;

    /** Partidas distintas que admite el carrito (acota lo que crece en la sesión). */
    public const MAX_PARTIDAS = 200;

    /* ------------------------------------------- Armar pedido -------- */

    private function carrito(): CarritoPedido
    {
        return new CarritoPedido('carrito', self::MAX_PARTIDAS);
    }

    public function nuevo(): void
    {
        Auth::authorize('pedidos.crear');
        $this->requiereAprobacion();

        $categoriaModel = new Categoria();

        $q = str_clean($_GET['q'] ?? '', 60) ?: null;
        $slug = str_clean($_GET['categoria'] ?? '', 180);
        $cat = $slug !== '' ? $categoriaModel->porSlug($slug) : null;
        $categoriaIds = $cat ? $categoriaModel->descendientesIds((int) $cat['id']) : null;
        $productoIds = (new ListaProductos())->idsParaCliente($this->usuario['lista_productos_id'] ?? null);

        $catalogo = CarritoPedido::catalogo(self::POR_PAGINA, (int) ($_GET['page'] ?? 1), $categoriaIds, $q, $productoIds);

        $this->render('portal/pedido_nuevo', [
            'title'      => 'Nuevo pedido — Portal RYM',
            'active'     => 'nuevo',
            'productos'  => $catalogo['productos'],
            'carrito'    => $this->carrito()->detallado(),
            'categorias' => $categoriaModel->activasArbol(),
            'q'          => $q ?? '',
            'catActual'  => $cat['slug'] ?? '',
            'accion'     => '/portal/pedidos/nuevo',
            'page'       => $catalogo['page'],
            'pages'      => $catalogo['pages'],
        ]);
    }

    public function agregar(): void
    {
        $this->guardCsrf('/portal/pedidos/nuevo');
        Auth::authorize('pedidos.crear');
        $this->requiereAprobacion();

        $pid = (int) ($_POST['producto_id'] ?? 0);
        $cantSolicitada = min(9999, max(1, (int) ($_POST['cantidad'] ?? 1)));
        $productoIds = (new ListaProductos())->idsParaCliente($this->usuario['lista_productos_id'] ?? null);

        $r = $this->carrito()->agregar($pid, $cantSolicitada, $productoIds);
        if (!$r['ok']) {
            flash('portal_error', $r['motivo'] === 'limite'
                ? 'Tu pedido ya tiene ' . self::MAX_PARTIDAS . ' productos distintos, el máximo por pedido. Envíalo y levanta otro para el resto.'
                : 'Ese producto no está disponible.');
        } elseif ($r['ajustado']) {
            flash('portal_ok', 'Se ajustó a ' . $r['cantidad'] . ' piezas para completar la presentación de "' . $r['producto']['nombre'] . '".');
        } else {
            flash('portal_ok', 'Producto agregado a tu pedido.');
        }

        $this->redirect('/portal/pedidos/nuevo');
    }

    /**
     * Quita un producto del carrito. Se usa desde el catálogo y desde la pantalla
     * de confirmación; "origen" indica a cuál de las dos regresar.
     */
    public function quitar(): void
    {
        $back = ($_POST['origen'] ?? '') === 'confirmar' ? '/portal/pedidos/confirmar' : '/portal/pedidos/nuevo';
        $this->guardCsrf($back);
        $this->requiereAprobacion();
        $this->carrito()->quitar((int) ($_POST['producto_id'] ?? 0));
        $this->redirect($back);
    }

    /** Fija (no suma) la cantidad de una partida — se usa desde "Revisar y confirmar". */
    public function actualizarCantidad(): void
    {
        $this->guardCsrf('/portal/pedidos/confirmar');
        $this->requiereAprobacion();

        $pid = (int) ($_POST['producto_id'] ?? 0);
        $cantSolicitada = (int) ($_POST['cantidad'] ?? 0);
        $r = $this->carrito()->actualizarCantidad($pid, $cantSolicitada);

        if (!$r['ok']) {
            flash('portal_error', 'La cantidad debe ser entre 1 y 9999.');
        } elseif ($r['ajustado']) {
            flash('portal_ok', 'Se ajustó a ' . $r['cantidad'] . ' piezas para completar la presentación'
                . ($r['producto'] ? ' de "' . $r['producto']['nombre'] . '"' : '') . '.');
        }
        $this->redirect('/portal/pedidos/confirmar');
    }

    /** Pantalla de revisión: cantidades editables antes de enviar el pedido. */
    public function confirmar(): void
    {
        Auth::authorize('pedidos.crear');
        $this->requiereAprobacion();

        $carrito = $this->carrito()->detallado();
        if (!$carrito) {
            flash('portal_error', 'Tu pedido está vacío.');
            $this->redirect('/portal/pedidos/nuevo');
        }

        $this->render('portal/pedido_confirmar', [
            'title'   => 'Revisar pedido — Portal RYM',
            'active'  => 'nuevo',
            'carrito' => $carrito,
        ]);
    }

    public function store(): void
    {
        $this->guardCsrf('/portal/pedidos/confirmar');
        Auth::authorize('pedidos.crear');
        $this->requiereAprobacion();

        $items = $this->carrito()->armarItems();
        if (!$items) {
            flash('portal_error', 'Tu pedido está vacío.');
            $this->redirect('/portal/pedidos/nuevo');
        }

        $notas = str_clean($_POST['notas'] ?? '', 1000) ?: null;
        $referencia = str_clean($_POST['referencia'] ?? '', 60) ?: null;
        $pedidoModel = new Pedido();
        $id = $pedidoModel->crear((int) $this->usuario['id'], $items, $notas, 'enviado', $referencia);

        $this->carrito()->vaciar();

        // Aviso interno del pedido nuevo (no bloquea si falla).
        $leads = config('app.mail.leads');
        if ($leads) {
            $nuevo = $pedidoModel->find($id);
            Mailer::enviar($leads, 'Nuevo pedido ' . ($nuevo['folio'] ?? ('#' . $id)) . ' — ' . $this->usuario['nombre'], 'pedido_nuevo', [
                'folio'      => $nuevo['folio'] ?? ('#' . $id),
                'cliente'    => $this->usuario['nombre'],
                'empresa'    => (string) ($this->usuario['empresa'] ?? ''),
                'referencia' => (string) ($referencia ?? ''),
                'notas'      => (string) ($notas ?? ''),
                'items'      => $items,
                'pedidoId'   => $id,
            ]);
        }

        flash('portal_ok', '¡Pedido enviado! Nuestro equipo lo procesará y te contactará con la cotización.');
        $this->redirect('/portal/pedidos/' . $id);
    }

    /** Recarga en el carrito las partidas de un pedido anterior para volver a pedirlas. */
    public function repetir(string $id): void
    {
        $this->guardCsrf('/portal/pedidos/' . (int) $id);
        Auth::authorize('pedidos.crear');
        $this->requiereAprobacion();

        $pedidoModel = new Pedido();
        $pedido = $pedidoModel->find((int) $id);

        // Verificación de propiedad: solo el dueño puede repetir su pedido.
        if (!$pedido || (int) $pedido['usuario_id'] !== (int) $this->usuario['id']) {
            flash('portal_error', 'No encontramos ese pedido.');
            $this->redirect('/portal/pedidos');
        }

        $items = $pedidoModel->items((int) $pedido['id']);
        if (!$items) {
            flash('portal_error', 'Ese pedido no tiene productos para repetir.');
            $this->redirect('/portal/pedidos/' . (int) $id);
        }

        // Solo se recargan productos que aún existen y siguen activos.
        $productos = (new Producto())->porIds(array_map(static fn($it) => (int) $it['producto_id'], $items));

        $agregados = 0;
        $omitidos  = 0;
        foreach ($items as $it) {
            $pid  = (int) $it['producto_id'];
            $prod = $productos[$pid] ?? null;
            $cabe = isset($_SESSION['carrito'][$pid])
                || count($_SESSION['carrito'] ?? []) < self::MAX_PARTIDAS;
            if ($prod && (int) $prod['activo'] === 1 && $cabe) {
                $cant = min(9999, ($_SESSION['carrito'][$pid] ?? 0) + (int) $it['cantidad']);
                $_SESSION['carrito'][$pid] = $cant;
                $agregados++;
            } else {
                $omitidos++;
            }
        }

        if ($agregados === 0) {
            flash('portal_error', 'Los productos de ese pedido ya no están disponibles.');
            $this->redirect('/portal/pedidos/' . (int) $id);
        }

        $msg = 'Agregamos ' . $agregados . ' producto(s) del pedido ' . $pedido['folio'] . ' a tu carrito. Revisa las cantidades y confirma.';
        if ($omitidos > 0) {
            $msg .= ' ' . $omitidos . ' producto(s) ya no está(n) disponible(s) y se omitió(eron).';
        }
        flash('portal_ok', $msg);
        $this->redirect('/portal/pedidos/nuevo');
    }

    /* ------------------------------------------- Consultar ----------- */

    public function index(): void
    {
        Auth::authorize('pedidos.ver_propios');
        $this->requiereAprobacion();

        $this->render('portal/pedidos', [
            'title'   => 'Mis pedidos — Portal RYM',
            'active'  => 'pedidos',
            'pedidos' => (new Pedido())->porUsuario((int) $this->usuario['id']),
        ]);
    }

    public function show(string $id): void
    {
        Auth::authorize('pedidos.ver_propios');
        $this->requiereAprobacion();

        $pedidoModel = new Pedido();
        $pedido = $pedidoModel->find((int) $id);

        if (!$pedido || (int) $pedido['usuario_id'] !== (int) $this->usuario['id']) {
            flash('portal_error', 'No encontramos ese pedido.');
            $this->redirect('/portal/pedidos');
        }

        $encuestaModel = new EncuestaPedido();

        $facturasPorEnvio = [];
        foreach ((new PedidoFactura())->paraPedido((int) $pedido['id']) as $f) {
            $facturasPorEnvio[(int) $f['envio_id']][] = $f;
        }

        $this->render('portal/pedido_detalle', [
            'title'       => 'Pedido ' . $pedido['folio'] . ' — Portal RYM',
            'active'      => 'pedidos',
            'pedido'      => $pedido,
            'items'       => $pedidoModel->items((int) $pedido['id']),
            'encuesta'    => $encuestaModel->porPedido((int) $pedido['id']),
            'calificable' => in_array($pedido['estado'], EncuestaPedido::ESTADOS_CALIFICABLES, true),
            'encuestaError' => flash('encuesta_error'),
            'envios'         => $pedidoModel->enviosParaPortal((int) $pedido['id']),
            'encuestaEntregaError' => flash('encuesta_entrega_error'),
            'facturasPorEnvio'     => $facturasPorEnvio,
        ]);
    }

    /** Descarga el PDF o XML (?tipo=pdf|xml) de una factura de un pedido propio. */
    public function descargarFactura(string $id, string $facturaId): void
    {
        Auth::authorize('pedidos.ver_propios');
        $this->requiereAprobacion();

        $factura = (new PedidoFactura())->conDetalle((int) $facturaId);
        if (!$factura || (int) $factura['pedido_id'] !== (int) $id || (int) $factura['usuario_id'] !== (int) $this->usuario['id']) {
            flash('portal_error', 'No encontramos esa factura.');
            $this->redirect('/portal/pedidos/' . (int) $id);
        }

        $tipo = ($_GET['tipo'] ?? '') === 'xml' ? 'xml' : 'pdf';
        $archivoNombre = $tipo === 'xml' ? $factura['archivo_xml'] : $factura['archivo_pdf'];
        $ruta = \App\Core\Upload::documentoRuta('facturas', $archivoNombre);
        if (!$ruta) {
            flash('portal_error', 'El archivo no está disponible.');
            $this->redirect('/portal/pedidos/' . (int) $id);
        }
        $nombre = 'factura-' . slugify((string) ($factura['folio'] ?? $factura['id'])) . '.' . $tipo;
        $this->streamFile($ruta, $nombre, $tipo === 'xml' ? 'application/xml' : 'application/pdf');
    }

    /** Guarda la encuesta de satisfacción de la entrega de un envío del cliente. */
    public function encuestaEntrega(string $id, string $envioId): void
    {
        $this->guardCsrf('/portal/pedidos/' . (int) $id);
        Auth::authorize('pedidos.ver_propios');
        $this->requiereAprobacion();

        $pedidoModel = new Pedido();
        $envio = $pedidoModel->envioConDetalle((int) $envioId);
        if (!$envio || (int) $envio['pedido_id'] !== (int) $id || (int) $envio['usuario_id'] !== (int) $this->usuario['id']) {
            flash('portal_error', 'No encontramos ese envío.');
            $this->redirect('/portal/pedidos/' . (int) $id);
        }

        $model = new EnvioEncuesta();
        // Solo envíos ya entregados y aún sin calificar.
        if ($envio['evento'] !== 'entregado' || $model->porEnvio((int) $envioId)) {
            flash('portal_error', 'Ese envío no se puede calificar o ya lo calificaste.');
            $this->redirect('/portal/pedidos/' . (int) $id);
        }

        $llegoCompleto = ($_POST['llego_completo'] ?? '') === '1';
        $sat = (int) ($_POST['satisfaccion'] ?? 0);
        if ($sat < 1 || $sat > 5) {
            flash('encuesta_entrega_error', 'Califica tu satisfacción con la entrega (1 a 5).');
            $this->redirect('/portal/pedidos/' . (int) $id . '#envio-' . (int) $envioId);
        }

        $model->crear([
            'envio_id'       => (int) $envioId,
            'usuario_id'     => (int) $this->usuario['id'],
            'llego_completo' => $llegoCompleto,
            'que_falto'      => str_clean($_POST['que_falto'] ?? '', 500) ?: null,
            'satisfaccion'   => $sat,
            'comentario'     => str_clean($_POST['comentario'] ?? '', 1000) ?: null,
        ]);

        flash('portal_ok', '¡Gracias por calificar tu entrega!');
        $this->redirect('/portal/pedidos/' . (int) $id . '#envio-' . (int) $envioId);
    }

    /** Guarda la encuesta de experiencia de un pedido del cliente. */
    public function encuesta(string $id): void
    {
        $this->guardCsrf('/portal/pedidos/' . (int) $id);
        Auth::authorize('pedidos.ver_propios');
        $this->requiereAprobacion();

        $pedidoModel = new Pedido();
        $pedido = $pedidoModel->find((int) $id);
        if (!$pedido || (int) $pedido['usuario_id'] !== (int) $this->usuario['id']) {
            flash('portal_error', 'No encontramos ese pedido.');
            $this->redirect('/portal/pedidos');
        }

        $model = new EncuestaPedido();
        // Solo pedidos ya procesados y aún sin calificar.
        if (!in_array($pedido['estado'], EncuestaPedido::ESTADOS_CALIFICABLES, true) || $model->porPedido((int) $id)) {
            flash('portal_error', 'Ese pedido no se puede calificar o ya lo calificaste.');
            $this->redirect('/portal/pedidos/' . (int) $id);
        }

        $sat = (int) ($_POST['satisfaccion'] ?? 0);
        $fac = (int) ($_POST['facilidad'] ?? 0);
        if ($sat < 1 || $sat > 5 || $fac < 1 || $fac > 5) {
            flash('encuesta_error', 'Califica la satisfacción y la facilidad (1 a 5).');
            $this->redirect('/portal/pedidos/' . (int) $id);
        }

        $npsRaw = (string) ($_POST['nps'] ?? '');
        $nps = ($npsRaw !== '' && ctype_digit($npsRaw) && (int) $npsRaw >= 0 && (int) $npsRaw <= 10) ? (int) $npsRaw : null;
        $comentario = str_clean($_POST['comentario'] ?? '', 1000) ?: null;

        $model->crear([
            'pedido_id'    => (int) $id,
            'usuario_id'   => (int) $this->usuario['id'],
            'satisfaccion' => $sat,
            'facilidad'    => $fac,
            'nps'          => $nps,
            'comentario'   => $comentario,
        ]);

        flash('portal_ok', '¡Gracias por tu opinión! Nos ayuda a mejorar el proceso.');
        $this->redirect('/portal/pedidos/' . (int) $id);
    }

    /* ------------------------------------------- Utilidades ---------- */

    private function guardCsrf(string $back): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró. Vuelve a intentarlo.');
            $this->redirect($back);
        }
    }
}
