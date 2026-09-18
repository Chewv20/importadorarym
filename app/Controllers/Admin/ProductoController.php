<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Upload;
use App\Core\Audit;
use App\Core\CatalogoImport;
use App\Models\Producto;
use App\Models\Categoria;

class ProductoController extends BaseController
{
    public function index(): void
    {
        Auth::authorize('productos.ver');
        $model = new Producto();
        $pg = $this->paginar($model->contarTodos(), 20);

        $this->render('admin/productos', [
            'title'     => 'Productos — Panel RYM',
            'active'    => 'productos',
            'productos' => $model->todosPaginado($pg['perPage'], $pg['offset']),
            'page'      => $pg['page'],
            'pages'     => $pg['pages'],
            // Para señalar qué productos heredan el esquema general y cuáles no tienen ninguno.
            'esquemaGeneral' => Producto::esquemaImpuestos(config('app.erp.esquema_impuestos')),
        ]);
    }

    public function create(): void
    {
        Auth::authorize('productos.crear');
        $this->form(null);
    }

    /** Autocompletar de productos (JSON) para armar cotizaciones. */
    public function buscar(): void
    {
        if (!Auth::can('productos.ver') && !Auth::can('cotizaciones.gestionar')) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'forbidden']);
            return;
        }
        header('Content-Type: application/json; charset=utf-8');
        $q = str_clean($_GET['q'] ?? '', 60);
        if (mb_strlen($q) < 2) {
            echo json_encode([]);
            return;
        }
        $items = array_map(static fn ($p) => [
            'id'     => (int) $p['id'],
            'nombre' => $p['nombre'],
            'sku'    => $p['sku'],
            'precio' => $p['precio'] !== null ? (float) $p['precio'] : null,
        ], (new Producto())->buscar($q, 15));
        echo json_encode($items);
    }

    public function store(): void
    {
        Auth::authorize('productos.crear');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/productos');
        }
        $data = $this->input();
        if ($data['nombre'] === '') {
            flash('portal_error', 'El nombre es obligatorio.');
            $this->redirect('/admin/productos/nuevo');
        }
        $model = new Producto();
        $id = $model->crear($data);
        $this->guardarImagenes($model, $id);
        \App\Core\Audit::cambio('crear', 'producto', $id, 'Creó el producto "' . $data['nombre'] . '"');
        flash('portal_ok', 'Producto creado.');
        $this->redirect('/admin/productos/' . $id . '/editar');
    }

    public function edit(string $id): void
    {
        Auth::authorize('productos.editar');
        $producto = (new Producto())->find((int) $id);
        if (!$producto) {
            flash('portal_error', 'Producto no encontrado.');
            $this->redirect('/admin/productos');
        }
        $this->form($producto);
    }

    public function update(string $id): void
    {
        Auth::authorize('productos.editar');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/productos');
        }
        $data = $this->input();
        if ($data['nombre'] === '') {
            flash('portal_error', 'El nombre es obligatorio.');
            $this->redirect('/admin/productos/' . (int) $id . '/editar');
        }
        $model = new Producto();
        $model->actualizar((int) $id, $data);
        $this->guardarImagenes($model, (int) $id);
        \App\Core\Audit::cambio('actualizar', 'producto', (int) $id, 'Editó el producto "' . $data['nombre'] . '"');
        flash('portal_ok', 'Producto actualizado.');
        $this->redirect('/admin/productos/' . (int) $id . '/editar');
    }

    public function delete(string $id): void
    {
        Auth::authorize('productos.eliminar');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/productos');
        }
        $model = new Producto();
        $prod = $model->find((int) $id);
        // Borra los archivos de imagen antes de eliminar (la BD hace CASCADE de las filas).
        foreach ($model->imagenes((int) $id) as $img) {
            Upload::borrar($img['ruta']);
        }
        $model->eliminar((int) $id);
        \App\Core\Audit::cambio('eliminar', 'producto', (int) $id, 'Eliminó el producto "' . ($prod['nombre'] ?? ('#' . $id)) . '"');
        flash('portal_ok', 'Producto eliminado.');
        $this->redirect('/admin/productos');
    }

    public function deleteImagen(string $id, string $imgId): void
    {
        Auth::authorize('productos.editar');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/productos');
        }
        $ruta = (new Producto())->eliminarImagen((int) $imgId, (int) $id);
        if ($ruta !== null) {
            Upload::borrar($ruta);
            flash('portal_ok', 'Imagen eliminada.');
        }
        $this->redirect('/admin/productos/' . (int) $id . '/editar');
    }

    /** Guarda las imágenes subidas respetando el máximo por producto. */
    private function guardarImagenes(Producto $model, int $id): void
    {
        $subidas = $_FILES['imagenes'] ?? null;
        $hayArchivo = $subidas && !empty(array_filter((array) ($subidas['name'] ?? [])));
        if (!$hayArchivo) {
            return;
        }

        $disponibles = Producto::MAX_IMAGENES - $model->contarImagenes($id);
        if ($disponibles <= 0) {
            flash('portal_error', 'El producto ya tiene el máximo de ' . Producto::MAX_IMAGENES . ' imágenes.');
            return;
        }

        $res = Upload::imagenes($subidas, 'productos', $disponibles);
        if ($res['rutas']) {
            $model->agregarImagenes($id, $res['rutas']);
        }
        if ($res['errores']) {
            flash('portal_error', implode(' ', array_unique($res['errores'])));
        }
    }

    /* --------------------------------- Importación CSV --------------- */

    public function importForm(): void
    {
        Auth::authorize('productos.crear');
        $resultado = $_SESSION['import_resultado'] ?? null;
        unset($_SESSION['import_resultado']);
        $this->render('admin/producto_import', [
            'title'     => 'Importar catálogo — Panel RYM',
            'active'    => 'productos',
            'resultado' => $resultado,
        ]);
    }

    /** Descarga la plantilla CSV de ejemplo. */
    public function plantilla(): void
    {
        Auth::authorize('productos.crear');
        $file = ROOT_PATH . '/database/plantilla_catalogo.csv';
        if (!is_file($file)) {
            flash('portal_error', 'No se encontró la plantilla.');
            $this->redirect('/admin/productos/importar');
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="plantilla_catalogo.csv"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    public function importar(): void
    {
        Auth::authorize('productos.crear');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/productos/importar');
        }

        $f = $_FILES['csv'] ?? null;
        if (!$f || (int) ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'] ?? '')) {
            flash('portal_error', 'Selecciona un archivo CSV.');
            $this->redirect('/admin/productos/importar');
        }
        if (strtolower(pathinfo((string) ($f['name'] ?? ''), PATHINFO_EXTENSION)) !== 'csv') {
            flash('portal_error', 'El archivo debe tener extensión .csv.');
            $this->redirect('/admin/productos/importar');
        }
        if ((int) ($f['size'] ?? 0) > 2 * 1024 * 1024) {
            flash('portal_error', 'El archivo supera el máximo de 2 MB.');
            $this->redirect('/admin/productos/importar');
        }

        $dryRun = isset($_POST['simular']);
        $r = CatalogoImport::procesar((string) $f['tmp_name'], $dryRun);

        if ($r['error'] !== null) {
            flash('portal_error', $r['error']);
            $this->redirect('/admin/productos/importar');
        }

        $r['dry'] = $dryRun;
        $_SESSION['import_resultado'] = $r;

        if (!$dryRun && ($r['creados'] || $r['actualizados'] || $r['categorias'])) {
            Audit::cambio('crear', 'catalogo', null,
                "Importó catálogo: {$r['creados']} creados, {$r['actualizados']} actualizados, {$r['categorias']} categorías");
        }

        flash('portal_ok', $dryRun ? 'Simulación completada (no se guardó nada).' : 'Importación completada.');
        $this->redirect('/admin/productos/importar');
    }

    private function form(?array $producto): void
    {
        $model = new Producto();
        $this->render('admin/producto_form', [
            'title'      => ($producto ? 'Editar' : 'Nuevo') . ' producto — Panel RYM',
            'active'     => 'productos',
            'producto'   => $producto,
            // Árbol (incluye inactivas, igual que antes): el select agrupa
            // subcategorías bajo su padre con <optgroup>.
            'categorias' => (new Categoria())->todasArbol(),
            'imagenes'   => $producto ? $model->imagenes((int) $producto['id']) : [],
            'maxImagenes' => Producto::MAX_IMAGENES,
            // Respaldo del .env, para indicar en el formulario qué pasa si se deja vacío.
            'esquemaGeneral' => Producto::esquemaImpuestos(config('app.erp.esquema_impuestos')),
        ]);
    }

    private function input(): array
    {
        return [
            'nombre'       => str_clean($_POST['nombre'] ?? '', 150),
            'slug'         => str_clean($_POST['slug'] ?? '', 180),
            'categoria_id' => (int) ($_POST['categoria_id'] ?? 0) ?: null,
            'descripcion'  => str_clean($_POST['descripcion'] ?? '', 2000) ?: null,
            'sku'          => str_clean($_POST['sku'] ?? '', 60) ?: null,
            'clave_sae'    => str_clean($_POST['clave_sae'] ?? '', 30) ?: null,
            'esquema_impuestos' => Producto::esquemaImpuestos($_POST['esquema_impuestos'] ?? null),
            'unidad'       => str_clean($_POST['unidad'] ?? '', 40) ?: null,
            'piezas_por_presentacion' => Producto::piezasPorPresentacion($_POST['piezas_por_presentacion'] ?? null),
            'piezas_minimas' => Producto::piezasMinimas($_POST['piezas_minimas'] ?? null),
            'destacado'    => isset($_POST['destacado']),
            'personalizable' => isset($_POST['personalizable']),
            'activo'       => isset($_POST['activo']),
            'orden'        => (int) ($_POST['orden'] ?? 0),
        ];
    }
}
