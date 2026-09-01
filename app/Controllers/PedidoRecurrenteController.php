<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Pedido;
use App\Models\PedidoRecurrente;
use App\Models\Producto;

/**
 * Pedidos recurrentes del portal: el cliente programa repetir un conjunto de
 * productos cada N días. El envío del recordatorio lo hace el cron
 * (database/recordatorios_recurrentes.php); aquí solo se administra la
 * plantilla y se atiende el enlace de "agregar al carrito" del correo.
 */
class PedidoRecurrenteController extends PortalBaseController
{
    /** Partidas distintas que admite el carrito (igual que PedidoController). */
    private const MAX_PARTIDAS = 200;

    public function index(): void
    {
        Auth::authorize('pedidos.ver_propios');

        $model = new PedidoRecurrente();
        $plantillas = $model->porUsuario((int) $this->usuario['id']);
        foreach ($plantillas as &$p) {
            $p['items'] = $model->items((int) $p['id']);
        }
        unset($p);

        $this->render('portal/recurrentes', [
            'title'        => 'Pedidos recurrentes — Portal RYM',
            'active'       => 'recurrentes',
            'plantillas'   => $plantillas,
            'frecuencias'  => PedidoRecurrente::FRECUENCIAS,
        ]);
    }

    /** Programa una plantilla a partir de un pedido ya existente del cliente. */
    public function crear(string $pedidoId): void
    {
        $this->guardCsrf('/portal/pedidos/' . (int) $pedidoId);
        Auth::authorize('pedidos.crear');
        $this->requiereAprobacion();

        $pedidoModel = new Pedido();
        $pedido = $pedidoModel->find((int) $pedidoId);
        if (!$pedido || (int) $pedido['usuario_id'] !== (int) $this->usuario['id']) {
            flash('portal_error', 'No encontramos ese pedido.');
            $this->redirect('/portal/pedidos');
        }

        $items = $pedidoModel->items((int) $pedidoId);
        if (!$items) {
            flash('portal_error', 'Ese pedido no tiene productos para programar.');
            $this->redirect('/portal/pedidos/' . (int) $pedidoId);
        }

        $frecuencia = (int) ($_POST['frecuencia_dias'] ?? 0);
        if (!in_array($frecuencia, PedidoRecurrente::FRECUENCIAS, true)) {
            flash('portal_error', 'Selecciona cada cuántos días quieres el recordatorio.');
            $this->redirect('/portal/pedidos/' . (int) $pedidoId);
        }

        $nombre = str_clean($_POST['nombre'] ?? '', 120) ?: null;

        $model = new PedidoRecurrente();
        $model->crear((int) $this->usuario['id'], (int) $pedidoId, $items, $frecuencia, $nombre);

        flash('portal_ok', 'Listo. Te avisaremos cada ' . $frecuencia . ' días para repetir este pedido.');
        $this->redirect('/portal/recurrentes');
    }

    public function pausar(string $id): void
    {
        $this->guardCsrf('/portal/recurrentes');
        $plantilla = $this->deMiPropiedad((int) $id);
        (new PedidoRecurrente())->pausar((int) $id);
        flash('portal_ok', 'Recordatorio pausado. Ya no te avisaremos hasta que lo reactives.');
        $this->redirect('/portal/recurrentes');
    }

    public function reanudar(string $id): void
    {
        $this->guardCsrf('/portal/recurrentes');
        $plantilla = $this->deMiPropiedad((int) $id);
        (new PedidoRecurrente())->reanudar((int) $id);
        flash('portal_ok', 'Recordatorio reactivado.');
        $this->redirect('/portal/recurrentes');
    }

    public function eliminar(string $id): void
    {
        $this->guardCsrf('/portal/recurrentes');
        $plantilla = $this->deMiPropiedad((int) $id);
        (new PedidoRecurrente())->eliminar((int) $id);
        flash('portal_ok', 'Pedido recurrente eliminado.');
        $this->redirect('/portal/recurrentes');
    }

