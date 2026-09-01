<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Audit;
use App\Core\Upload;
use App\Models\Modal;

class ModalController extends BaseController
{
    public function index(): void
    {
        Auth::authorize('modales.gestionar');
        $this->render('admin/modales', [
            'title'   => 'Modales del sitio — Panel RYM',
            'active'  => 'modales',
            'modales' => (new Modal())->todos(),
        ]);
    }

    public function create(): void
    {
        Auth::authorize('modales.gestionar');
        $this->form(null);
    }

    public function store(): void
    {
        Auth::authorize('modales.gestionar');
        $this->guard('/admin/modales');

        $model  = new Modal();
        $titulo = str_clean($_POST['titulo'] ?? '', 150);
        $ruta   = $this->subirImagen();

        if ($titulo === '') {
            flash('portal_error', 'El título es obligatorio.');
            $this->redirect('/admin/modales/nuevo');
        }
        if ($ruta === null) {
            flash('portal_error', 'Sube una imagen (JPG, PNG o WebP, máx. 3 MB).');
            $this->redirect('/admin/modales/nuevo');
        }

        $id = $model->crear([
            'titulo' => $titulo,
            'imagen' => $ruta,
            'enlace' => $this->enlace(),
            'activo' => isset($_POST['activo']),
            'orden'  => (int) ($_POST['orden'] ?? 0),
        ]);
        Audit::cambio('crear', 'modal', $id, 'Creó el modal "' . $titulo . '"');
        flash('portal_ok', 'Modal creado.');
        $this->redirect('/admin/modales');
    }

    public function edit(string $id): void
    {
        Auth::authorize('modales.gestionar');
        $modal = (new Modal())->find((int) $id);
        if (!$modal) {
            flash('portal_error', 'Modal no encontrado.');
            $this->redirect('/admin/modales');
        }
        $this->form($modal);
    }

    public function update(string $id): void
    {
        Auth::authorize('modales.gestionar');
        $this->guard('/admin/modales');

        $model = new Modal();
        $modal = $model->find((int) $id);
        if (!$modal) {
            $this->redirect('/admin/modales');
        }
        $titulo = str_clean($_POST['titulo'] ?? '', 150);
        if ($titulo === '') {
            flash('portal_error', 'El título es obligatorio.');
            $this->redirect('/admin/modales/' . (int) $id . '/editar');
        }

        $ruta = $this->subirImagen();
        if ($ruta !== null) {
            Upload::borrar($modal['imagen']); // reemplaza la anterior
        }

        $model->actualizar((int) $id, [
            'titulo' => $titulo,
            'imagen' => $ruta,            // null = conserva la actual
            'enlace' => $this->enlace(),
            'activo' => isset($_POST['activo']),
            'orden'  => (int) ($_POST['orden'] ?? 0),
        ]);
        Audit::cambio('actualizar', 'modal', (int) $id, 'Editó el modal "' . $titulo . '"');
        flash('portal_ok', 'Modal actualizado.');
        $this->redirect('/admin/modales');
    }

    public function delete(string $id): void
    {
        Auth::authorize('modales.gestionar');
        $this->guard('/admin/modales');
        $model = new Modal();
        $modal = $model->find((int) $id);
        if ($modal) {
            Upload::borrar($modal['imagen']);
            $model->eliminar((int) $id);
            Audit::cambio('eliminar', 'modal', (int) $id, 'Eliminó el modal "' . ($modal['titulo'] ?? '') . '"');
            flash('portal_ok', 'Modal eliminado.');
        }
        $this->redirect('/admin/modales');
    }

    /* ------------------------------------------------ Utilidades ------ */

    private function subirImagen(): ?string
    {
        $res = Upload::imagenes($_FILES['imagen'] ?? null, 'modales', 1);
        if ($res['errores']) {
            flash('portal_error', implode(' ', array_unique($res['errores'])));
        }
        return $res['rutas'][0] ?? null;
    }

    /**
     * Enlace del modal, restringido a http/https: este valor se emite en un href
     * que ve todo visitante del sitio público, así que no admite esquemas
     * ejecutables (javascript:, data:).
     */
    private function enlace(): ?string
    {
        $url = str_clean($_POST['enlace'] ?? '', 255);
        return url_http_valida($url) ? $url : null;
    }

    private function form(?array $modal): void
    {
        $this->render('admin/modal_form', [
            'title'  => ($modal ? 'Editar' : 'Nuevo') . ' modal — Panel RYM',
            'active' => 'modales',
            'modal'  => $modal,
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
