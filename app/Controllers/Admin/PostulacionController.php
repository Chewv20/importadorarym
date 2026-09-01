<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Audit;
use App\Core\Mailer;
use App\Core\Upload;
use App\Models\Vacante;
use App\Models\Postulacion;

class PostulacionController extends BaseController
{
    public function index(): void
    {
        Auth::authorize('postulaciones.ver');

        $model = new Postulacion();
        $vacanteParam = $_GET['vacante'] ?? '';
        $f = [
            'vacante_id' => $vacanteParam === 'general' ? 'general' : ((int) $vacanteParam ?: null),
            'estado'     => array_key_exists($_GET['estado'] ?? '', Postulacion::ESTADOS) ? $_GET['estado'] : null,
        ];
        $pg = $this->paginar($model->contar($f), 20);

        $this->render('admin/postulaciones', [
            'title'         => 'Postulaciones — Panel RYM',
            'active'        => 'postulaciones',
            'postulaciones' => $model->paginado($pg['perPage'], $pg['offset'], $f),
            'vacantes'      => (new Vacante())->todas(),
            'estados'       => Postulacion::ESTADOS,
            'filtro'        => $f,
            'page'          => $pg['page'],
            'pages'         => $pg['pages'],
        ]);
    }

    public function show(string $id): void
    {
        Auth::authorize('postulaciones.ver');
        $p = (new Postulacion())->find((int) $id);
        if (!$p) {
            flash('portal_error', 'Postulación no encontrada.');
            $this->redirect('/admin/postulaciones');
        }
        $this->render('admin/postulacion_detalle', [
            'title'   => 'Postulación de ' . $p['nombre'] . ' — Panel RYM',
            'active'  => 'postulaciones',
            'p'       => $p,
            'estados' => Postulacion::ESTADOS,
            'tieneCv' => Upload::documentoRuta('cvs', $p['cv_archivo']) !== null,
        ]);
    }

    /** Descarga el CV (stream desde storage/, nunca accesible por URL pública). */
    public function descargarCv(string $id): void
    {
        Auth::authorize('postulaciones.ver');
        $p = (new Postulacion())->find((int) $id);
        $ruta = $p ? Upload::documentoRuta('cvs', $p['cv_archivo']) : null;
        if (!$ruta) {
            flash('portal_error', 'El CV no está disponible.');
            $this->redirect('/admin/postulaciones');
        }
        $this->streamFile($ruta, 'CV-' . slugify($p['nombre']) . '.pdf', 'application/pdf');
    }

    public function updateEstado(string $id): void
    {
        Auth::authorize('postulaciones.gestionar');
        $this->guard($id);
        $estado = $_POST['estado'] ?? '';
        if (array_key_exists($estado, Postulacion::ESTADOS)) {
            (new Postulacion())->cambiarEstado((int) $id, $estado);
            Audit::cambio('actualizar', 'postulacion', (int) $id, 'Cambió el estado de la postulación a "' . Postulacion::ESTADOS[$estado] . '"');
            flash('portal_ok', 'Estado actualizado.');
        }
        $this->redirect('/admin/postulaciones/' . (int) $id);
    }

    /** Agenda la entrevista y notifica al postulante por correo. */
    public function agendarCita(string $id): void
    {
        Auth::authorize('postulaciones.gestionar');
        $this->guard($id);

        $model = new Postulacion();
        $p = $model->find((int) $id);
        if (!$p) {
            $this->redirect('/admin/postulaciones');
        }

        $ts = strtotime((string) ($_POST['cita'] ?? ''));
        if (!$ts) {
            flash('portal_error', 'Captura una fecha y hora válidas para la cita.');
            $this->redirect('/admin/postulaciones/' . (int) $id);
        }

        $model->agendarCita((int) $id, date('Y-m-d H:i:s', $ts));
        Audit::cambio('actualizar', 'postulacion', (int) $id, 'Agendó entrevista para ' . date('d/m/Y H:i', $ts));

        Mailer::enviar($p['email'], 'Te invitamos a una entrevista — Importadora RYM', 'postulacion_cita', [
            'nombre'  => $p['nombre'],
            'vacante' => $p['vacante_titulo'] ?? 'la vacante',
            'fecha'   => date('d/m/Y \a \l\a\s H:i', $ts),
        ]);

        flash('portal_ok', 'Entrevista agendada. Se notificó al candidato por correo.');
        $this->redirect('/admin/postulaciones/' . (int) $id);
    }

    private function guard(string $id): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/postulaciones/' . (int) $id);
        }
    }
}
