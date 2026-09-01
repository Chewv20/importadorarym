<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\Zona;

class ZonaController extends BaseController
{
    public function index(): void
    {
        Auth::authorize('zonas.gestionar');
        $this->render('admin/zonas', [
            'title'  => 'Zonas de reparto — Panel RYM',
            'active' => 'zonas',
            'zonas'  => (new Zona())->todas(),
        ]);
    }

    public function create(): void
    {
        Auth::authorize('zonas.gestionar');
        $this->render('admin/zona_form', [
            'title'  => 'Nueva zona — Panel RYM',
            'active' => 'zonas',
            'zona'   => null,
        ]);
    }

    public function store(): void
    {
        Auth::authorize('zonas.gestionar');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/zonas');
        }
        $data = $this->input();
        if ($data['nombre'] === '') {
            flash('portal_error', 'El nombre es obligatorio.');
            $this->redirect('/admin/zonas/nueva');
        }
        $id = (new Zona())->crear($data);
        \App\Core\Audit::cambio('crear', 'zona', $id, 'Creó la zona "' . $data['nombre'] . '"');
        flash('portal_ok', 'Zona creada.');
        $this->redirect('/admin/zonas');
    }

    public function edit(string $id): void
    {
        Auth::authorize('zonas.gestionar');
        $zona = (new Zona())->find((int) $id);
        if (!$zona) {
            flash('portal_error', 'Zona no encontrada.');
            $this->redirect('/admin/zonas');
        }
        $this->render('admin/zona_form', [
            'title'  => 'Editar zona — Panel RYM',
            'active' => 'zonas',
            'zona'   => $zona,
        ]);
    }

    public function update(string $id): void
    {
        Auth::authorize('zonas.gestionar');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/zonas');
        }
        $data = $this->input();
        if ($data['nombre'] === '') {
            flash('portal_error', 'El nombre es obligatorio.');
            $this->redirect('/admin/zonas/' . (int) $id . '/editar');
        }
        (new Zona())->actualizar((int) $id, $data);
        \App\Core\Audit::cambio('actualizar', 'zona', (int) $id, 'Editó la zona "' . $data['nombre'] . '"');
        flash('portal_ok', 'Zona actualizada.');
        $this->redirect('/admin/zonas');
    }

    public function delete(string $id): void
    {
        Auth::authorize('zonas.gestionar');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/zonas');
        }
        (new Zona())->eliminar((int) $id);
        \App\Core\Audit::cambio('eliminar', 'zona', (int) $id, 'Eliminó una zona (#' . (int) $id . ')');
        flash('portal_ok', 'Zona eliminada. Sus envíos asociados se quedaron sin zona.');
        $this->redirect('/admin/zonas');
    }

    private function input(): array
    {
        return [
            'nombre' => str_clean($_POST['nombre'] ?? '', 120),
            'activa' => isset($_POST['activa']),
        ];
    }
}
