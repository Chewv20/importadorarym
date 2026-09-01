<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Audit;
use App\Core\Mailer;
use App\Core\Upload;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\Pedido;

class CotizacionController extends BaseController
{
    public function index(): void
    {
        Auth::authorize('cotizaciones.ver');

        $model  = new Cotizacion();
        $scope  = $this->vendedorScope();
        $estado = in_array($_GET['estado'] ?? '', Cotizacion::ESTADOS, true) ? $_GET['estado'] : null;
        $pg     = $this->paginar($model->contar($estado, $scope), 20);

        $this->render('admin/cotizaciones', [
            'title'         => 'Cotizaciones — Panel RYM',
            'active'        => 'cotizaciones',
            'cotizaciones'  => $model->paginado($pg['perPage'], $pg['offset'], $estado, $scope),
            'estado'        => $estado,
            'estados'       => Cotizacion::ESTADOS,
            'soloAsignados' => $scope !== null,
            'page'          => $pg['page'],
            'pages'         => $pg['pages'],
        ]);
    }

    public function show(string $id): void
    {
        Auth::authorize('cotizaciones.ver');
        $this->verificarAcceso((int) $id);
        $model = new Cotizacion();
        $cot = $model->find((int) $id);
        if (!$cot) {
            flash('portal_error', 'Cotización no encontrada.');
            $this->redirect('/admin/cotizaciones');
        }
        $this->render('admin/cotizacion_detalle', [
            'title'  => 'Cotización ' . ($cot['folio'] ?? '') . ' — Panel RYM',
            'active' => 'cotizaciones',
            'cot'    => $cot,
            'items'  => $model->items((int) $id),
            'iva'    => (float) config('app.iva'),
        ]);
    }

    /** Guarda cantidades/precios y recalcula; opcionalmente envía al cliente. */
    public function guardar(string $id): void
    {
        Auth::authorize('cotizaciones.gestionar');
        $this->guard($id);
        $this->verificarAcceso((int) $id);

        $model = new Cotizacion();
        $cot = $model->find((int) $id);
        if (!$cot) {
            $this->redirect('/admin/cotizaciones');
        }

        $model->actualizarLineas((int) $id, $_POST['items'] ?? []);
        $tot = $model->recalcular((int) $id, (float) config('app.iva'));

        if (isset($_POST['enviar'])) {
            if (!$model->items((int) $id)) {
                flash('portal_error', 'Agrega al menos una partida antes de enviar.');
                $this->redirect('/admin/cotizaciones/' . (int) $id);
            }
            $model->marcarEnviada((int) $id);
            Mailer::enviar($cot['email'], 'Tu cotización ' . ($cot['folio'] ?? '') . ' — Importadora RYM', 'cotizacion_lista', [
                'nombre'  => $cot['nombre'],
                'folio'   => $cot['folio'] ?? '',
                'total'   => $tot['total'],
                'verUrl'  => rtrim((string) config('app.url'), '/') . '/portal/cotizaciones/' . (int) $id,
            ]);
            Audit::cambio('actualizar', 'cotizacion', (int) $id, 'Envió la cotización ' . ($cot['folio'] ?? $id) . ' al cliente');
            flash('portal_ok', 'Cotización enviada al cliente.');
        } else {
            Audit::cambio('actualizar', 'cotizacion', (int) $id, 'Actualizó la cotización ' . ($cot['folio'] ?? $id));
            flash('portal_ok', 'Cotización guardada.');
        }
        $this->redirect('/admin/cotizaciones/' . (int) $id);
    }

    public function agregarItem(string $id): void
    {
        Auth::authorize('cotizaciones.gestionar');
        $this->guard($id);
        $this->verificarAcceso((int) $id);

        $model = new Cotizacion();
        $pid  = (int) ($_POST['producto_id'] ?? 0);
        $cant = max(1, (int) ($_POST['cantidad'] ?? 1));
        $prod = (new Producto())->find($pid);
        if ($prod) {
            $model->agregarItems((int) $id, [[
                'producto_id'     => $pid,
                'descripcion'     => $prod['nombre'],
                'cantidad'        => $cant,
                'precio_unitario' => (float) ($prod['precio'] ?? 0),
            ]]);
            $model->recalcular((int) $id, (float) config('app.iva'));
        }
        $this->redirect('/admin/cotizaciones/' . (int) $id);
    }

    public function quitarItem(string $id, string $itemId): void
    {
        Auth::authorize('cotizaciones.gestionar');
        $this->guard($id);
        $this->verificarAcceso((int) $id);
        $model = new Cotizacion();
        $model->eliminarItem((int) $itemId, (int) $id);
        $model->recalcular((int) $id, (float) config('app.iva'));
        $this->redirect('/admin/cotizaciones/' . (int) $id);
    }

