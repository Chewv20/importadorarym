<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Audit;
use App\Core\Xlsx;
use App\Models\Visita;
use App\Models\Anfitrion;
use App\Models\ChecadorDispositivo;

class VisitaController extends BaseController
{
    /* --------------------------------- Historial --------------------- */

    public function index(): void
    {
        Auth::authorize('visitas.ver');

        $model = new Visita();
        $f  = $this->filtrosHistorial();
        $pg = $this->paginar($model->contar($f), 20);

        $this->render('admin/visitas', [
            'title'       => 'Libreta de visitas — Panel RYM',
            'active'      => 'visitas',
            'visitas'     => $model->paginado($pg['perPage'], $pg['offset'], $f),
            'anfitriones' => (new Anfitrion())->todos(),
            'filtro'      => $f,
            'page'        => $pg['page'],
            'pages'       => $pg['pages'],
        ]);
    }

    /** Exporta a Excel el historial con los filtros aplicados. */
    public function exportar(): void
    {
        Auth::authorize('visitas.ver');

        $rows = (new Visita())->exportar($this->filtrosHistorial());
        if (count($rows) >= Visita::MAX_EXPORT) {
            flash('portal_error', 'La exportación se limitó a ' . Visita::MAX_EXPORT
                . ' registros. Acota el rango de fechas para obtener el resto.');
        }
        $filas = [];
        foreach ($rows as $r) {
            $filas[] = [
                date('d/m/Y H:i', strtotime($r['created_at'])),
                $r['nombre_visitante'],
                $r['empresa'] ?? '',
                $r['telefono'] ?? '',
                (int) $r['num_personas'],
                $r['motivo'] ?? '',
                $r['anfitrion_nombre'] ?? '',
                $r['anfitrion_area'] ?? '',
                $r['dispositivo_nombre'] ?? '',
            ];
        }
        $headers = ['Fecha', 'Visitante', 'Empresa', 'Teléfono', 'Personas', 'Motivo', 'Anfitrión', 'Área', 'Punto de registro'];

        $bin = Xlsx::crear($headers, $filas, 'Visitas');
        $nombre = 'visitas-' . date('Ymd-Hi') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: no-store');
        echo $bin;
        exit;
    }

    private function filtrosHistorial(): array
    {
        return [
            'anfitrion_id' => (int) ($_GET['anfitrion'] ?? 0) ?: null,
            'desde'        => $this->fecha($_GET['desde'] ?? ''),
            'hasta'        => $this->fecha($_GET['hasta'] ?? ''),
        ];
    }

    /* --------------------------------- Dispositivos ------------------ */

    public function dispositivos(): void
    {
        Auth::authorize('visitas.gestionar');
        $this->render('admin/visitas_dispositivos', [
            'title'        => 'Dispositivos de visitas — Panel RYM',
            'active'       => 'visitas',
            'dispositivos' => (new ChecadorDispositivo())->todos(),
            'enlace'       => flash('checador_enlace'),
        ]);
    }

    public function guardarDispositivo(): void
    {
        Auth::authorize('visitas.gestionar');
        $this->guard('/admin/visitas/dispositivos');

        $nombre = str_clean($_POST['nombre'] ?? '', 80);
        if ($nombre === '') {
            flash('portal_error', 'Escribe un nombre para el dispositivo.');
            $this->redirect('/admin/visitas/dispositivos');
        }
        $token = (new ChecadorDispositivo())->crear($nombre);
        Audit::cambio('crear', 'dispositivo_checador', null, 'Creó el dispositivo de visitas "' . $nombre . '"');
        flash('checador_enlace', $this->enlaceActivacion($token));
        flash('portal_ok', 'Dispositivo creado. Abre el enlace de activación UNA vez en la tablet.');
        $this->redirect('/admin/visitas/dispositivos');
    }

