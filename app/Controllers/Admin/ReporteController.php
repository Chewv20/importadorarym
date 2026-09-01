<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Xlsx;
use App\Models\Pedido;
use App\Models\Cotizacion;

class ReporteController extends BaseController
{
    private const TIPOS = ['pedidos', 'leads', 'productos', 'clientes'];

    public function index(): void
    {
        Auth::authorize('reportes.ver');

        $this->render('admin/reportes', [
            'title'          => 'Reportes — Panel RYM',
            'active'         => 'reportes',
            'desde'          => date('Y-m-d', strtotime('-30 days')),
            'hasta'          => date('Y-m-d'),
            'estadosPedido'  => Pedido::ESTADOS,
            'estadosLead'    => Cotizacion::ESTADOS,
            'soloAsignados'  => $this->vendedorScope() !== null,
        ]);
    }

    /** Genera y descarga el reporte solicitado en Excel. */
    public function descargar(): void
    {
        Auth::authorize('reportes.ver');

        $tipo = $_GET['tipo'] ?? '';
        if (!in_array($tipo, self::TIPOS, true)) {
            flash('portal_error', 'Reporte no válido.');
            $this->redirect('/admin/reportes');
        }

        [$desde, $hasta] = $this->rango();
        $scope = $this->vendedorScope();

        [$titulo, $headers, $filas] = match ($tipo) {
            'pedidos'   => $this->reportePedidos($desde, $hasta, $scope),
            'leads'     => $this->reporteLeads($desde, $hasta, $scope),
            'productos' => $this->reporteProductos($desde, $hasta, $scope),
            'clientes'  => $this->reporteClientes($desde, $hasta, $scope),
        };

        $nombre = 'reporte-' . $tipo . '-' . date('Ymd-Hi') . '.xlsx';
        $this->descargarXlsx($nombre, $headers, $filas, $titulo);
    }

    /* --------------------------------- Reportes ---------------------- */

    private function reportePedidos(string $desde, string $hasta, ?int $scope): array
    {
        $estado = $this->estadoValido($_GET['estado'] ?? '', Pedido::ESTADOS);
        $rows = (new Pedido())->reportePedidos($desde, $hasta, $estado, $scope);
        $filas = [];
        foreach ($rows as $r) {
            $filas[] = [
                $r['folio'],
                date('d/m/Y H:i', strtotime($r['created_at'])),
                $r['cliente'],
                $r['empresa'] ?? '',
                ucfirst(str_replace('_', ' ', $r['estado'])),
                (int) $r['num_items'],
                number_format((float) $r['total'], 2, '.', ''),
                $r['erp_folio'] ?? '',
            ];
        }
        return ['Pedidos', ['Folio', 'Fecha', 'Cliente', 'Empresa', 'Estado', 'Partidas', 'Total', 'Folio ERP'], $filas];
    }

    private function reporteLeads(string $desde, string $hasta, ?int $scope): array
    {
        $estado = $this->estadoValido($_GET['estado'] ?? '', Cotizacion::ESTADOS);
        $rows = (new Cotizacion())->reporteLeads($desde, $hasta, $estado, $scope);
        $filas = [];
        foreach ($rows as $r) {
            $filas[] = [
                $r['folio'] ?? '',
                date('d/m/Y H:i', strtotime($r['created_at'])),
                $r['nombre'],
                $r['empresa'] ?? '',
                $r['email'],
                $r['telefono'] ?? '',
                $r['origen'],
                ucfirst($r['estado']),
                $r['total'] !== null ? number_format((float) $r['total'], 2, '.', '') : '',
            ];
        }
        return ['Cotizaciones', ['Folio', 'Fecha', 'Nombre', 'Empresa', 'Correo', 'Teléfono', 'Origen', 'Estado', 'Total'], $filas];
    }

    private function reporteProductos(string $desde, string $hasta, ?int $scope): array
    {
        $rows = (new Pedido())->reporteProductos($desde, $hasta, $scope);
        $filas = [];
        foreach ($rows as $r) {
            $filas[] = [
                $r['sku'] ?? '',
                $r['nombre'],
                (int) $r['cantidad'],
                (int) $r['pedidos'],
            ];
        }
        return ['Productos', ['SKU', 'Producto', 'Cantidad', 'Pedidos'], $filas];
    }

    private function reporteClientes(string $desde, string $hasta, ?int $scope): array
    {
        $rows = (new Pedido())->reporteClientes($desde, $hasta, $scope);
        $filas = [];
        foreach ($rows as $r) {
            $filas[] = [
                $r['nombre'],
                $r['empresa'] ?? '',
                (int) $r['num_pedidos'],
                number_format((float) $r['total'], 2, '.', ''),
            ];
        }
        return ['Clientes', ['Cliente', 'Empresa', 'Pedidos', 'Total'], $filas];
    }

    /* --------------------------------- Utilidades -------------------- */

    /** Lee y valida el rango de fechas del query; devuelve [desde 00:00:00, hasta 23:59:59]. */
    private function rango(): array
    {
        $desde = $this->fechaValida($_GET['desde'] ?? '') ?? date('Y-m-d', strtotime('-30 days'));
        $hasta = $this->fechaValida($_GET['hasta'] ?? '') ?? date('Y-m-d');
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        return [$desde . ' 00:00:00', $hasta . ' 23:59:59'];
    }

    /** Valida una fecha Y-m-d real; devuelve la fecha o null. */
    private function fechaValida(string $v): ?string
    {
        $v = trim($v);
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m)) {
            return null;
        }
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $v : null;
    }

    private function estadoValido(string $v, array $permitidos): ?string
    {
        return in_array($v, $permitidos, true) ? $v : null;
    }

    private function descargarXlsx(string $filename, array $headers, array $filas, string $hoja): void
    {
        $bin = Xlsx::crear($headers, $filas, $hoja);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: no-store');
        echo $bin;
        exit;
    }
}
