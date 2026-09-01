<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Permiso;

class UsuarioController extends BaseController
{
    public function index(): void
    {
        Auth::authorize('usuarios.ver');
        $this->render('admin/usuarios', [
            'title'    => 'Usuarios internos — Panel RYM',
            'active'   => 'usuarios',
            'usuarios' => (new Usuario())->internos(),
        ]);
    }

    public function create(): void
    {
        Auth::authorize('usuarios.gestionar');
        $this->form(null);
    }

    public function store(): void
    {
        Auth::authorize('usuarios.gestionar');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/usuarios');
        }

        $model    = new Usuario();
        $nombre   = str_clean($_POST['nombre'] ?? '', 120);
        $email    = str_clean($_POST['email'] ?? '', 191);
        $rolId    = (int) ($_POST['rol_id'] ?? 0);
        $password = (string) ($_POST['password'] ?? '');

        $errores = [];
        if (!nombre_valido($nombre))                        $errores[] = 'El nombre solo puede contener letras, espacios y . - \'.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $errores[] = 'Correo inválido.';
        elseif ($model->emailExiste($email))                $errores[] = 'Ese correo ya existe.';
        if ($rolId <= 0)                                    $errores[] = 'Selecciona un rol.';
        elseif (!$this->rolAsignable($rolId))               $errores[] = 'No puedes asignar un rol con más permisos que los tuyos.';
        $errores = array_merge($errores, password_errores($password));

        if ($errores) {
            flash('portal_error', implode(' ', $errores));
            $this->redirect('/admin/usuarios/nuevo');
        }

