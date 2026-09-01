<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Pedido;

/**
 * Interfaz mínima de reparto (Fase 7.7): pensada para el celular del
 * repartidor, fuera del panel completo — dos botones grandes ("Salió a
 * ruta" / "Entregado") por cada envío que tiene asignado.
 */
class RepartoController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            flash('portal_error', 'Inicia sesión para acceder a reparto.');
            header('Location: ' . base_url('/portal/login'));
            exit;
        }
    }

    public function index(): void
    {
        Auth::authorize('pedidos.tracking');
        $usuario = Auth::user();

        $model = new Pedido();
        $this->view('reparto/index', [
            'title'      => 'Reparto — Importadora RYM',
            'robots'     => 'noindex, nofollow',
            'usuario'    => $usuario,
            'pendientes' => $model->enviosDeRepartidor((int) $usuario['id']),
            'entregados' => $model->enviosEntregadosDeRepartidor((int) $usuario['id']),
        ], 'layouts/reparto');
    }

    public function enRuta(string $envioId): void
    {
        Auth::authorize('pedidos.tracking');
        $this->guardCsrf();
        $envio = $this->envioDelRepartidor((int) $envioId);

        // El <input type="datetime-local"> manda "AAAA-MM-DDTHH:MM"; MySQL espera espacio, no "T".
        $eta = str_replace('T', ' ', str_clean($_POST['eta'] ?? '', 20));
        (new Pedido())->marcarEnRuta((int) $envioId, $eta !== '' ? $eta : null);
        \App\Core\Audit::cambio('actualizar', 'pedido', (int) $envio['pedido_id'], 'Marcó "en ruta" el envío del pedido ' . ($envio['folio'] ?? ''));

        $this->avisarCliente($envio, 'en_ruta');
        flash('portal_ok', 'Marcado como en ruta.');
        $this->redirect('/reparto');
    }

    public function entregado(string $envioId): void
    {
        Auth::authorize('pedidos.tracking');
        $this->guardCsrf();
        $envio = $this->envioDelRepartidor((int) $envioId);

        (new Pedido())->marcarEntregado((int) $envioId);
        \App\Core\Audit::cambio('actualizar', 'pedido', (int) $envio['pedido_id'], 'Marcó "entregado" el envío del pedido ' . ($envio['folio'] ?? ''));

        $this->avisarCliente($envio, 'entregado');
        flash('portal_ok', 'Marcado como entregado. ¡Gracias!');
        // Marcador para el sonido/vibración de confirmación (se lee y se
        // descarta en la siguiente carga de /reparto, como cualquier flash).
        flash('reparto_entregado_ok', '1');
        $this->redirect('/reparto');
    }

    /** Envío + verificación de que pertenece al repartidor autenticado (evita que uno toque los envíos de otro). */
    private function envioDelRepartidor(int $envioId): array
    {
        $model = new Pedido();
        $envio = $model->envioConDetalle($envioId);
        $usuario = Auth::user();
        if (!$envio || (int) ($envio['repartidor_id'] ?? 0) !== (int) $usuario['id']) {
            flash('portal_error', 'No tienes ese envío asignado.');
            $this->redirect('/reparto');
        }
        return $envio;
    }

    private function guardCsrf(): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/reparto');
        }
    }

    /** Avisa al cliente del evento de su envío (no bloquea si falla). */
    private function avisarCliente(array $envio, string $evento): void
    {
        if (empty($envio['cliente_email'])) {
            return;
        }
        $vista = $evento === 'entregado' ? 'pedido_entregado' : 'pedido_en_ruta';
        $asunto = $evento === 'entregado'
            ? 'Tu pedido ' . ($envio['folio'] ?? '') . ' fue entregado'
            : 'Tu pedido ' . ($envio['folio'] ?? '') . ' va en camino';
        $verUrl = rtrim((string) config('app.url'), '/') . '/portal/pedidos/' . (int) $envio['pedido_id'];
        \App\Core\Mailer::enviar($envio['cliente_email'], $asunto, $vista, [
            'nombre' => $envio['cliente_nombre'] ?? '',
            'folio'  => $envio['folio'] ?? '',
            'eta'    => $envio['eta'] ?? null,
            'verUrl' => $verUrl,
            'calificarUrl' => $verUrl . '#envio-' . (int) $envio['id'],
        ]);
    }
}
