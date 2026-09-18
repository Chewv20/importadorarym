<?php

namespace App\Core;

use App\Models\Producto;

/**
 * Catálogo de armado de pedido y carrito en sesión: mismo mecanismo compartido
 * entre el portal (App\Controllers\PedidoController) y el panel
 * (Admin\PedidoNuevoController, vendedor crea un pedido a nombre de un
 * cliente) — cambia solo la clave de sesión donde vive el carrito y quién
 * termina siendo el dueño del pedido. Extraído en la auditoría pre-producción
 * del 01/09/2026 para que un cambio en el ajuste de lote, el tope de
 * partidas o el armado de partidas no tenga que aplicarse dos veces.
 */
class CarritoPedido
{
    public function __construct(private string $sessionKey, private int $maxPartidas)
    {
    }

    /* ------------------------------------------------- Catálogo ------- */

    /**
     * Página de catálogo filtrado con imagen y mínimo efectivo ya resueltos.
     * @return array{productos:array,page:int,pages:int}
     */
    public static function catalogo(int $porPagina, int $page, ?array $categoriaIds, ?string $q, ?array $productoIds): array
    {
        $productoModel = new Producto();
        $total = $productoModel->contarActivosFiltrado($categoriaIds, $q, $productoIds);
        $pages = max(1, (int) ceil($total / $porPagina));
        $page  = min(max(1, $page), $pages);
        $offset = ($page - 1) * $porPagina;

        $productos = $productoModel->activosFiltrado($porPagina, $offset, $categoriaIds, $q, $productoIds);
        $imagenes  = self::imagenPrincipalPorIds(array_column($productos, 'id'));
        foreach ($productos as &$p) {
            $p['imagen'] = $imagenes[(int) $p['id']] ?? null;
            [$piezas, $minimo] = Producto::loteDesdeFila($p);
            $p['minimo_efectivo'] = ($piezas !== null || $minimo !== null)
                ? Producto::cantidadValida(1, $piezas, $minimo) : null;
            $p['disponibilidad'] = Producto::estadoDisponibilidad($p);
        }
        unset($p);

        return ['productos' => $productos, 'page' => $page, 'pages' => $pages];
    }

    /**
     * Imagen actual (no un snapshot) de cada producto — la de menor `orden`.
     * @param array<int|string> $ids
     * @return array<int,?string> id => ruta de la imagen, o null si no tiene.
     */
    public static function imagenPrincipalPorIds(array $ids): array
    {
        $mapa = (new Producto())->imagenesPorProductos($ids);
        $out = [];
        foreach ($ids as $id) {
            $out[(int) $id] = $mapa[(int) $id][0] ?? null;
        }
        return $out;
    }

    /* --------------------------------------------------- Carrito ------ */

    public function raw(): array
    {
        return $_SESSION[$this->sessionKey] ?? [];
    }

    public function vaciar(): void
    {
        unset($_SESSION[$this->sessionKey]);
    }

    public function quitar(int $pid): void
    {
        unset($_SESSION[$this->sessionKey][$pid]);
    }

    /** Carrito con datos de producto listos para mostrar (nombre, unidad, imagen). */
    public function detallado(): array
    {
        $carrito = $this->raw();
        if (!$carrito) {
            return [];
        }
        $productos = (new Producto())->porIds(array_keys($carrito));
        $imagenes  = self::imagenPrincipalPorIds(array_keys($carrito));
        $out = [];
        foreach ($carrito as $pid => $cant) {
            $p = $productos[(int) $pid] ?? null;
            if ($p) {
                $out[] = [
                    'id'       => (int) $pid,
                    'nombre'   => $p['nombre'],
                    'unidad'   => $p['unidad'] ?? '',
                    'cantidad' => (int) $cant,
                    'imagen'   => $imagenes[(int) $pid] ?? null,
                ];
            }
        }
        return $out;
    }

    /**
     * Agrega (o suma) una cantidad, ajustada a presentación/mínimo del producto.
     * @return array{ok:bool,motivo?:string,ajustado?:bool,cantidad?:int,producto?:array}
     */
    public function agregar(int $pid, int $cantSolicitada, ?array $productoIds): array
    {
        $enCarrito = isset($_SESSION[$this->sessionKey][$pid]);
        if (!$enCarrito && count($this->raw()) >= $this->maxPartidas) {
            return ['ok' => false, 'motivo' => 'limite'];
        }
        if ($productoIds !== null && !in_array($pid, $productoIds, true)) {
            return ['ok' => false, 'motivo' => 'no_disponible'];
        }
        $prod = (new Producto())->find($pid);
        if (!$prod || (int) $prod['activo'] !== 1) {
            return ['ok' => false, 'motivo' => 'no_disponible'];
        }

        [$piezas, $minimo] = Producto::loteDesdeFila($prod);
        $cant = Producto::cantidadValida($cantSolicitada, $piezas, $minimo);
        $_SESSION[$this->sessionKey][$pid] = ($_SESSION[$this->sessionKey][$pid] ?? 0) + $cant;

        return ['ok' => true, 'ajustado' => $cant !== $cantSolicitada, 'cantidad' => $cant, 'producto' => $prod];
    }

    /**
     * Fija (no suma) la cantidad de una partida ya presente en el carrito.
     * @return array{ok:bool,ajustado?:bool,cantidad?:int,producto?:?array}
     */
    public function actualizarCantidad(int $pid, int $cantSolicitada): array
    {
        if (!isset($_SESSION[$this->sessionKey][$pid]) || $cantSolicitada < 1 || $cantSolicitada > 9999) {
            return ['ok' => false];
        }
        $prod = (new Producto())->find($pid);
        [$piezas, $minimo] = $prod ? Producto::loteDesdeFila($prod) : [null, null];
        $cant = Producto::cantidadValida($cantSolicitada, $piezas, $minimo);
        $_SESSION[$this->sessionKey][$pid] = $cant;
        return ['ok' => true, 'ajustado' => $cant !== $cantSolicitada, 'cantidad' => $cant, 'producto' => $prod];
    }

    /** Arma las partidas para Pedido::crear() a partir del carrito actual (sin N+1). */
    public function armarItems(): array
    {
        $carrito = $this->raw();
        if (!$carrito) {
            return [];
        }
        $productos = (new Producto())->porIds(array_keys($carrito));
        $items = [];
        foreach ($carrito as $pid => $cant) {
            $p = $productos[(int) $pid] ?? null;
            if (!$p) {
                continue;
            }
            $items[] = [
                'producto_id'     => (int) $pid,
                'sku'             => $p['sku'] ?? null,
                'nombre'          => $p['nombre'],
                'cantidad'        => (int) $cant,
                'precio_unitario' => 0,
            ];
        }
        return $items;
    }
}
