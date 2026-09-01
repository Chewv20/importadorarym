<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Audit;
use App\Models\Auditoria;

class AuditoriaController extends BaseController
{
    private const LOG_ERRORES = 'php-error.log';

    /** Bitácora de ingresos y cambios. */
    public function index(): void
    {
        Auth::authorize('auditoria.ver');

        $model = new Auditoria();

        $filtros = [
            'tipo'    => in_array($_GET['tipo'] ?? '', Auditoria::TIPOS, true) ? $_GET['tipo'] : null,
            'usuario' => str_clean($_GET['usuario'] ?? '', 191) ?: null,
            'desde'   => $this->fecha($_GET['desde'] ?? ''),
            'hasta'   => $this->fecha($_GET['hasta'] ?? ''),
        ];

        $pg = $this->paginar($model->contar($filtros), 30);

        $this->render('admin/auditoria', [
            'title'     => 'Auditoría — Panel RYM',
            'active'    => 'auditoria',
            'registros' => $model->paginado($pg['perPage'], $pg['offset'], $filtros),
            'filtros'   => $filtros,
            'page'      => $pg['page'],
            'pages'     => $pg['pages'],
        ]);
    }

    /** Devuelve la fecha si es un Y-m-d válido, o null. */
    private function fecha(string $valor): ?string
    {
        $valor = trim($valor);
        $d = \DateTime::createFromFormat('Y-m-d', $valor);
        return ($d && $d->format('Y-m-d') === $valor) ? $valor : null;
    }

    /** Líneas que muestra el visor. */
    private const LINEAS_VISOR = 300;

    /** Bytes que se leen desde el final del log (suficientes para LINEAS_VISOR). */
    private const COLA_BYTES = 262144; // 256 KB

    /** Visor del log de errores de la aplicación. */
    public function errores(): void
    {
        Auth::authorize('auditoria.ver');

        $file   = ROOT_PATH . '/storage/logs/' . self::LOG_ERRORES;
        $lineas = [];
        $tam    = 0;

        if (is_file($file)) {
            $tam    = (int) filesize($file);
            $lineas = $this->ultimasLineas($file, self::LINEAS_VISOR);
        }

        $this->render('admin/errores', [
            'title'    => 'Errores — Panel RYM',
            'active'   => 'errores',
            'lineas'   => $lineas,
            'tam'      => $tam,
            'existe'   => is_file($file),
            'truncado' => $tam > self::COLA_BYTES,
        ]);
    }

    /**
     * Últimas $n líneas leyendo solo la COLA del archivo.
     * Antes se cargaba el log entero en memoria para mostrar 300 líneas: con un
     * error recurrente en producción, la pantalla de diagnóstico moría por
     * memory_limit justo cuando hacía falta.
     *
     * @return string[] recientes primero
     */
    private function ultimasLineas(string $file, int $n): array
    {
        $fh = @fopen($file, 'rb');
        if (!$fh) {
            return [];
        }
        $tam    = (int) filesize($file);
        $inicio = max(0, $tam - self::COLA_BYTES);
        if ($inicio > 0) {
            fseek($fh, $inicio);
            fgets($fh); // descarta la línea partida por el corte
        }
        $cola = stream_get_contents($fh) ?: '';
        fclose($fh);

        $lineas = preg_split('/\R/', $cola, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return array_reverse(array_slice($lineas, -$n));
    }

    public function limpiarErrores(): void
    {
        Auth::authorize('auditoria.ver');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/errores');
        }

        $file = ROOT_PATH . '/storage/logs/' . self::LOG_ERRORES;
        if (is_file($file)) {
            file_put_contents($file, '');
        }
        Audit::cambio('eliminar', 'log_errores', null, 'Vació el log de errores');
        flash('portal_ok', 'Log de errores vaciado.');
        $this->redirect('/admin/errores');
    }
}
