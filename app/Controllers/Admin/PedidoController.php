<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Upload;
use App\Core\Xlsx;
use App\Core\SaeExport;
use App\Models\Pedido;
use App\Models\PedidoFactura;
use App\Models\SaeSerieConsecutivo;

class PedidoController extends BaseController
{
    public function index(): void
    {
        Auth::authorize('pedidos.ver_todos');

        $model    = new Pedido();
        $scope    = $this->vendedorScope();
        $estado   = in_array($_GET['estado'] ?? '', Pedido::ESTADOS, true) ? $_GET['estado'] : null;
        $busqueda = str_clean($_GET['q'] ?? '', 60) ?: null;
        $pg       = $this->paginar($model->contarAdmin($estado, $busqueda, $scope), 20);

        $this->render('admin/pedidos', [
            'title'         => 'Pedidos — Panel RYM',
            'active'        => 'pedidos',
            'pedidos'       => $model->adminPaginado($pg['perPage'], $pg['offset'], $estado, $busqueda, $scope),
            'estado'        => $estado,
            'busqueda'      => $busqueda,
            'estados'       => Pedido::ESTADOS,
            'soloAsignados' => $scope !== null,
            'page'          => $pg['page'],
            'pages'         => $pg['pages'],
        ]);
    }

    public function show(string $id): void
    {
        Auth::authorize('pedidos.ver_todos');
        $this->verificarAcceso((int) $id);

        $model  = new Pedido();
        $pedido = $model->findConCliente((int) $id);
        if (!$pedido) {
            flash('portal_error', 'Pedido no encontrado.');
            $this->redirect('/admin/pedidos');
        }

        // Facturas de todos los envíos del pedido, agrupadas por envío (un envío
        // puede tener más de una, ej. cancelada + reemitida).
        $facturasPorEnvio = [];
        foreach ((new PedidoFactura())->paraPedido((int) $pedido['id']) as $f) {
            $facturasPorEnvio[(int) $f['envio_id']][] = $f;
        }

        $this->render('admin/pedido_detalle', [
            'title'              => 'Pedido ' . $pedido['folio'] . ' — Panel RYM',
            'active'             => 'pedidos',
            'pedido'             => $pedido,
            'items'              => $model->items((int) $pedido['id']),
            'estados'            => Pedido::ESTADOS,
            'flujo'              => Pedido::FLUJO,
            'exportaciones'      => $model->exportaciones((int) $pedido['id']),
            'envios'             => $model->envios((int) $pedido['id']),
            'partidasPendientes' => $model->contarPendientesSae((int) $pedido['id']),
            'frecuencias'        => \App\Models\PedidoRecurrente::FRECUENCIAS,
            'repartidores'       => (new \App\Models\Usuario())->repartidores(),
            'zonas'              => (new \App\Models\Zona())->activas(),
            'facturasPorEnvio'   => $facturasPorEnvio,
        ]);
    }

    /** POST: asigna (o quita) repartidor/zona de un envío ya exportado. */
    public function asignarRepartidor(string $envioId): void
    {
        Auth::authorize('pedidos.actualizar_estado');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/pedidos');
        }

        $model  = new Pedido();
        $envio  = $model->envioConDetalle((int) $envioId);
        if (!$envio) {
            flash('portal_error', 'Envío no encontrado.');
            $this->redirect('/admin/pedidos');
        }
        $this->verificarAcceso((int) $envio['pedido_id']);

        $repartidorId = (int) ($_POST['repartidor_id'] ?? 0) ?: null;
        $zonaId       = (int) ($_POST['zona_id'] ?? 0) ?: null;
        $notas        = str_clean($_POST['notas'] ?? '', 500) ?: null;

