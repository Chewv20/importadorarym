<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\Rol;
use App\Models\Permiso;

class RolController extends BaseController
{
    public function index(): void
    {
        Auth::authorize('roles.gestionar');
        $rolModel = new Rol();
        $roles = $rolModel->todos();
        foreach ($roles as &$r) {
            $r['num_permisos'] = count($rolModel->permisoIds((int) $r['id']));
            $r['num_usuarios'] = $rolModel->conteoUsuarios((int) $r['id']);
        }
        unset($r);

        $this->render('admin/roles', [
            'title'  => 'Roles y permisos — Panel RYM',
            'active' => 'roles',
            'roles'  => $roles,
        ]);
    }

    public function edit(string $id): void
    {
        Auth::authorize('roles.gestionar');
        $rolModel = new Rol();
        $rol = $rolModel->find((int) $id);
        if (!$rol) {
            flash('portal_error', 'Rol no encontrado.');
            $this->redirect('/admin/roles');
        }

        $this->render('admin/rol_form', [
            'title'          => 'Editar rol — Panel RYM',
            'active'         => 'roles',
            'rol'            => $rol,
            'permisosGrupos' => (new Permiso())->agrupados(),
            'asignados'      => $rolModel->permisoIds((int) $id),
        ]);
    }

    public function update(string $id): void
    {
        Auth::authorize('roles.gestionar');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/roles');
        }

        $rolModel = new Rol();
        $rol = $rolModel->find((int) $id);

        // El rol Administrador siempre conserva TODOS los permisos (anti-bloqueo).
        if ($rol && $rol['slug'] === 'admin') {
            flash('portal_error', 'El rol Administrador no se puede limitar.');
            $this->redirect('/admin/roles');
        }

        $permisos = array_map('intval', $_POST['permisos'] ?? []);
        $rolModel->sincronizarPermisos((int) $id, $permisos);

        \App\Core\Audit::cambio('actualizar', 'rol', (int) $id, 'Actualizó los permisos del rol "' . ($rol['nombre'] ?? ('#' . $id)) . '"');
        flash('portal_ok', 'Permisos del rol actualizados.');
        $this->redirect('/admin/roles');
    }
}
