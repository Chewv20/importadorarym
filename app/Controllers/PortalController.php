<?php

namespace App\Controllers;

use App\Models\Pedido;

class PortalController extends PortalBaseController
{
    public function index(): void
    {
        $pedidos = (new Pedido())->porUsuario((int) $this->usuario['id']);

        $this->render('portal/dashboard', [
            'title'        => 'Mi portal — Importadora RYM',
            'active'       => 'inicio',
            'recientes'    => array_slice($pedidos, 0, 5),
            'totalPedidos' => count($pedidos),
        ]);
    }
}
