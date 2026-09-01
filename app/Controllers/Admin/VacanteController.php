<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Audit;
use App\Models\Vacante;

class VacanteController extends BaseController
{
    public function index(): void
    {
        Auth::authorize('vacantes.gestionar');
        $this->render('admin/vacantes', [
            'title'    => 'Vacantes — Panel RYM',
            'active'   => 'vacantes',
            'vacantes' => (new Vacante())->todas(),
            'tipos'    => Vacante::TIPOS,
        ]);
    }

    public function create(): void
    {
        Auth::authorize('vacantes.gestionar');
        $this->render('admin/vacante_form', [
            'title'  => 'Nueva vacante — Panel RYM',
            'active' => 'vacantes',
            'vac'    => null,
            'tipos'  => Vacante::TIPOS,
            'accion' => url('/admin/vacantes'),
        ]);
    }

    public function store(): void
    {
        Auth::authorize('vacantes.gestionar');
        $this->guard('/admin/vacantes');

        $d = $this->datos();
        if ($d['titulo'] === '') {
            flash('portal_error', 'El título de la vacante es obligatorio.');
            $this->redirect('/admin/vacantes/nueva');
        }
        $id = (new Vacante())->crear($d);
        Audit::cambio('crear', 'vacante', $id, 'Publicó la vacante "' . $d['titulo'] . '"');
        flash('portal_ok', 'Vacante creada.');
        $this->redirect('/admin/vacantes');
    }

    public function edit(string $id): void
    {
        Auth::authorize('vacantes.gestionar');
        $vac = (new Vacante())->find((int) $id);
        if (!$vac) {
            flash('portal_error', 'Vacante no encontrada.');
            $this->redirect('/admin/vacantes');
        }
        $this->render('admin/vacante_form', [
            'title'  => 'Editar vacante — Panel RYM',
            'active' => 'vacantes',
            'vac'    => $vac,
            'tipos'  => Vacante::TIPOS,
            'accion' => url('/admin/vacantes/' . (int) $id),
        ]);
    }

    public function update(string $id): void
    {
        Auth::authorize('vacantes.gestionar');
        $this->guard('/admin/vacantes');

        $d = $this->datos();
        if ($d['titulo'] === '') {
            flash('portal_error', 'El título de la vacante es obligatorio.');
            $this->redirect('/admin/vacantes/' . (int) $id . '/editar');
        }
        (new Vacante())->actualizar((int) $id, $d);
        Audit::cambio('actualizar', 'vacante', (int) $id, 'Actualizó la vacante "' . $d['titulo'] . '"');
        flash('portal_ok', 'Vacante actualizada.');
        $this->redirect('/admin/vacantes');
    }

    public function delete(string $id): void
    {
        Auth::authorize('vacantes.gestionar');
        $this->guard('/admin/vacantes');
        (new Vacante())->eliminar((int) $id);
        Audit::cambio('eliminar', 'vacante', (int) $id, 'Eliminó la vacante #' . (int) $id);
        flash('portal_ok', 'Vacante eliminada.');
        $this->redirect('/admin/vacantes');
    }

    private function datos(): array
    {
        $tipo = $_POST['tipo'] ?? '';
        $estado = $_POST['estado'] ?? '';
        return [
            'titulo'      => str_clean($_POST['titulo'] ?? '', 150),
            'area'        => str_clean($_POST['area'] ?? '', 100),
            'ubicacion'   => str_clean($_POST['ubicacion'] ?? '', 150),
            'tipo'        => array_key_exists($tipo, Vacante::TIPOS) ? $tipo : 'tiempo_completo',
            'descripcion' => str_clean($_POST['descripcion'] ?? '', 5000),
            'requisitos'  => str_clean($_POST['requisitos'] ?? '', 5000),
            'estado'      => in_array($estado, ['abierta', 'cerrada'], true) ? $estado : 'abierta',
        ];
    }

    private function guard(string $back): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect($back);
        }
    }
}
