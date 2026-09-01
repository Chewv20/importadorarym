<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\Categoria;

class CategoriaController extends BaseController
{
    public function index(): void
    {
        Auth::authorize('categorias.gestionar');
        $this->render('admin/categorias', [
            'title'      => 'Categorías — Panel RYM',
            'active'     => 'categorias',
            'categorias' => (new Categoria())->todas(),
        ]);
    }

    public function create(): void
    {
        Auth::authorize('categorias.gestionar');
        $this->form(null);
    }

    public function store(): void
    {
        Auth::authorize('categorias.gestionar');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/categorias');
        }
        $data = $this->input();
        if ($data['nombre'] === '') {
            flash('portal_error', 'El nombre es obligatorio.');
            $this->redirect('/admin/categorias/nueva');
        }
        if ($err = $this->validarPadre($data['categoria_padre_id'], null)) {
            flash('portal_error', $err);
            $this->redirect('/admin/categorias/nueva');
        }
        $nuevoId = (new Categoria())->crear($data);
        \App\Core\Audit::cambio('crear', 'categoria', is_int($nuevoId) ? $nuevoId : null, 'Creó la categoría "' . $data['nombre'] . '"');
        flash('portal_ok', 'Categoría creada.');
        $this->redirect('/admin/categorias');
    }

    public function edit(string $id): void
    {
        Auth::authorize('categorias.gestionar');
        $categoria = (new Categoria())->find((int) $id);
        if (!$categoria) {
            flash('portal_error', 'Categoría no encontrada.');
            $this->redirect('/admin/categorias');
        }
        $this->form($categoria);
    }

    public function update(string $id): void
    {
        Auth::authorize('categorias.gestionar');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/categorias');
        }
        $data = $this->input();
        if ($data['nombre'] === '') {
            flash('portal_error', 'El nombre es obligatorio.');
            $this->redirect('/admin/categorias/' . (int) $id . '/editar');
        }
        if ($err = $this->validarPadre($data['categoria_padre_id'], (int) $id)) {
            flash('portal_error', $err);
            $this->redirect('/admin/categorias/' . (int) $id . '/editar');
        }
        (new Categoria())->actualizar((int) $id, $data);
        \App\Core\Audit::cambio('actualizar', 'categoria', (int) $id, 'Editó la categoría "' . $data['nombre'] . '"');
        flash('portal_ok', 'Categoría actualizada.');
        $this->redirect('/admin/categorias');
    }

    public function delete(string $id): void
    {
        Auth::authorize('categorias.gestionar');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/categorias');
        }
        $model = new Categoria();
        $tieneHijos = $model->tieneHijos((int) $id);
        $model->eliminar((int) $id);
        \App\Core\Audit::cambio('eliminar', 'categoria', (int) $id, 'Eliminó una categoría (#' . (int) $id . ')');
        flash('portal_ok', 'Categoría eliminada. Sus productos quedaron sin categoría.'
            . ($tieneHijos ? ' Sus subcategorías pasaron a ser categorías principales.' : ''));
        $this->redirect('/admin/categorias');
    }

    /**
     * Valida el límite de 2 niveles: el padre elegido (si hay) debe existir,
     * ser una categoría RAÍZ (no otra subcategoría) y no ser la propia
     * categoría; además, una categoría que ya tiene subcategorías no puede
     * convertirse ella misma en subcategoría de otra.
     */
    private function validarPadre(?int $padreId, ?int $propiaId): ?string
    {
        if ($padreId === null) {
            return null;
        }
        $model = new Categoria();

        if ($propiaId !== null && $padreId === $propiaId) {
            return 'Una categoría no puede ser su propia categoría padre.';
        }
        if ($propiaId !== null && $model->tieneHijos($propiaId)) {
            return 'Esta categoría ya tiene subcategorías; no puede convertirse en subcategoría de otra (solo se admiten 2 niveles).';
        }
        if (!$model->find($padreId)) {
            return 'La categoría padre seleccionada no existe.';
        }
        if (!$model->esRaiz($padreId)) {
            return 'Solo puedes elegir como padre una categoría principal (no otra subcategoría): solo se admiten 2 niveles.';
        }
        return null;
    }

    private function form(?array $categoria): void
    {
        $model = new Categoria();

        // Padres disponibles: todas las raíces, excepto la propia categoría
        // (no puede ser su propio padre) y salvo que ella misma ya tenga hijos
        // (entonces no puede tener padre, ver validarPadre()).
        $propiaId = $categoria ? (int) $categoria['id'] : null;
        $yaEsPadre = $propiaId && $model->tieneHijos($propiaId);
        $raices = $yaEsPadre ? [] : array_filter(
            $model->todas(),
            fn ($c) => $c['nivel'] === 0 && (int) $c['id'] !== $propiaId
        );

        $this->render('admin/categoria_form', [
            'title'     => ($categoria ? 'Editar' : 'Nueva') . ' categoría — Panel RYM',
            'active'    => 'categorias',
            'categoria' => $categoria,
            'raices'    => array_values($raices),
            'yaEsPadre' => $yaEsPadre,
        ]);
    }

    private function input(): array
    {
        return [
            'nombre'             => str_clean($_POST['nombre'] ?? '', 120),
            'slug'               => str_clean($_POST['slug'] ?? '', 150),
            'descripcion'        => str_clean($_POST['descripcion'] ?? '', 255) ?: null,
            'categoria_padre_id' => (int) ($_POST['categoria_padre_id'] ?? 0) ?: null,
            'activo'             => isset($_POST['activo']),
            'orden'              => (int) ($_POST['orden'] ?? 0),
        ];
    }
}
