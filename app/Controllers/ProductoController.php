<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Categoria;
use App\Models\Producto;

class ProductoController extends Controller
{
    private const POR_PAGINA = 12;

    public function index(): void
    {
        $this->render(null);
    }

    public function categoria(string $slug): void
    {
        $this->render($slug);
    }

    private function render(?string $slug): void
    {
        $categoriaModel = new Categoria();
        $productoModel  = new Producto();

        // Árbol (raíces + subcategorías activas): el menú de filtro pinta las
        // sub-tabs de la raíz a la que pertenezca $actual.
        $categorias = $categoriaModel->activasArbol();
        $actual = null;
        $categoriaIds = null;

        if ($slug !== null) {
            $actual = $categoriaModel->porSlug($slug);
            if (!$actual) {
                http_response_code(404);
                $this->view('errors/404', ['title' => 'Categoría no encontrada', 'active' => 'productos']);
                return;
            }
            // Si $actual es una categoría padre, incluye también sus subcategorías.
            $categoriaIds = $categoriaModel->descendientesIds((int) $actual['id']);
        }

        // Búsqueda por texto (opcional) + paginación
        $q = str_clean($_GET['q'] ?? '', 60) ?: null;

        $total  = $productoModel->contarActivosFiltrado($categoriaIds, $q);
        $pages  = max(1, (int) ceil($total / self::POR_PAGINA));
        $page   = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);
        $offset = ($page - 1) * self::POR_PAGINA;
        $productos = $productoModel->activosFiltrado(self::POR_PAGINA, $offset, $categoriaIds, $q);
        $imagenesPorProducto = $productoModel->imagenesPorProductos(array_column($productos, 'id'));
        foreach ($productos as &$p) {
            $p['disponibilidad'] = Producto::estadoDisponibilidad($p);
        }
        unset($p);

        $breadcrumb = [
            ['label' => 'Inicio', 'url' => url('/')],
            ['label' => 'Productos', 'url' => $actual ? url('/productos') : null],
        ];
        if ($actual && !empty($actual['categoria_padre_id'])) {
            $padre = $categoriaModel->find((int) $actual['categoria_padre_id']);
            if ($padre) {
                $breadcrumb[] = ['label' => $padre['nombre'], 'url' => url('/productos/' . $padre['slug'])];
            }
        }
        if ($actual) {
            $breadcrumb[] = ['label' => $actual['nombre']];
        }

        $this->view('pages/productos', [
            'title'           => ($actual ? $actual['nombre'] . ' — ' : '') . 'Productos — Importadora RYM',
            'metaDescription' => 'Catálogo de vasos, contenedores, servilletas, cubiertos y más para la industria alimentaria.',
            'active'          => 'productos',
            'pageTitle'       => $actual ? $actual['nombre'] : 'Nuestros productos',
            'pageSubtitle'    => $actual
                ? ($actual['descripcion'] ?? '')
                : 'Vasos, contenedores, servilletas, cubiertos, bolsas, cajas plegadizas y más para tu negocio.',
            'breadcrumb'      => $breadcrumb,
            'categorias'      => $categorias,
            'productos'       => $productos,
            'imagenesPorProducto' => $imagenesPorProducto,
            'actualSlug'      => $slug,
            'q'               => $q ?? '',
            'page'            => $page,
            'pages'           => $pages,
            'total'           => $total,
        ]);
    }
}
