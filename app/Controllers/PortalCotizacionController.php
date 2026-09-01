<?php

namespace App\Controllers;

use App\Core\RateLimiter;
use App\Core\Mailer;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\Categoria;

/**
 * Cotizaciones del portal. Disponible para TODO cliente (aprobado o no):
 * arma una solicitud con productos y cantidades; Ventas le pone precios.
 */
class PortalCotizacionController extends PortalBaseController
{
    private const POR_PAGINA = 12;

    /* --------------------------------------- Armar solicitud -------- */

    public function form(): void
    {
        $productoModel = new Producto();
        $filtro = $this->filtroProductos();

        $total  = $productoModel->contarActivosFiltrado($filtro['categoriaIds'], $filtro['q']);
        $pages  = max(1, (int) ceil($total / self::POR_PAGINA));
        $page   = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);
        $offset = ($page - 1) * self::POR_PAGINA;

        $this->render('portal/cotizar', [
            'title'      => 'Solicitar cotización — Portal RYM',
            'active'     => 'cotizar',
            'productos'  => $productoModel->activosFiltrado(self::POR_PAGINA, $offset, $filtro['categoriaIds'], $filtro['q']),
            'carrito'    => $this->carrito(),
            'categorias' => (new Categoria())->activasArbol(),
            'q'          => $filtro['q'] ?? '',
            'catActual'  => $filtro['slug'],
            'accion'     => '/portal/cotizar',
            'page'       => $page,
            'pages'      => $pages,
        ]);
    }

    /** Lee q + categoría del GET y resuelve los ids de categoría (incluye subcategorías si aplica). */
    private function filtroProductos(): array
    {
        $q = str_clean($_GET['q'] ?? '', 60) ?: null;
        $slug = str_clean($_GET['categoria'] ?? '', 180);
        $categoriaModel = new Categoria();
        $cat = $slug !== '' ? $categoriaModel->porSlug($slug) : null;
        return [
            'q'            => $q,
            'slug'         => $cat['slug'] ?? '',
            'categoriaIds' => $cat ? $categoriaModel->descendientesIds((int) $cat['id']) : null,
        ];
    }

    public function agregar(): void
    {
        $this->guardCsrf();
        $pid  = (int) ($_POST['producto_id'] ?? 0);
        $cant = min(9999, max(1, (int) ($_POST['cantidad'] ?? 1)));
        $prod = (new Producto())->find($pid);

        $enCarrito = isset($_SESSION['cot_cart'][$pid]);
        if (!$enCarrito && count($_SESSION['cot_cart'] ?? []) >= PedidoController::MAX_PARTIDAS) {
            flash('portal_error', 'Tu solicitud ya tiene ' . PedidoController::MAX_PARTIDAS
                . ' productos distintos, el máximo por solicitud. Envíala y arma otra para el resto.');
        } elseif ($prod && (int) $prod['activo'] === 1) {
            $_SESSION['cot_cart'][$pid] = ($_SESSION['cot_cart'][$pid] ?? 0) + $cant;
            flash('portal_ok', 'Producto agregado a tu solicitud.');
        } else {
            flash('portal_error', 'Ese producto no está disponible.');
        }
        $this->redirect('/portal/cotizar');
    }

    public function quitar(): void
    {
        $this->guardCsrf();
        unset($_SESSION['cot_cart'][(int) ($_POST['producto_id'] ?? 0)]);
        $this->redirect('/portal/cotizar');
    }

    public function enviar(): void
    {
        $this->guardCsrf();
        if (!RateLimiter::attempt('cotizar-portal:' . $this->usuario['id'], 10, 600)) {
            flash('portal_error', 'Recibimos varias solicitudes tuyas. Inténtalo en unos minutos.');
            $this->redirect('/portal/cotizar');
        }

        $cart = $_SESSION['cot_cart'] ?? [];
        if (!$cart) {
            flash('portal_error', 'Agrega al menos un producto a tu solicitud.');
            $this->redirect('/portal/cotizar');
        }

        $productos = (new Producto())->porIds(array_keys($cart));
        $items = [];
        foreach ($cart as $pid => $cant) {
            $p = $productos[(int) $pid] ?? null;
            if ($p) {
                $items[] = [
                    'producto_id'     => (int) $pid,
                    'descripcion'     => $p['nombre'],
                    'cantidad'        => (int) $cant,
                    'precio_unitario' => 0,
                ];
            }
        }
        if (!$items) {
            flash('portal_error', 'Los productos de tu solicitud ya no están disponibles.');
            $this->redirect('/portal/cotizar');
        }

        $cotModel = new Cotizacion();
        $id = $cotModel->crear([
            'usuario_id' => (int) $this->usuario['id'],
            'nombre'     => $this->usuario['nombre'],
            'empresa'    => $this->usuario['empresa'] ?? null,
            'email'      => $this->usuario['email'],
            'telefono'   => $this->usuario['telefono'] ?? null,
            'mensaje'    => str_clean($_POST['mensaje'] ?? '', 2000) ?: null,
            'origen'     => 'portal',
            'ip'         => client_ip(),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255) ?: null,
        ]);
        $cotModel->agregarItems($id, $items);
        $folio = $cotModel->generarFolio($id);
        unset($_SESSION['cot_cart']);

        // Avisos (no bloquean el flujo).
        $leads = config('app.mail.leads');
        if ($leads) {
            Mailer::enviar($leads, 'Nueva solicitud de cotización ' . $folio, 'cotizacion_asesor', [
                'cot' => [
                    'nombre'  => $this->usuario['nombre'],
                    'empresa' => $this->usuario['empresa'] ?? null,
                    'email'   => $this->usuario['email'],
                    'origen'  => 'portal',
                    'mensaje' => 'Solicitud ' . $folio . ' con ' . count($items) . ' producto(s).',
                ],
            ]);
        }

        flash('portal_ok', 'Enviamos tu solicitud (' . $folio . '). Un asesor la revisará y te enviará la cotización con precios.');
        $this->redirect('/portal/cotizaciones');
    }

    /* --------------------------------------- Ver mis cotizaciones ---- */

    public function mis(): void
    {
        $this->render('portal/cotizaciones', [
            'title'        => 'Mis cotizaciones — Portal RYM',
            'active'       => 'cotizar',
            'cotizaciones' => (new Cotizacion())->porUsuario((int) $this->usuario['id']),
        ]);
    }

    public function ver(string $id): void
    {
        $cotModel = new Cotizacion();
        $cot = $cotModel->find((int) $id);
        if (!$cot || (int) $cot['usuario_id'] !== (int) $this->usuario['id']) {
            flash('portal_error', 'No encontramos esa cotización.');
            $this->redirect('/portal/cotizaciones');
        }
        $this->render('portal/cotizacion_detalle', [
            'title'  => 'Cotización ' . ($cot['folio'] ?? '') . ' — Portal RYM',
            'active' => 'cotizar',
            'cot'    => $cot,
            'items'  => $cotModel->items((int) $id),
        ]);
    }

    public function imprimir(string $id): void
    {
        $cotModel = new Cotizacion();
        $cot = $cotModel->find((int) $id);
        if (!$cot || (int) $cot['usuario_id'] !== (int) $this->usuario['id']) {
            $this->redirect('/portal/cotizaciones');
        }
        $this->view('cotizacion_imprimible', [
            'cot'   => $cot,
            'items' => $cotModel->items((int) $id),
        ], null);
    }

    public function responder(string $id): void
    {
        $this->guardCsrf('/portal/cotizaciones/' . (int) $id);
        $cotModel = new Cotizacion();
        $cot = $cotModel->find((int) $id);
        if (!$cot || (int) $cot['usuario_id'] !== (int) $this->usuario['id']) {
            flash('portal_error', 'No encontramos esa cotización.');
            $this->redirect('/portal/cotizaciones');
        }
        if ($cot['estado'] !== 'cotizada') {
            flash('portal_error', 'Esta cotización no está disponible para responder.');
            $this->redirect('/portal/cotizaciones/' . (int) $id);
        }

        $accion = $_POST['accion'] ?? '';
        $nuevo  = $accion === 'aceptar' ? 'aprobada' : ($accion === 'rechazar' ? 'rechazada' : null);
        if ($nuevo === null) {
            $this->redirect('/portal/cotizaciones/' . (int) $id);
        }
        $cotModel->actualizarEstado((int) $id, $nuevo);

        $leads = config('app.mail.leads');
        if ($leads) {
            Mailer::enviar($leads, 'Cotización ' . ($cot['folio'] ?? $id) . ' ' . $nuevo,
                'cotizacion_asesor', ['cot' => [
                    'nombre'  => $this->usuario['nombre'],
                    'email'   => $this->usuario['email'],
                    'origen'  => 'portal',
                    'mensaje' => 'El cliente ' . ($nuevo === 'aprobada' ? 'APROBÓ' : 'RECHAZÓ') . ' la cotización ' . ($cot['folio'] ?? $id) . '.',
                ]]);
        }

        flash('portal_ok', $nuevo === 'aprobada'
            ? '¡Gracias! Registramos tu aprobación; un asesor dará seguimiento.'
            : 'Registramos tu respuesta.');
        $this->redirect('/portal/cotizaciones/' . (int) $id);
    }

    /* --------------------------------------- Utilidades -------------- */

    private function guardCsrf(?string $back = null): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró. Vuelve a intentarlo.');
            $this->redirect($back ?? '/portal/cotizar');
        }
    }

    private function carrito(): array
    {
        $cart = $_SESSION['cot_cart'] ?? [];
        if (!$cart) {
            return [];
        }
        $productos = (new Producto())->porIds(array_keys($cart));
        $out = [];
        foreach ($cart as $pid => $cant) {
            $p = $productos[(int) $pid] ?? null;
            if ($p) {
                $out[] = [
                    'id'       => (int) $pid,
                    'nombre'   => $p['nombre'],
                    'unidad'   => $p['unidad'] ?? '',
                    'cantidad' => (int) $cant,
                ];
            }
        }
        return $out;
    }
}
