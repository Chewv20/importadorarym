<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Categoria;
use App\Models\Producto;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('pages/home', [
            'title'           => 'Importadora RYM — Insumos y empaques para empresas',
            'metaDescription' => 'Fabricación, impresión personalizada y distribución de envases, empaques e insumos para la industria alimentaria. 35 años de experiencia, +2,000 clientes.',
            'active'          => 'inicio',
            // Solo categorías principales: con subcategorías, mezclar niveles
            // en la grilla de tarjetas del home resultaría confuso.
            'categorias'      => (new Categoria())->raicesActivas(),
            'destacados'      => (new Producto())->destacados(4),
        ]);
    }
}