    public function revocarDispositivo(string $id): void
    {
        Auth::authorize('visitas.gestionar');
        $this->guard('/admin/visitas/dispositivos');
        (new ChecadorDispositivo())->revocar((int) $id);
        Audit::cambio('actualizar', 'dispositivo_checador', (int) $id, 'Revocó el dispositivo de visitas #' . (int) $id);
        flash('portal_ok', 'Dispositivo revocado. Ese equipo dejó de tener acceso al kiosco.');
        $this->redirect('/admin/visitas/dispositivos');
    }

    public function regenerarDispositivo(string $id): void
    {
        Auth::authorize('visitas.gestionar');
        $this->guard('/admin/visitas/dispositivos');
        $token = (new ChecadorDispositivo())->regenerarActivacion((int) $id);
        Audit::cambio('actualizar', 'dispositivo_checador', (int) $id, 'Regeneró el enlace del dispositivo de visitas #' . (int) $id);
        flash('checador_enlace', $this->enlaceActivacion($token));
        flash('portal_ok', 'Nuevo enlace de activación generado (el anterior dejó de servir).');
        $this->redirect('/admin/visitas/dispositivos');
    }

    /* --------------------------------- Anfitriones ------------------- */

    public function anfitriones(): void
    {
        Auth::authorize('visitas.gestionar');
        $this->render('admin/visitas_anfitriones', [
            'title'       => 'Anfitriones — Panel RYM',
            'active'      => 'visitas',
            'anfitriones' => (new Anfitrion())->todos(),
        ]);
    }

    public function guardarAnfitrion(): void
    {
        Auth::authorize('visitas.gestionar');
        $this->guard('/admin/visitas/anfitriones');

        $d = $this->datosAnfitrion();
        if ($err = $this->validarAnfitrion($d)) {
            flash('portal_error', $err);
            $this->redirect('/admin/visitas/anfitriones');
        }
        (new Anfitrion())->crear($d);
        Audit::cambio('crear', 'anfitrion', null, 'Agregó al anfitrión ' . $d['nombre']);
        flash('portal_ok', 'Anfitrión agregado.');
        $this->redirect('/admin/visitas/anfitriones');
    }

    public function actualizarAnfitrion(string $id): void
    {
        Auth::authorize('visitas.gestionar');
        $this->guard('/admin/visitas/anfitriones');

        $d = $this->datosAnfitrion();
        if ($err = $this->validarAnfitrion($d)) {
            flash('portal_error', $err);
            $this->redirect('/admin/visitas/anfitriones');
        }
        (new Anfitrion())->actualizar((int) $id, $d);
        Audit::cambio('actualizar', 'anfitrion', (int) $id, 'Actualizó al anfitrión ' . $d['nombre']);
        flash('portal_ok', 'Anfitrión actualizado.');
        $this->redirect('/admin/visitas/anfitriones');
    }

    public function eliminarAnfitrion(string $id): void
    {
        Auth::authorize('visitas.gestionar');
        $this->guard('/admin/visitas/anfitriones');
        (new Anfitrion())->eliminar((int) $id);
        Audit::cambio('eliminar', 'anfitrion', (int) $id, 'Eliminó al anfitrión #' . (int) $id);
        flash('portal_ok', 'Anfitrión eliminado.');
        $this->redirect('/admin/visitas/anfitriones');
    }

    /* --------------------------------- Utilidades -------------------- */

    private function datosAnfitrion(): array
    {
        return [
            'nombre' => str_clean($_POST['nombre'] ?? '', 120),
            'email'  => str_clean($_POST['email'] ?? '', 191),
            'area'   => str_clean($_POST['area'] ?? '', 100),
            'activo' => !empty($_POST['activo']),
        ];
    }

    private function validarAnfitrion(array $d): ?string
    {
        if ($d['nombre'] === '') {
            return 'Escribe el nombre del anfitrión.';
        }
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Captura un correo válido para el anfitrión.';
        }
        return null;
    }

    private function enlaceActivacion(string $token): string
    {
        return rtrim((string) config('app.url'), '/') . '/checador/activar/' . $token;
    }

    private function fecha(string $v): ?string
    {
        $v = trim($v);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
    }

    private function guard(string $back): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect($back);
        }
    }
}
