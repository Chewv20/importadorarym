<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Xlsx;
use App\Models\EncuestaPedido;
use App\Models\EnvioEncuesta;

class EncuestaController extends BaseController
{
    public function index(): void
    {
        Auth::authorize('encuestas.ver');

        $model = new EncuestaPedido();
        $f  = $this->filtros();
        $pg = $this->paginar($model->contar($f), 20);

        $this->render('admin/encuestas', [
            'title'     => 'Encuestas de experiencia — Panel RYM',
            'active'    => 'encuestas',
            'metricas'  => $model->metricas($f),
            'encuestas' => $model->paginado($pg['perPage'], $pg['offset'], $f),
            'filtro'    => $f,
            'page'      => $pg['page'],
            'pages'     => $pg['pages'],
        ]);
    }

    /** Encuestas de satisfacción de ENTREGA (por envío, Fase 7.8) — distintas de las de proceso. */
    public function entregas(): void
    {
        Auth::authorize('encuestas.ver');

        $model = new EnvioEncuesta();
        $f  = $this->filtros();
        $pg = $this->paginar($model->contar($f), 20);

        $this->render('admin/encuestas_entrega', [
            'title'     => 'Encuestas de entrega — Panel RYM',
            'active'    => 'encuestas',
            'metricas'  => $model->metricas($f),
            'encuestas' => $model->paginado($pg['perPage'], $pg['offset'], $f),
            'filtro'    => $f,
            'page'      => $pg['page'],
            'pages'     => $pg['pages'],
        ]);
    }

    public function entregasExportar(): void
    {
        Auth::authorize('encuestas.ver');

        $rows = (new EnvioEncuesta())->exportar($this->filtros());
        if (count($rows) >= EnvioEncuesta::MAX_EXPORT) {
            flash('portal_error', 'La exportación se limitó a ' . EnvioEncuesta::MAX_EXPORT
                . ' registros. Acota el rango de fechas para obtener el resto.');
        }
        $filas = [];
        foreach ($rows as $r) {
            $filas[] = [
                date('d/m/Y H:i', strtotime($r['created_at'])),
                $r['pedido_folio'] ?? '',
                $r['cliente_nombre'] ?? '',
                $r['cliente_empresa'] ?? '',
                (int) $r['llego_completo'] === 1 ? 'Sí' : 'No',
                $r['que_falto'] ?? '',
                (int) $r['satisfaccion'],
                (int) $r['num_partidas'],
                $r['comentario'] ?? '',
            ];
        }
        $headers = ['Fecha', 'Pedido', 'Cliente', 'Empresa', 'Llegó completo', 'Qué faltó', 'Satisfacción', 'Partidas exportadas', 'Comentario'];
        $bin = Xlsx::crear($headers, $filas, 'Encuestas de entrega');
        $nombre = 'encuestas-entrega-' . date('Ymd-Hi') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: no-store');
        echo $bin;
        exit;
    }

    public function exportar(): void
    {
        Auth::authorize('encuestas.ver');

        $rows = (new EncuestaPedido())->exportar($this->filtros());
        if (count($rows) >= EncuestaPedido::MAX_EXPORT) {
            flash('portal_error', 'La exportación se limitó a ' . EncuestaPedido::MAX_EXPORT
                . ' registros. Acota el rango de fechas para obtener el resto.');
        }
        $filas = [];
        foreach ($rows as $r) {
            $filas[] = [
                date('d/m/Y H:i', strtotime($r['created_at'])),
                $r['pedido_folio'] ?? '',
                $r['cliente_nombre'] ?? '',
                $r['cliente_empresa'] ?? '',
                (int) $r['satisfaccion'],
                (int) $r['facilidad'],
                $r['nps'] !== null ? (int) $r['nps'] : '',
                $r['comentario'] ?? '',
            ];
        }
        $headers = ['Fecha', 'Pedido', 'Cliente', 'Empresa', 'Satisfacción', 'Facilidad', 'NPS', 'Comentario'];
        $bin = Xlsx::crear($headers, $filas, 'Encuestas');
        $nombre = 'encuestas-' . date('Ymd-Hi') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: no-store');
        echo $bin;
        exit;
    }

    private function filtros(): array
    {
        $fecha = static function (string $v): ?string {
            $v = trim($v);
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
        };
        return [
            'desde' => $fecha($_GET['desde'] ?? ''),
            'hasta' => $fecha($_GET['hasta'] ?? ''),
        ];
    }
}