        $vend = $this->datosVendedor($rolId);
        $nuevoId = $model->crearInterno([
            'nombre'         => $nombre,
            'email'          => $email,
            'password'       => $password,
            'rol_id'         => $rolId,
            'clave_vendedor' => $vend['clave_vendedor'],
            'comision'       => $vend['comision'],
        ]);
        \App\Core\Audit::cambio('crear', 'usuario', $nuevoId, 'Creó al usuario interno ' . $email);
        flash('portal_ok', 'Usuario interno creado.');
        $this->redirect('/admin/usuarios');
    }

    public function edit(string $id): void
    {
        Auth::authorize('usuarios.gestionar');
        $this->form($this->objetivoInterno((int) $id));
    }

    public function update(string $id): void
    {
        Auth::authorize('usuarios.gestionar');
        if (!csrf_verify($_POST['_csrf'] ?? null)) {
            flash('portal_error', 'La sesión expiró.');
            $this->redirect('/admin/usuarios');
        }

        // Solo usuarios internos: las cuentas de cliente se administran en su módulo.
        $objetivo = $this->objetivoInterno((int) $id);
        // Nadie ajusta su propio nivel de acceso desde aquí (evita autoconcederse permisos).
        $esSiMismo = (int) $id === (int) $this->usuario['id'];

        $model  = new Usuario();
        $nombre = str_clean($_POST['nombre'] ?? '', 120);
        $rolId  = $esSiMismo ? (int) $objetivo['rol_id'] : (int) ($_POST['rol_id'] ?? 0);
        $activo = $esSiMismo ? ((int) $objetivo['activo'] === 1) : isset($_POST['activo']);

        if (!nombre_valido($nombre) || $rolId <= 0) {
            flash('portal_error', 'Nombre válido (solo letras, espacios y . - \') y rol son obligatorios.');
            $this->redirect('/admin/usuarios/' . (int) $id . '/editar');
        }
        // Conservar el rol que ya tenía siempre se permite (no eleva privilegios).
        if (!$esSiMismo && $rolId !== (int) $objetivo['rol_id'] && !$this->rolAsignable($rolId)) {
            flash('portal_error', 'No puedes asignar un rol con más permisos que los tuyos.');
            $this->redirect('/admin/usuarios/' . (int) $id . '/editar');
        }

        $vend = $this->datosVendedor($rolId);
        $model->actualizarInterno((int) $id, [
            'nombre'         => $nombre,
            'rol_id'         => $rolId,
            'clave_vendedor' => $vend['clave_vendedor'],
            'comision'       => $vend['comision'],
            'activo'         => $activo,
        ]);

        // Cambio de contraseña opcional.
        $password = (string) ($_POST['password'] ?? '');
        if ($password !== '') {
            $errPass = password_errores($password);
            if ($errPass) {
                flash('portal_error', implode(' ', $errPass));
                $this->redirect('/admin/usuarios/' . (int) $id . '/editar');
            }
            $model->cambiarPassword((int) $id, $password);
        }

        if (!$esSiMismo) {
            [$conceder, $revocar, $bloqueados] = $this->overridesDelPost();
            $model->guardarOverrides((int) $id, $conceder, $revocar);
            if ($bloqueados) {
                flash('portal_error', 'No se concedieron ' . $bloqueados
                    . ' permiso(s) porque tú no los tienes; el resto se guardó.');
            }
        }

        \App\Core\Audit::cambio('actualizar', 'usuario', (int) $id, 'Editó al usuario interno "' . $nombre . '"');
        flash('portal_ok', $esSiMismo
            ? 'Tus datos se actualizaron. El rol y los permisos propios no se modifican desde aquí.'
            : 'Usuario actualizado.');
        $this->redirect('/admin/usuarios');
    }

    /* --------------------------------- Control de privilegios -------- */

    /**
     * Carga el usuario a editar exigiendo que sea INTERNO. Las cuentas de
     * cliente no se tocan desde este módulo (cambiarles la contraseña aquí
     * permitiría suplantarlas en el portal).
     */
    private function objetivoInterno(int $id): array
    {
        $usuario = (new Usuario())->autenticado($id);
        if (!$usuario) {
            flash('portal_error', 'Usuario no encontrado.');
            $this->redirect('/admin/usuarios');
        }
        if (($usuario['rol_slug'] ?? 'cliente') === 'cliente') {
            flash('portal_error', 'Esa cuenta es de cliente; adminístrala desde Clientes.');
            $this->redirect('/admin/usuarios');
        }
        return $usuario;
    }

    /**
     * Permisos que RESTRINGEN en vez de conceder: tenerlos reduce el alcance de
     * quien los porta, así que no cuentan para decidir si un rol es asignable
     * (si contaran, un administrador no podría dar de alta vendedores).
     */
    private const PERMISOS_RESTRICTIVOS = ['ventas.solo_asignados'];

    /**
     * ¿Puede quien edita asignar este rol?
     *
     * - Con 'roles.gestionar' sí, siempre: ese permiso ya deja redefinir los
     *   permisos de cualquier rol, así que es la llave de administrador.
     * - Sin él, solo roles cuyos permisos ya posee: así nadie reparte (ni se
     *   autoconcede) más autoridad de la que tiene.
     */
    private function rolAsignable(int $rolId): bool
    {
        $rolModel = new Rol();
        if (!$rolModel->find($rolId)) {
            return false;
        }
        if (Auth::can('roles.gestionar')) {
            return true;
        }
        $exigidos = array_diff($rolModel->permisos($rolId), self::PERMISOS_RESTRICTIVOS);
        return array_diff($exigidos, Auth::permissions()) === [];
    }

    /**
     * Lee los overrides del POST. Conceder solo se admite para permisos que
     * quien edita ya tiene; revocar siempre se admite (reduce privilegios).
     * @return array{0:int[], 1:int[], 2:int} conceder, revocar, concesiones descartadas
     */
    private function overridesDelPost(): array
    {
        $claves   = (new Permiso())->clavesPorId();
        $propios  = Auth::permissions();
        $conceder = [];
        $revocar  = [];
        $bloqueados = 0;

        foreach ((array) ($_POST['override'] ?? []) as $permisoId => $accion) {
            $pid = (int) $permisoId;
            if (!isset($claves[$pid])) {
                continue;
            }
            if ($accion === 'grant') {
                if (in_array($claves[$pid], $propios, true)) {
                    $conceder[] = $pid;
                } else {
                    $bloqueados++;
                }
            } elseif ($accion === 'deny') {
                $revocar[] = $pid;
            }
        }
        return [$conceder, $revocar, $bloqueados];
    }

    /** Lee y normaliza el % de comisión del POST (0–100). Devuelve '' si no viene. */
    private function comision(): string
    {
        $raw = str_replace(',', '.', trim((string) ($_POST['comision'] ?? '')));
        if ($raw === '' || !is_numeric($raw)) {
            return '';
        }
        return (string) max(0, min(100, (float) $raw));
    }

    /** Solo el rol Ventas puede tener clave de vendedor y comisión. */
    private function datosVendedor(int $rolId): array
    {
        $rol = (new Rol())->find($rolId);
        if (!$rol || $rol['slug'] !== 'ventas') {
            return ['clave_vendedor' => '', 'comision' => ''];
        }
        return [
            'clave_vendedor' => str_clean($_POST['clave_vendedor'] ?? '', 20),
            'comision'       => $this->comision(),
        ];
    }

    private function form(?array $usuario): void
    {
        $rolModel = new Rol();
        $rolPermisoIds = $usuario ? $rolModel->permisoIds((int) $usuario['rol_id']) : [];

        // La vista solo ofrece lo que este usuario puede repartir (el back-end
        // vuelve a validarlo; esto es para no mostrar opciones que fallarían).
        $roles = array_values(array_filter(
            $rolModel->todos(),
            fn ($r) => $this->rolAsignable((int) $r['id'])
                || ($usuario && (int) $r['id'] === (int) $usuario['rol_id'])
        ));

        $this->render('admin/usuario_form', [
            'title'          => ($usuario ? 'Editar' : 'Nuevo') . ' usuario — Panel RYM',
            'active'         => 'usuarios',
            'usuarioEdit'    => $usuario,
            'roles'          => $roles,
            'permisosGrupos' => (new Permiso())->agrupados(),
            'overrides'      => $usuario ? (new Usuario())->overrides((int) $usuario['id']) : [],
            'rolPermisoIds'  => $rolPermisoIds,
            'permisosPropios' => Auth::permissions(),
            'esSiMismo'      => $usuario && (int) $usuario['id'] === (int) $this->usuario['id'],
        ]);
    }
}
