<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Páginas de contenido estático del sitio.
 */
class PageController extends Controller
{
    public function nosotros(): void
    {
        $this->view('pages/nosotros', [
            'title'           => 'Nosotros — Importadora RYM',
            'metaDescription' => '35 años fabricando, imprimiendo y distribuyendo insumos para la industria alimentaria.',
            'active'          => 'nosotros',
            'pageTitle'       => 'Nosotros',
            'pageSubtitle'    => 'Líderes en manufactura, impresión y distribución de envases para la industria alimentaria.',
            'breadcrumb'      => [
                ['label' => 'Inicio', 'url' => url('/')],
                ['label' => 'Nosotros'],
            ],
        ]);
    }

    public function personalizacion(): void
    {
        $this->view('pages/personalizacion', [
            'title'           => 'Laboratorio de impresión — Importadora RYM',
            'metaDescription' => 'Personaliza vasos y empaques con tu marca. Impresión desde 1,000 piezas con tintas grado alimenticio.',
            'active'          => 'personalizacion',
            'pageTitle'       => 'Laboratorio de impresión',
            'pageSubtitle'    => 'Llevamos tu marca en cada empaque.',
            'breadcrumb'      => [
                ['label' => 'Inicio', 'url' => url('/')],
                ['label' => 'Personalización'],
            ],
        ]);
    }

    public function reciclaje(): void
    {
        $this->view('pages/reciclaje', [
            'title'           => 'Reciclaje — Importadora RYM',
            'metaDescription' => 'Productos ecológicos y materiales amigables con el medio ambiente para tu negocio.',
            'active'          => 'reciclaje',
            'pageTitle'       => 'Compromiso ecológico',
            'pageSubtitle'    => 'Cuida el medio ambiente con nuestros productos ecológicos.',
            'breadcrumb'      => [
                ['label' => 'Inicio', 'url' => url('/')],
                ['label' => 'Reciclaje'],
            ],
        ]);
    }

    public function avisoPrivacidad(): void
    {
        $this->view('pages/aviso', [
            'title'           => 'Aviso de privacidad — Importadora RYM',
            'metaDescription' => 'Aviso de privacidad de Importadora RYM S.A. de C.V. conforme a la LFPDPPP.',
            'active'          => '',
            'pageTitle'       => 'Aviso de privacidad',
            'pageSubtitle'    => 'Protección de tus datos personales conforme a la LFPDPPP.',
            'breadcrumb'      => [
                ['label' => 'Inicio', 'url' => url('/')],
                ['label' => 'Aviso de privacidad'],
            ],
        ]);
    }
}
