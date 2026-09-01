<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Audit;
use App\Core\Upload;
use App\Models\ClienteLogo;

class ClienteLogoController extends BaseController
{
    public function index(): void
    {
        Auth::authorize('clientes_logos.gestionar');
        $this->render('admin/logos_clientes', [
            'title'  => 'Logos de clientes — Panel RYM',
            'active' => 'logos_clientes',
            'logos'  => (new ClienteLogo())->todos(),
        ]);
    }

    public function create(): void
    {
        Auth::authorize('clientes_logos.gestionar');
        $this->form(null);
    }

    public function store(): void
    {
        Auth::authorize('clientes_logos.gestionar');
        $this->guard('/admin/logos-clientes');

        $nombre = str_clean($_POST['nombre'] ?? '', 150);
        $ruta   = $this->subirLogo();
        if ($nombre === '') {
            flash('portal_error', 'El nombre es obligatorio.');
            $this->redirect('/admin/logos-clientes/nuevo');
        }
        if ($ruta === null) {
            flash('portal_error', 'Sube una imagen del logo (JPG, PNG o WebP, máx. 3 MB).');
            $this->redirect('/admin/logos-clientes/nuevo');
        }

        $id = (new ClienteLogo())->crear([
            'nombre' => $nombre,
            'imagen' => $ruta,
            'activo' => isset($_POST['activo']),
            'orden'  => (int) ($_POST['orden'] ?? 0),
        ]);
        Audit::cambio('crear', 'cliente_logo', $id, 'Agregó el logo de cliente "' . $nombre . '"');
        flash('portal_ok', 'Logo agregado.');
        $this->redirect('/admin/logos-clientes');
    }

    public function edit(string $id): void
    {
        Auth::authorize('clientes_logos.gestionar');
        $logo = (new ClienteLogo())->find((int) $id);
        if (!$logo) {
            flash('portal_error', 'Logo no encontrado.');
            $this->redirect('/admin/logos-clientes');
        }
        $this->form($logo);
    }

    public function update(string $id): void
    {
        Auth::authorize('clientes_logos.gestionar');
        $this->guard('/admin/logos-clientes');

        $model = new ClienteLogo();
        $logo  = $model->find((int) $id);
        if (!$logo) {
            $this->redirect('/admin/logos-clientes');
        }
        $nombre = str_clean($_POST['nombre'] ?? '', 150);
        if ($nombre === '') {
            flash('portal_error', 'El nombre es obligatorio.');
            $this->redirect('/admin/logos-clientes/' . (int) $id . '/editar');
        }

        $ruta = $this->subirLogo();
        if ($ruta !== null) {
            Upload::borrar($logo['imagen']); // borra la anterior (solo si es de uploads/)
        }

        $model->actualizar((int) $id, [
            'nombre' => $nombre,
            'imagen' => $ruta,          // null = conserva la actual
            'activo' => isset($_POST['activo']),
            'orden'  => (int) ($_POST['orden'] ?? 0),
        ]);
        Audit::cambio('actualizar', 'cliente_logo', (int) $id, 'Editó el logo de cliente "' . $nombre . '"');
        flash('portal_ok', 'Logo actualizado.');
        $this->redirect('/admin/logos-clientes');
    }

    public function delete(string $id): void
    {
        Auth::authorize('clientes_logos.gestionar');
        $this->guard('/admin/logos-clientes');
        $model = new ClienteLogo();
        $logo  = $model->find((int) $id);
        if ($logo) {
            Upload::borrar($logo['imagen']);
            $model->eliminar((int) $id);
            Audit::cambio('eliminar', 'cliente_logo', (int) $id, 'Eliminó el logo de cliente "' . ($logo['nombre'] ?? '') . '"');
            flash('portal_ok', 'Logo eliminado.');
        }
        $this->redirect('/admin/logos-clientes');
    }

    /* ------------------------------------------------ Utilidades ------ */

    private function subirLogo(): ?string
    {
        $res = Upload::logo($_FILES['imagen'] ?? null, 'clientes');
        if (($res['error'] ?? null) !== null) {
            flash('portal_error', $res['error']);
        }
        return $res['ruta'] ?? null;
    }

    private function form(?array $logo): void
    {
        $this->render('admin/logo_cliente_form', [
            'title'  => ($logo ? 'Editar' : 'Nuevo') . ' logo de cliente — Panel RYM',
            'active' => 'logos_clientes',
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