    public function actualizarFrecuencia(string $id): void
    {
        $this->guardCsrf('/portal/recurrentes');
        $plantilla = $this->deMiPropiedad((int) $id);

        $frecuencia = (int) ($_POST['frecuencia_dias'] ?? 0);
        if (!in_array($frecuencia, PedidoRecurrente::FRECUENCIAS, true)) {
            flash('portal_error', 'Frecuencia no válida.');
            $this->redirect('/portal/recurrentes');
        }

        (new PedidoRecurrente())->cambiarFrecuencia((int) $id, $frecuencia);
        flash('portal_ok', 'Frecuencia actualizada.');
        $this->redirect('/portal/recurrentes');
    }

    /**
     * Detalle de una plantilla: a dónde apunta el enlace del correo de
     * recordatorio. Deliberadamente GET y sin efectos secundarios (agregar al
     * carrito requiere el POST de pedirAhora(), con CSRF); así un enlace de
     * correo no puede usarse para mutar el carrito de otra pestaña abierta.
     */
    public function show(string $id): void
    {
        Auth::authorize('pedidos.ver_propios');
        $plantilla = $this->deMiPropiedad((int) $id);

        $this->render('portal/recurrente_detalle', [
            'title'      => ($plantilla['nombre'] ?: 'Pedido recurrente') . ' — Portal RYM',
            'active'     => 'recurrentes',
            'plantilla'  => $plantilla,
            'items'      => (new PedidoRecurrente())->items((int) $id),
        ]);
    }

    /**
     * Agrega al carrito los productos de la plantilla que sigan activos y
     * manda a armar el pedido. Mismo criterio que PedidoController::repetir().
     */
    public function pedirAhora(string $id): void
    {
        $this->guardCsrf('/portal/recurrentes/' . (int) $id);
        Auth::authorize('pedidos.crear');
        $this->requiereAprobacion();
        $plantilla = $this->deMiPropiedad((int) $id);

        $model = new PedidoRecurrente();
        $items = $model->items((int) $id);
        if (!$items) {
            flash('portal_error', 'Este pedido recurrente ya no tiene productos.');
            $this->redirect('/portal/recurrentes');
        }

        $productos = (new Producto())->porIds(array_map(static fn ($it) => (int) $it['producto_id'], $items));

        $agregados = 0;
        $omitidos  = 0;
        foreach ($items as $it) {
            $pid  = (int) $it['producto_id'];
            $prod = $productos[$pid] ?? null;
            $cabe = isset($_SESSION['carrito'][$pid]) || count($_SESSION['carrito'] ?? []) < self::MAX_PARTIDAS;
            if ($prod && (int) $prod['activo'] === 1 && $cabe) {
                $cant = min(9999, ($_SESSION['carrito'][$pid] ?? 0) + (int) $it['cantidad']);
                $_SESSION['carrito'][$pid] = $cant;
                $agregados++;
            } else {
                $omitidos++;
            }
        }

        if ($agregados === 0) {
            flash('portal_error', 'Los productos de este pedido recurrente ya no están disponibles.');
            $this->redirect('/portal/recurrentes');
        }

        $msg = 'Agregamos ' . $agregados . ' producto(s) a tu carrito. Revisa las cantidades y confirma.';
        if ($omitidos > 0) {
            $msg .= ' ' . $omitidos . ' producto(s) ya no está(n) disponible(s) y se omitió(eron).';
        }
        flash('portal_ok', $msg);
        $this->redirect('/portal/pedidos/nuevo');
    }

    /* --------------------------------- Utilidades -------------------- */

    /** Carga la plantilla verificando que pertenezca al cliente en sesión (anti-IDOR). */
    private function deMiPropiedad(int $id): array
    {
        $plantilla = (new PedidoRecurrente())->find($id);
        if (!$plantilla || (int) $plantilla['usuario_id'] !== (int) $this->usuario['id']) {
            flash('portal_error', 'No encontramos ese pedido recurrente.');
            $this->redirect('/portal/recurrentes');
        }
        return $plantilla;
    }

    private function guardCsrf(string $back): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró. Vuelve a intentarlo.');
            $this->redirect($back);
        }
    }
}