        $model->asignarRepartidor((int) $envioId, $repartidorId, $zonaId, $notas);
        \App\Core\Audit::cambio('actualizar', 'pedido', (int) $envio['pedido_id'],
            'Actualizó el repartidor/zona del envío #' . (int) $envioId . ' del pedido ' . ($envio['folio'] ?? ''));
        flash('portal_ok', 'Envío actualizado.');
        $this->redirect('/admin/pedidos/' . (int) $envio['pedido_id']);
    }

    /** POST: sube el PDF+XML de una factura para un envío ya exportado. */
    public function subirFactura(string $envioId): void
    {
        Auth::authorize('pedidos.sincronizar_erp');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/pedidos');
        }

        $model = new Pedido();
        $envio = $model->envioConDetalle((int) $envioId);
        if (!$envio) {
            flash('portal_error', 'Envío no encontrado.');
            $this->redirect('/admin/pedidos');
        }
        $this->verificarAcceso((int) $envio['pedido_id']);
        $back = '/admin/pedidos/' . (int) $envio['pedido_id'];

        $pdf = Upload::facturaPdf($_FILES['archivo_pdf'] ?? null, 'facturas');
        $xml = Upload::facturaXml($_FILES['archivo_xml'] ?? null, 'facturas');

        $errores = array_filter([$pdf['error'], $xml['error']]);
        if (!$errores && ($pdf['nombre'] === null || $xml['nombre'] === null)) {
            $errores[] = 'Sube el PDF y el XML de la factura, los dos son obligatorios.';
        }
        if ($errores) {
            // No dejar huérfano el archivo que sí se haya subido.
            Upload::borrarDocumento('facturas', $pdf['nombre']);
            Upload::borrarDocumento('facturas', $xml['nombre']);
            flash('portal_error', implode(' ', array_unique($errores)));
            $this->redirect($back);
        }

        $facturaId = (new PedidoFactura())->crear([
            'envio_id'    => (int) $envioId,
            'archivo_pdf' => $pdf['nombre'],
            'archivo_xml' => $xml['nombre'],
            'subido_por'  => (int) $this->usuario['id'],
        ]);
        \App\Core\Audit::cambio('crear', 'pedido', (int) $envio['pedido_id'],
            'Subió una factura para el envío #' . (int) $envioId . ' del pedido ' . ($envio['folio'] ?? ''));

        if (!empty($envio['cliente_email'])) {
            \App\Core\Mailer::enviar($envio['cliente_email'], 'Factura de tu pedido ' . ($envio['folio'] ?? ''), 'factura_nueva', [
                'nombre' => $envio['cliente_nombre'] ?? '',
                'folio'  => $envio['folio'] ?? '',
                'verUrl' => rtrim((string) config('app.url'), '/') . '/portal/pedidos/' . (int) $envio['pedido_id'] . '#envio-' . (int) $envioId,
            ]);
        }

        flash('portal_ok', 'Factura subida. Se notificó al cliente.');
        $this->redirect($back);
    }

    /** GET: reconstruye y vuelve a descargar el Excel SAE de un envío ya exportado. */
    public function redescargarEnvio(string $envioId): void
    {
        Auth::authorize('pedidos.sincronizar_erp');

        $model = new Pedido();
        $envio = $model->envioConDetalle((int) $envioId);
        if (!$envio || empty($envio['sae_serie']) || $envio['sae_consecutivo'] === null) {
            flash('portal_error', 'Este envío no tiene una exportación a SAE que volver a descargar.');
            $this->redirect('/admin/pedidos');
        }
        $this->verificarAcceso((int) $envio['pedido_id']);

        $pedido = $model->findConCliente((int) $envio['pedido_id']);
        $items  = $model->itemsDeEnvio((int) $envioId);
        if (!$pedido || !$items) {
            flash('portal_error', 'No se encontraron las partidas de este envío.');
            $this->redirect('/admin/pedidos/' . (int) $envio['pedido_id']);
        }

        $clave = SaeExport::claveDocumento((int) $envio['sae_consecutivo'], $envio['sae_serie']);
        $res   = SaeExport::filasDePedido($pedido, $items, $clave, (string) ($envio['fecha_entrega'] ?? ''));

        \App\Core\Audit::cambio('actualizar', 'pedido', (int) $envio['pedido_id'],
            'Volvió a descargar la exportación SAE del envío #' . (int) $envioId . ' del pedido ' . ($envio['folio'] ?? ''));

        $this->descargar('pedido-' . $pedido['folio'] . '-envio' . (int) $envioId . '.xlsx', $res['filas']);
    }

    /** GET: descarga el PDF o XML de una factura (?tipo=pdf|xml). */
    public function descargarFactura(string $facturaId): void
    {
        Auth::authorize('pedidos.sincronizar_erp');
        $factura = (new PedidoFactura())->conDetalle((int) $facturaId);
        if (!$factura) {
            flash('portal_error', 'Factura no encontrada.');
            $this->redirect('/admin/pedidos');
        }
        $this->verificarAcceso((int) $factura['pedido_id']);
        $this->enviarArchivoFactura($factura);
    }

    /** POST: elimina una factura (y sus archivos en disco). */
    public function eliminarFactura(string $facturaId): void
    {
        Auth::authorize('pedidos.sincronizar_erp');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/pedidos');
        }

        $model   = new PedidoFactura();
        $factura = $model->conDetalle((int) $facturaId);
        if (!$factura) {
            flash('portal_error', 'Factura no encontrada.');
            $this->redirect('/admin/pedidos');
        }
        $this->verificarAcceso((int) $factura['pedido_id']);

        Upload::borrarDocumento('facturas', $factura['archivo_pdf']);
        Upload::borrarDocumento('facturas', $factura['archivo_xml']);
        $model->eliminar((int) $facturaId);
        \App\Core\Audit::cambio('eliminar', 'pedido', (int) $factura['pedido_id'],
            'Eliminó una factura del envío #' . (int) $factura['envio_id'] . ' del pedido ' . ($factura['folio'] ?? ''));

        flash('portal_ok', 'Factura eliminada.');
        $this->redirect('/admin/pedidos/' . (int) $factura['pedido_id']);
    }

    /** Sirve el archivo (PDF o XML según ?tipo=) de una factura ya validada. */
    private function enviarArchivoFactura(array $factura): void
    {
        $tipo = ($_GET['tipo'] ?? '') === 'xml' ? 'xml' : 'pdf';
        $archivoNombre = $tipo === 'xml' ? $factura['archivo_xml'] : $factura['archivo_pdf'];
        $ruta = Upload::documentoRuta('facturas', $archivoNombre);
        if (!$ruta) {
            flash('portal_error', 'El archivo no está disponible.');
            $this->redirect('/admin/pedidos/' . (int) $factura['pedido_id']);
        }
        $nombre = 'factura-' . slugify((string) ($factura['folio'] ?? $factura['id'])) . '.' . $tipo;
        $this->streamFile($ruta, $nombre, $tipo === 'xml' ? 'application/xml' : 'application/pdf');
    }

    public function updateEstado(string $id): void
    {
        Auth::authorize('pedidos.actualizar_estado');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/pedidos/' . (int) $id);
        }
        $this->verificarAcceso((int) $id);

        $estado = $_POST['estado'] ?? '';
        if (in_array($estado, Pedido::ESTADOS, true)) {
            (new Pedido())->cambiarEstado((int) $id, $estado);
            \App\Core\Audit::cambio('actualizar', 'pedido', (int) $id, 'Cambió el estado del pedido #' . (int) $id . ' a "' . $estado . '"');
            $this->notificarCliente((int) $id, $estado);
            flash('portal_ok', 'Estado del pedido actualizado. Se notificó al cliente.');
        }
        $this->redirect('/admin/pedidos/' . (int) $id);
    }

    public function sincronizarErp(string $id): void
    {
        Auth::authorize('pedidos.sincronizar_erp');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/pedidos/' . (int) $id);
        }
        $this->verificarAcceso((int) $id);

        $folio = str_clean($_POST['erp_folio'] ?? '', 60);
        if ($folio === '') {
            flash('portal_error', 'Captura el folio del ERP.');
            $this->redirect('/admin/pedidos/' . (int) $id);
        }

        (new Pedido())->marcarSincronizado((int) $id, $folio);
        \App\Core\Audit::cambio('actualizar', 'pedido', (int) $id, 'Sincronizó el pedido #' . (int) $id . ' al ERP (folio ' . $folio . ')');
        $this->notificarCliente((int) $id, 'sincronizado');
        flash('portal_ok', 'Pedido marcado como sincronizado con el ERP (folio ' . $folio . ').');
        $this->redirect('/admin/pedidos/' . (int) $id);
    }

    /** Envía al cliente un correo con el nuevo estado de su pedido (no bloquea si falla). */
    private function notificarCliente(int $pedidoId, string $estado): void
    {
        if ($estado === 'borrador') {
            return; // sin cliente que avisar en borrador
        }
        $pedido = (new Pedido())->findConCliente($pedidoId);
        if (!$pedido || empty($pedido['cliente_email'])) {
            return;
        }
        $labels = [
            'enviado'      => 'recibido',
            'en_proceso'   => 'en preparación',
            'parcial'      => 'parcialmente procesado',
            'sincronizado' => 'procesado',
            'cancelado'    => 'cancelado',
        ];
        $label = $labels[$estado] ?? $estado;
        \App\Core\Mailer::enviar(
            $pedido['cliente_email'],
            'Tu pedido ' . ($pedido['folio'] ?? '') . ': ' . $label,
            'pedido_estado',
            [
                'nombre' => $pedido['cliente_nombre'] ?? '',
                'folio'  => $pedido['folio'] ?? '',
                'estado' => $estado,
                'label'  => $label,
                'verUrl' => rtrim((string) config('app.url'), '/') . '/portal/pedidos/' . $pedidoId,
            ]
        );
    }

    /* --------------------------------- Exportación a Aspel SAE ------- */

    /** GET: valida claves SAE y muestra la selección de partidas previa a exportar. */
    public function exportarPedido(string $id): void
    {
        Auth::authorize('pedidos.sincronizar_erp');
        $this->verificarAcceso((int) $id);

        $model  = new Pedido();
        $pedido = $model->findConCliente((int) $id);
        if (!$pedido) {
            flash('portal_error', 'Pedido no encontrado.');
            $this->redirect('/admin/pedidos');
        }

        $items = $model->itemsPendientesSae((int) $id);
        if (!$items) {
            flash('portal_error', 'Este pedido ya no tiene partidas pendientes de exportar.');
            $this->redirect('/admin/pedidos/' . (int) $id);
        }

        // Error del cliente (sin clave SAE): bloquea todo el pedido, no hay nada
        // exportable. Errores por artículo (sin clave/esquema) solo bloquean esa
        // partida — se marcan por fila en la vista, no todo el pedido.
        $erroresCliente = SaeExport::filasDePedido($pedido, [])['errores'];
        if ($erroresCliente) {
            flash('portal_error', 'Completa antes de exportar: ' . implode(' ', array_unique($erroresCliente)));
            $this->redirect('/admin/pedidos/' . (int) $id);
        }

        foreach ($items as &$it) {
            $it['errores'] = SaeExport::filasDePedido($pedido, [$it])['errores'];
        }
        unset($it);

        $this->render('admin/pedido_exportar', [
            'title'  => 'Exportar a SAE — ' . $pedido['folio'],
            'active' => 'pedidos',
            'pedido' => $pedido,
            'items'  => $items,
        ]);
    }

    /** POST: guarda los precios capturados y descarga el Excel de las partidas elegidas. */
    public function procesarExportacion(string $id): void
    {
        Auth::authorize('pedidos.sincronizar_erp');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/pedidos/' . (int) $id);
        }
        $this->verificarAcceso((int) $id);

        $model  = new Pedido();
        $pedido = $model->findConCliente((int) $id);
        if (!$pedido) {
            flash('portal_error', 'Pedido no encontrado.');
            $this->redirect('/admin/pedidos');
        }

        $seleccionados = array_map('intval', $_POST['item_id'] ?? []);
        if (!$seleccionados) {
            flash('portal_error', 'Selecciona al menos una partida para exportar.');
            $this->redirect('/admin/pedidos/' . (int) $id . '/exportar');
        }

        $back = '/admin/pedidos/' . (int) $id . '/exportar';
        $fechaEntrega = $this->validarFechaEntrega(trim((string) ($_POST['fecha_entrega'] ?? '')), $back);
        $serie = SaeExport::serieDelDia($fechaEntrega);
        $consecutivo = (new SaeSerieConsecutivo())->siguienteConsecutivo($serie);

        $precios = [];
        foreach (($_POST['precio'] ?? []) as $itemId => $val) {
            $precios[(int) $itemId] = max(0, (float) str_replace(',', '', (string) $val));
        }
        $model->guardarPrecios((int) $id, $precios);

        // Solo las partidas elegidas y que sigan pendientes (por si la pantalla
        // quedó abierta mientras alguien más ya exportó alguna de ellas).
        $pendientes = $model->itemsPendientesSae((int) $id);
        $items = array_values(array_filter(
            $pendientes,
            static fn ($it) => in_array((int) $it['id'], $seleccionados, true)
        ));
        if (!$items) {
            flash('portal_error', 'Esas partidas ya no están pendientes de exportar.');
            $this->redirect('/admin/pedidos/' . (int) $id);
        }

        $res = SaeExport::filasDePedido($pedido, $items, SaeExport::claveDocumento($consecutivo, $serie), $fechaEntrega);
        if ($res['errores']) {
            flash('portal_error', 'No se pudo exportar. ' . implode(' ', array_unique($res['errores'])));
            $this->redirect('/admin/pedidos/' . (int) $id . '/exportar');
        }

        try {
            $model->crearEnvio((int) $id, array_column($items, 'id'), (int) $this->usuario['id'], $fechaEntrega, $serie, $consecutivo);
        } catch (\RuntimeException $e) {
            flash('portal_error', $e->getMessage());
            $this->redirect('/admin/pedidos/' . (int) $id);
        }
        $model->registrarExportacion((int) $id, 'individual', (int) $this->usuario['id'], $this->usuario['email']);
        \App\Core\Audit::cambio('actualizar', 'pedido', (int) $id,
            'Exportó a SAE ' . count($items) . ' partida(s) del pedido ' . ($pedido['folio'] ?? ('#' . $id)));

        $this->descargar('pedido-' . $pedido['folio'] . '.xlsx', $res['filas']);
    }

    /** POST: programa un recordatorio interno para las partidas aún pendientes del pedido. */
    public function programarRecordatorio(string $id): void
    {
        Auth::authorize('pedidos.sincronizar_erp');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/pedidos/' . (int) $id);
        }
        $this->verificarAcceso((int) $id);

        $dias = (int) ($_POST['dias'] ?? 0);
        if (!in_array($dias, \App\Models\PedidoRecurrente::FRECUENCIAS, true)) {
            flash('portal_error', 'Frecuencia de recordatorio no válida.');
            $this->redirect('/admin/pedidos/' . (int) $id);
        }

        (new Pedido())->establecerRecordatorio((int) $id, $dias);
        flash('portal_ok', 'Se te recordará en ' . $dias . ' días sobre las partidas pendientes de este pedido.');
        $this->redirect('/admin/pedidos/' . (int) $id);
    }

    /** GET: captura de precios de las partidas pendientes de todos los pedidos, antes de exportar el lote. */
    public function exportarLote(): void
    {
        Auth::authorize('pedidos.sincronizar_erp');
        $this->bloquearLoteSiVendedor();

        $model = new Pedido();
        $pendientes = $model->pendientesSae();
        if (!$pendientes) {
            flash('portal_error', 'No hay pedidos con partidas pendientes de sincronizar.');
            $this->redirect('/admin/pedidos');
        }

        $itemsPorPedido = $model->itemsPendientesSaePorPedidos(array_column($pendientes, 'id'));

        $exportables = [];
        $bloqueados  = [];
        foreach ($pendientes as $pedido) {
            // Error de cliente (sin clave SAE): no hay nada exportable de este
            // pedido, se excluye del lote entero. Errores por artículo NO excluyen
            // el pedido completo — solo esa partida, igual que en la exportación
            // individual (se marcan por fila, seleccionable el resto).
            $erroresCliente = SaeExport::filasDePedido($pedido, [])['errores'];
            if ($erroresCliente) {
                $bloqueados[] = ['folio' => $pedido['folio'], 'motivos' => array_unique($erroresCliente)];
                continue;
            }
            $items = $itemsPorPedido[(int) $pedido['id']] ?? [];
            foreach ($items as &$it) {
                $it['errores'] = SaeExport::filasDePedido($pedido, [$it])['errores'];
            }
            unset($it);
            $exportables[] = ['pedido' => $pedido, 'items' => $items];
        }

        $this->render('admin/pedidos_exportar', [
            'title'       => 'Exportar pendientes a SAE — Panel RYM',
            'active'      => 'pedidos',
            'exportables' => $exportables,
            'bloqueados'  => $bloqueados,
        ]);
    }

    /** POST: guarda precios de todo el lote y descarga un Excel con las partidas pendientes exportables. */
    public function procesarLote(): void
    {
        Auth::authorize('pedidos.sincronizar_erp');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/pedidos');
        }
        $this->bloquearLoteSiVendedor();

        $model = new Pedido();
        $serieModel = new SaeSerieConsecutivo();
        $fechas = $_POST['fecha_entrega'] ?? [];

        $seleccionados = array_map('intval', $_POST['item_id'] ?? []);

        $precios = [];
        foreach (($_POST['precio'] ?? []) as $itemId => $val) {
            $precios[(int) $itemId] = max(0, (float) str_replace(',', '', (string) $val));
        }
        $model->guardarPreciosItems($precios);

        $pendientesLote = $model->pendientesSae();
        $itemsPorPedido = $model->itemsPendientesSaePorPedidos(array_column($pendientesLote, 'id'));

        $filas = [];
        $exportados = []; // pedido_id => ['items' => [...], 'fecha' => ..., 'serie' => ..., 'consecutivo' => ...]
        $saltados = [];   // folio => motivo (no bloquea el resto del lote)
        foreach ($pendientesLote as $pedido) {
            $pendientes = $itemsPorPedido[(int) $pedido['id']] ?? [];
            $items = array_values(array_filter(
                $pendientes,
                static fn ($it) => in_array((int) $it['id'], $seleccionados, true)
            ));
            if (!$items) {
                continue; // nada seleccionado de este pedido
            }
            if (SaeExport::filasDePedido($pedido, $items)['errores']) {
                continue; // por si alguna partida seleccionada dejó de ser exportable entre tanto
            }

            // Cada pedido del lote captura su propia fecha de entrega: pueden
            // caer en días distintos, cada uno con su propia serie/consecutivo.
            $fecha = trim((string) ($fechas[(int) $pedido['id']] ?? ''));
            if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || strtotime($fecha) === false) {
                $saltados[] = $pedido['folio'] . ' (falta la fecha de entrega)';
                continue;
            }
            $serie = SaeExport::serieDelDia($fecha);
            if ($serie === null) {
                $saltados[] = $pedido['folio'] . ' (la fecha de entrega no puede ser sábado ni domingo)';
                continue;
            }

            $consecutivo = $serieModel->siguienteConsecutivo($serie);
            $res = SaeExport::filasDePedido($pedido, $items, SaeExport::claveDocumento($consecutivo, $serie), $fecha);
            $exportados[(int) $pedido['id']] = [
                'folio'       => $pedido['folio'],
                'items'       => array_column($items, 'id'),
                'fecha'       => $fecha,
                'serie'       => $serie,
                'consecutivo' => $consecutivo,
                'filas'       => $res['filas'],
            ];
        }

        if (!$exportados) {
            flash('portal_error', 'No hay pedidos listos para exportar (revisa las claves de SAE y la fecha de entrega).');
            $this->redirect('/admin/pedidos');
        }

        // La fila del Excel solo se incluye si crearEnvio() confirma que las
        // partidas seguían libres — si otra persona ya las exportó entre la
        // lectura y este punto, se salta ese pedido en vez de descargar un
        // Excel con partidas que la base de datos no marcó como exportadas.
        $filas = [];
        $creados = 0;
        foreach ($exportados as $pid => $info) {
            try {
                $model->crearEnvio($pid, $info['items'], (int) $this->usuario['id'], $info['fecha'], $info['serie'], $info['consecutivo']);
            } catch (\RuntimeException $e) {
                $saltados[] = $info['folio'] . ' (' . $e->getMessage() . ')';
                continue;
            }
            $model->registrarExportacion($pid, 'lote', (int) $this->usuario['id'], $this->usuario['email']);
            $filas = array_merge($filas, $info['filas']);
            $creados++;
        }
        \App\Core\Audit::cambio('actualizar', 'pedido', null, 'Exportó a SAE ' . $creados . ' pedido(s) por lote');

        if (!$creados) {
            flash('portal_error', 'No se pudo completar la exportación: ' . implode('; ', $saltados));
            $this->redirect('/admin/pedidos');
        }

        if ($saltados) {
            flash('portal_error', 'Algunos pedidos no se incluyeron: ' . implode('; ', $saltados));
        }

        $this->descargar('pedidos-sae-' . date('Ymd') . '.xlsx', $filas);
    }

    /** Fecha de entrega válida (Y-m-d) y en día hábil L-V; si no, redirige con error. Fase 7.4. */
    private function validarFechaEntrega(string $fecha, string $back): string
    {
        if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || strtotime($fecha) === false) {
            flash('portal_error', 'Captura la fecha de entrega.');
            $this->redirect($back);
        }
        if (SaeExport::serieDelDia($fecha) === null) {
            flash('portal_error', 'La fecha de entrega no puede caer en sábado ni domingo.');
            $this->redirect($back);
        }
        return $fecha;
    }

    /** Vendedor con alcance limitado: solo puede tocar pedidos de sus clientes. */
    private function verificarAcceso(int $id): void
    {
        $scope = $this->vendedorScope();
        if ($scope !== null && (new Pedido())->vendedorDe($id) !== $scope) {
            flash('portal_error', 'No tienes acceso a ese pedido.');
            $this->redirect('/admin/pedidos');
        }
    }

    /** La exportación por lote a SAE es global; se reserva a administración. */
    private function bloquearLoteSiVendedor(): void
    {
        if ($this->vendedorScope() !== null) {
            flash('portal_error', 'La exportación por lote a SAE la realiza administración. Exporta tus pedidos de forma individual.');
            $this->redirect('/admin/pedidos');
        }
    }

    private function descargar(string $filename, array $filas): void
    {
        $bin = Xlsx::crear(SaeExport::HEADERS, $filas, 'Pedidos');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: no-store');
        echo $bin;
        exit;
    }
}
