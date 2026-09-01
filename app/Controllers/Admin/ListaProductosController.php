<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Audit;
use App\Models\ListaProductos;
use App\Models\Producto;

/**
 * Fase 7.3: listas de productos que se pueden asignar a un cliente para
 * acotar su catálogo (qué productos ve/puede pedir, no a qué precio — eso
 * sigue pausado). La asignación a un cliente vive en Admin\ClienteController.
 */
class ListaProductosController extends BaseController
{
    public function index(): void
    {
        Auth::authorize('listas.gestionar');
        $this->render('admin/listas_productos', [
            'title'  => 'Listas de productos — Panel RYM',
            'active' => 'listas_productos',
            'listas' => (new ListaProductos())->todas(),
        ]);
    }

    public function create(): void
    {
        Auth::authorize('listas.gestionar');
        $this->render('admin/lista_productos_form', [
            'title'  => 'Nueva lista de productos — Panel RYM',
            'active' => 'listas_productos',
            'lista'  => null,
        ]);
    }

    public function store(): void
    {
        Auth::authorize('listas.gestionar');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/listas-productos');
        }
        $data = $this->input();
        if ($data['nombre'] === '') {
            flash('portal_error', 'El nombre es obligatorio.');
            $this->redirect('/admin/listas-productos/nueva');
        }
        $id = (new ListaProductos())->crear($data);
        Audit::cambio('crear', 'lista_productos', $id, 'Creó la lista de productos "' . $data['nombre'] . '"');
        flash('portal_ok', 'Lista creada. Ahora agrega los productos que incluye.');
        $this->redirect('/admin/listas-productos/' . $id . '/editar');
    }

    public function edit(string $id): void
    {
        Auth::authorize('listas.gestionar');
        $model = new ListaProductos();
        $lista = $model->find((int) $id);
        if (!$lista) {
            flash('portal_error', 'Lista no encontrada.');
            $this->redirect('/admin/listas-productos');
        }
        $this->render('admin/lista_productos_form', [
            'title'     => 'Editar lista de productos — Panel RYM',
            'active'    => 'listas_productos',
            'lista'     => $lista,
            'productos' => $model->productos((int) $id),
        ]);
    }

    public function update(string $id): void
    {
        Auth::authorize('listas.gestionar');
        $this->guard($id);
        $data = $this->input();
        if ($data['nombre'] === '') {
            flash('portal_error', 'El nombre es obligatorio.');
            $this->redirect('/admin/listas-productos/' . (int) $id . '/editar');
        }
        (new ListaProductos())->actualizar((int) $id, $data);
        Audit::cambio('actualizar', 'lista_productos', (int) $id, 'Editó la lista de productos "' . $data['nombre'] . '"');
        flash('portal_ok', 'Lista actualizada.');
        $this->redirect('/admin/listas-productos/' . (int) $id . '/editar');
    }

    public function delete(string $id): void
    {
        Auth::authorize('listas.gestionar');
        $this->guard($id);
        (new ListaProductos())->eliminar((int) $id);
        Audit::cambio('eliminar', 'lista_productos', (int) $id, 'Eliminó una lista de productos (#' . (int) $id . ')');
        flash('portal_ok', 'Lista eliminada. Sus clientes asignados vuelven a ver el catálogo completo.');
        $this->redirect('/admin/listas-productos');
    }

    public function agregarProducto(string $id): void
    {
        Auth::authorize('listas.gestionar');
        $this->guard($id);
        $pid = (int) ($_POST['producto_id'] ?? 0);
        if ($pid && (new Producto())->find($pid)) {
            (new ListaProductos())->agregarProducto((int) $id, $pid);
        }
        $this->redirect('/admin/listas-productos/' . (int) $id . '/editar');
    }

    public function quitarProducto(string $id, string $productoId): void
    {
        Auth::authorize('listas.gestionar');
        $this->guard($id);
        (new ListaProductos())->quitarProducto((int) $id, (int) $productoId);
        $this->redirect('/admin/listas-productos/' . (int) $id . '/editar');
    }

    private function guard(string $id): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/listas-productos/' . (int) $id . '/editar');
        }
    }

    private function input(): array
    {
        return [
            'nombre'      => str_clean($_POST['nombre'] ?? '', 120),
            'descripcion' => str_clean($_POST['descripcion'] ?? '', 255) ?: null,
            'activa'      => isset($_POST['activa']),
        ];
    }
}
