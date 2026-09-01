<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\CodigoPostal;
use App\Core\RateLimiter;

/**
 * Autocompletar de dirección por código postal (registro y perfil). Público
 * y sin CSRF a propósito: es un GET de solo lectura sobre datos geográficos
 * públicos (no expone ni recibe nada del usuario), igual que el buscador de
 * productos del panel.
 */
class CodigoPostalController extends Controller
{
    public function buscar(string $cp): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Limita el uso de este endpoint como proxy hacia el servicio externo.
        if (!RateLimiter::attempt('cp:' . client_ip(), 30, 600)) {
            http_response_code(429);
            echo json_encode(['found' => false]);
            return;
        }

        $datos = CodigoPostal::buscar($cp);
        if ($datos === null) {
            echo json_encode(['found' => false]);
            return;
        }

        echo json_encode(['found' => true] + $datos, JSON_UNESCAPED_UNICODE);
    }
}
