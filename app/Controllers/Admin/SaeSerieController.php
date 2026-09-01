<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\SaeSerieConsecutivo;

/**
 * Reconciliación de los consecutivos de SAE por serie (Fase 7.4). El sistema
 * los avanza solo al exportar; esta pantalla permite corregirlos a mano si
 * el conteo se desfasa del que lleva SAE (por una exportación cancelada,
 * un ajuste manual en SAE, etc.).
 */
class SaeSerieController extends BaseController
{
    public function index(): void
    {
        Auth::authorize('pedidos.sincronizar_erp');
        $this->render('admin/series_sae', [
            'title'  => 'Series de SAE — Panel RYM',
            'active' => 'pedidos',
            'series' => (new SaeSerieConsecutivo())->todas(),
        ]);
    }

    public function actualizar(): void
    {
        Auth::authorize('pedidos.sincronizar_erp');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/series-sae');
        }

        $serie = strtoupper(trim((string) ($_POST['serie'] ?? '')));
        $valor = trim((string) ($_POST['ultimo_consecutivo'] ?? ''));
        if (!in_array($serie, SaeSerieConsecutivo::SERIES, true) || $valor === '' || !ctype_digit($valor)) {
            flash('portal_error', 'Captura un consecutivo válido (solo números).');
            $this->redirect('/admin/series-sae');
        }

        (new SaeSerieConsecutivo())->establecer($serie, (int) $valor);
        \App\Core\Audit::cambio('actualizar', 'sae_serie_consecutivo', null,
            'Corrigió el consecutivo de la serie "' . $serie . '" a ' . (int) $valor);
        flash('portal_ok', 'Consecutivo de la serie "' . $serie . '" actualizado.');
        $this->redirect('/admin/series-sae');
    }
}
