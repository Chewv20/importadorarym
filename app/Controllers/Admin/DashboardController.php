<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\Cotizacion;
use App\Models\Usuario;
use App\Models\Producto;
use App\Models\Pedido;

class DashboardController extends BaseController
{
    public function index(): void
    {
        $puedeVer = Auth::can('dashboard.ver');
        $data = [
            'title'      => 'Panel — Importadora RYM',
            'active'     => 'dashboard',
            'puedeVer'   => $puedeVer,
        ];

        if ($puedeVer) {
            $cotizaciones = new Cotizacion();
            $pedidos      = new Pedido();
            $scope        = $this->vendedorScope();

            $data += [
                'leadsNuevos'      => $cotizaciones->contar('nueva', $scope),
                'leadsTotal'       => $cotizaciones->contar(null, $scope),
                'clientesPend'     => (new Usuario())->contarClientes(true, $scope),
                // El catálogo de productos es global (no depende del vendedor).
                'productosActivos' => (new Producto())->contarActivos(),
                'pedidosTotal'     => $pedidos->contar($scope),
                'recientes'        => $cotizaciones->recientes(6, $scope),
                'pedidosPorEstado' => $pedidos->contarPorEstado($scope),
                'cotsPorEstado'    => $cotizaciones->contarPorEstado($scope),
                'cotsPorSemana'    => $cotizaciones->porSemana(8, $scope),
                'soloAsignados'    => $scope !== null,
            ];
        }

        $this->render('admin/dashboard', $data);
    }
}
