<?php

namespace App\Controllers;

use App\Models\Usuario;

class PerfilController extends PortalBaseController
{
    public function edit(): void
    {
        $this->render('portal/perfil', [
            'title'  => 'Mi perfil — Portal RYM',
            'active' => 'perfil',
        ]);
    }

    public function update(): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró. Vuelve a intentarlo.');
            $this->redirect('/portal/perfil');
        }

        $nombre   = str_clean($_POST['nombre'] ?? '', 120);
        $empresa  = str_clean($_POST['empresa'] ?? '', 150);
        $telefono = str_clean($_POST['telefono'] ?? '', 30);
        $rfc      = str_clean($_POST['rfc'] ?? '', 20);
        $calle            = str_clean($_POST['calle'] ?? '', 150);
        $numeroExt        = str_clean($_POST['numero_ext'] ?? '', 20);
        $numeroInt        = str_clean($_POST['numero_int'] ?? '', 20);
        $colonia          = str_clean($_POST['colonia'] ?? '', 100);
        $codigoPostal     = str_clean($_POST['codigo_postal'] ?? '', 5);
        $delegacionMunicipio = str_clean($_POST['delegacion_municipio'] ?? '', 100);
        $estadoDireccion  = str_clean($_POST['estado_direccion'] ?? '', 100);
        $referencias      = str_clean($_POST['referencias'] ?? '', 255);

        $errores = [];
        if (!nombre_valido($nombre))                       $errores[] = 'El nombre solo puede contener letras, espacios y . - \'.';
        if ($empresa !== '' && !empresa_valida($empresa))  $errores[] = 'La empresa contiene caracteres no permitidos.';
        if ($telefono !== '' && !telefono_valido($telefono)) $errores[] = 'El teléfono solo admite dígitos y + - ( ).';
        if ($rfc !== '' && !rfc_valido($rfc))              $errores[] = 'El RFC no tiene un formato válido.';
        if ($calle === '' || !direccion_valida($calle))     $errores[] = 'Captura una calle válida.';
        if ($numeroExt === '')                              $errores[] = 'Captura el número exterior.';
        if ($colonia === '' || !direccion_valida($colonia)) $errores[] = 'Captura una colonia válida.';
        if (!codigo_postal_valido($codigoPostal))           $errores[] = 'El código postal debe tener 5 dígitos.';
        if ($delegacionMunicipio === '' || !direccion_valida($delegacionMunicipio)) $errores[] = 'Captura una delegación o municipio válido.';
        if ($estadoDireccion === '' || !direccion_valida($estadoDireccion)) $errores[] = 'Captura un estado válido.';

        if ($errores) {
            flash('portal_error', implode(' ', $errores));
            $this->redirect('/portal/perfil');
        }

        (new Usuario())->actualizarPerfil((int) $this->usuario['id'], [
            'nombre'   => $nombre,
            'empresa'  => $empresa ?: null,
            'telefono' => $telefono ?: null,
            'rfc'      => $rfc ?: null,
            'calle'            => $calle,
            'numero_ext'       => $numeroExt,
            'numero_int'       => $numeroInt ?: null,
            'colonia'          => $colonia,
            'codigo_postal'    => $codigoPostal,
            'delegacion_municipio' => $delegacionMunicipio,
            'estado_direccion' => $estadoDireccion,
            'referencias'      => $referencias ?: null,
        ]);

        flash('portal_ok', 'Tus datos se actualizaron correctamente.');
        $this->redirect('/portal/perfil');
    }
}