    public function updateEstado(string $id): void
    {
        Auth::authorize('cotizaciones.gestionar');
        $this->guard($id);
        $this->verificarAcceso((int) $id);
        $estado = $_POST['estado'] ?? '';
        if (in_array($estado, Cotizacion::ESTADOS, true)) {
            (new Cotizacion())->actualizarEstado((int) $id, $estado);
            Audit::cambio('actualizar', 'cotizacion', (int) $id, 'Cambió el estado de la cotización a "' . $estado . '"');
            flash('portal_ok', 'Estado actualizado.');
        }
        $this->redirect('/admin/cotizaciones/' . (int) $id);
    }

    /** Convierte una cotización aprobada en pedido. */
    public function convertir(string $id): void
    {
        Auth::authorize('cotizaciones.gestionar');
        $this->guard($id);
        $this->verificarAcceso((int) $id);

        $model = new Cotizacion();
        $cot = $model->find((int) $id);
        if (!$cot) {
            $this->redirect('/admin/cotizaciones');
        }
        if ($cot['estado'] !== 'aprobada') {
            flash('portal_error', 'Solo puedes convertir cotizaciones aprobadas.');
            $this->redirect('/admin/cotizaciones/' . (int) $id);
        }
        if (empty($cot['usuario_id'])) {
            flash('portal_error', 'Esta cotización no tiene un cliente del portal para levantar el pedido.');
            $this->redirect('/admin/cotizaciones/' . (int) $id);
        }

        $items = [];
        foreach ($model->items((int) $id) as $it) {
            $items[] = [
                'producto_id'     => $it['producto_id'],
                'sku'             => null,
                'nombre'          => $it['descripcion'],
                'cantidad'        => (int) $it['cantidad'],
                'precio_unitario' => (float) $it['precio_unitario'],
            ];
        }
        if (!$items) {
            flash('portal_error', 'La cotización no tiene partidas.');
            $this->redirect('/admin/cotizaciones/' . (int) $id);
        }

        $pedidoId = (new Pedido())->crear(
            (int) $cot['usuario_id'],
            $items,
            'Generado desde la cotización ' . ($cot['folio'] ?? $id),
            'enviado',
            $cot['folio'] ?? null
        );
        $model->vincularPedido((int) $id, $pedidoId);
        Audit::cambio('crear', 'pedido', $pedidoId, 'Convirtió la cotización ' . ($cot['folio'] ?? $id) . ' en pedido');

        flash('portal_ok', 'Cotización convertida en pedido.');
        $this->redirect('/admin/pedidos/' . $pedidoId);
    }

    /** Descarga el logo adjunto (stream desde storage/, nunca accesible por URL pública). */
    public function descargarLogo(string $id): void
    {
        Auth::authorize('cotizaciones.ver');
        $this->verificarAcceso((int) $id);
        $cot = (new Cotizacion())->find((int) $id);
        $ruta = $cot ? Upload::documentoRuta('cotizaciones_logos', $cot['logo_archivo']) : null;
        if (!$ruta) {
            flash('portal_error', 'El logo no está disponible.');
            $this->redirect('/admin/cotizaciones/' . (int) $id);
        }
        $ext = strtolower((string) pathinfo($ruta, PATHINFO_EXTENSION));
        $mime = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'][$ext] ?? 'application/octet-stream';
        $this->streamFile($ruta, 'logo-' . slugify($cot['nombre']) . '.' . $ext, $mime);
    }

    public function imprimir(string $id): void
    {
        Auth::authorize('cotizaciones.ver');
        $this->verificarAcceso((int) $id);
        $model = new Cotizacion();
        $cot = $model->find((int) $id);
        if (!$cot) {
            $this->redirect('/admin/cotizaciones');
        }
        $this->view('cotizacion_imprimible', [
            'cot'   => $cot,
            'items' => $model->items((int) $id),
        ], null);
    }

    private function guard(string $id): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/cotizaciones/' . (int) $id);
        }
    }

    /**
     * Vendedor con alcance limitado: solo puede tocar cotizaciones de sus
     * clientes. Las públicas (sin cliente) no pertenecen a ningún vendedor.
     */
    private function verificarAcceso(int $id): void
    {
        $scope = $this->vendedorScope();
        if ($scope !== null && (new Cotizacion())->vendedorDe($id) !== $scope) {
            flash('portal_error', 'No tienes acceso a esa cotización.');
            $this->redirect('/admin/cotizaciones');
        }
    }
}
