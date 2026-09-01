<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Audit;
use App\Core\Upload;
use App\Models\LogoProveedor;

class LogoProveedorController extends BaseController
{
    public function index(): void
    {
        Auth::authorize('logos_proveedores.gestionar');
        $this->render('admin/logos_proveedores', [
            'title'  => 'Logos de proveedores — Panel RYM',
            'active' => 'logos_proveedores',
            'logos'  => (new LogoProveedor())->todos(),
        ]);
    }

    public function create(): void
    {
        Auth::authorize('logos_proveedores.gestionar');
        $this->form(null);
    }

    public function store(): void
    {
        Auth::authorize('logos_proveedores.gestionar');
        $this->guard('/admin/logos-proveedores');

        $nombre = str_clean($_POST['nombre'] ?? '', 150);
        $ruta   = $this->subirLogo();
        if ($nombre === '') {
            flash('portal_error', 'El nombre es obligatorio.');
            $this->redirect('/admin/logos-proveedores/nuevo');
        }
        if ($ruta === null) {
            flash('portal_error', 'Sube una imagen del logo (JPG, PNG o WebP, máx. 3 MB).');
            $this->redirect('/admin/logos-proveedores/nuevo');
        }

        $id = (new LogoProveedor())->crear([
            'nombre' => $nombre,
            'imagen' => $ruta,
            'activo' => isset($_POST['activo']),
            'orden'  => (int) ($_POST['orden'] ?? 0),
        ]);
        Audit::cambio('crear', 'logo_proveedor', $id, 'Agregó el logo de proveedor "' . $nombre . '"');
        flash('portal_ok', 'Logo agregado.');
        $this->redirect('/admin/logos-proveedores');
    }

    public function edit(string $id): void
    {
        Auth::authorize('logos_proveedores.gestionar');
        $logo = (new LogoProveedor())->find((int) $id);
        if (!$logo) {
            flash('portal_error', 'Logo no encontrado.');
            $this->redirect('/admin/logos-proveedores');
        }
        $this->form($logo);
    }

    public function update(string $id): void
    {
        Auth::authorize('logos_proveedores.gestionar');
        $this->guard('/admin/logos-proveedores');

        $model = new LogoProveedor();
        $logo  = $model->find((int) $id);
        if (!$logo) {
            $this->redirect('/admin/logos-proveedores');
        }
        $nombre = str_clean($_POST['nombre'] ?? '', 150);
        if ($nombre === '') {
            flash('portal_error', 'El nombre es obligatorio.');
            $this->redirect('/admin/logos-proveedores/' . (int) $id . '/editar');
        }

        $ruta = $this->subirLogo();
        if ($ruta !== null) {
            Upload::borrar($logo['imagen']); // borra el anterior (solo si es de uploads/)
        }

        $model->actualizar((int) $id, [
            'nombre' => $nombre,
            'imagen' => $ruta,          // null = conserva la actual
            'activo' => isset($_POST['activo']),
            'orden'  => (int) ($_POST['orden'] ?? 0),
        ]);
        Audit::cambio('actualizar', 'logo_proveedor', (int) $id, 'Editó el logo de proveedor "' . $nombre . '"');
        flash('portal_ok', 'Logo actualizado.');
        $this->redirect('/admin/logos-proveedores');
    }

    public function delete(string $id): void
    {
        Auth::authorize('logos_proveedores.gestionar');
        $this->guard('/admin/logos-proveedores');
        $model = new LogoProveedor();
        $logo  = $model->find((int) $id);
        if ($logo) {
            Upload::borrar($logo['imagen']);
            $model->eliminar((int) $id);
            Audit::cambio('eliminar', 'logo_proveedor', (int) $id, 'Eliminó el logo de proveedor "' . ($logo['nombre'] ?? '') . '"');
            flash('portal_ok', 'Logo eliminado.');
        }
        $this->redirect('/admin/logos-proveedores');
    }

    /* ------------------------------------------------ Utilidades ------ */

    private function subirLogo(): ?string
    {
        $res = Upload::logo($_FILES['imagen'] ?? null, 'proveedores');
        if (($res['error'] ?? null) !== null) {
            flash('portal_error', $res['error']);
        }
        return $res['ruta'] ?? null;
    }

    private function form(?array $logo): void
    {
        $this->render('admin/logo_proveedor_form', [
            'title'  => ($logo ? 'Editar' : 'Nuevo') . ' logo de proveedor — Panel RYM',
            'active' => 'logos_proveedores',
            'logo'   => $logo,
        ]);
    }

    private function guard(string $back): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect($back);
        }
    }
}
