<?php

namespace App\Controllers;

use App\Core\Controller;

class ContactoController extends Controller
{
    public function index(): void
    {
        $this->view('pages/contacto', [
            'title'           => 'Contacto — Importadora RYM',
            'metaDescription' => 'Contáctanos para cotizar insumos y empaques. Tel. (55) 5612 1612, WhatsApp y correo.',
            'active'          => 'contacto',
            'pageTitle'       => 'Contacto',
            'pageSubtitle'    => 'Estamos para atenderte. Cotiza o resuelve tus dudas con un asesor.',
            'breadcrumb'      => [
                ['label' => 'Inicio', 'url' => url('/')],
                ['label' => 'Contacto'],
            ],
        ]);
    }
}
