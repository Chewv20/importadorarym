<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Usuario extends Model
{
    /**
     * Coste de bcrypt de toda la app. Unificarlo importa por dos razones: las
     * cuentas quedan igual de protegidas, y el tiempo de verificación deja de
     * variar entre usuarios (una diferencia medible delata qué cuentas existen).
     * Las contraseñas con un coste anterior se migran al iniciar sesión.
     */
    public const HASH_ALGO = PASSWORD_BCRYPT;
    public const HASH_OPTS = ['cost' => 12];

    /** Hash de contraseña con los parámetros de la app. */
    public static function hash(string $password): string
    {
        return password_hash($password, self::HASH_ALGO, self::HASH_OPTS);
    }

    public function porEmail(string $email): ?array
    {
        $st = $this->db->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = 1");
        $st->execute([$email]);
        return $st->fetch() ?: null;
    }

    /** Usuario con su rol (slug/nombre) para la sesión autenticada. */
    public function autenticado(int $id): ?array
    {
        $st = $this->db->prepare(
            "SELECT u.*, r.slug AS rol_slug, r.nombre AS rol_nombre
               FROM usuarios u
               LEFT JOIN roles r ON r.id = u.rol_id
              WHERE u.id = ?"
        );
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /**
     * Permisos efectivos = permisos del rol, aplicando los overrides
     * por usuario (concedido=1 agrega, concedido=0 quita).
     *
     * @return string[] lista de claves de permiso
     */
    public function permisosEfectivos(int $usuarioId, ?int $rolId): array
    {
        $set = [];

        if ($rolId !== null) {
            $st = $this->db->prepare(
                "SELECT p.clave FROM rol_permiso rp JOIN permisos p ON p.id = rp.permiso_id WHERE rp.rol_id = ?"
            );
            $st->execute([$rolId]);
            foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $clave) {
                $set[$clave] = true;
            }
        }

        $st = $this->db->prepare(
            "SELECT p.clave, up.concedido FROM usuario_permiso up JOIN permisos p ON p.id = up.permiso_id WHERE up.usuario_id = ?"
        );
        $st->execute([$usuarioId]);
        foreach ($st->fetchAll() as $row) {
            if ((int) $row['concedido'] === 1) {
                $set[$row['clave']] = true;
            } else {
                unset($set[$row['clave']]);
            }
        }

        return array_keys($set);
    }

    /** Sin filtro de activo/aprobado (para el flujo de login). */
    public function buscarPorEmail(string $email): ?array
    {
        $st = $this->db->prepare("SELECT * FROM usuarios WHERE email = ?");
        $st->execute([$email]);
        return $st->fetch() ?: null;
    }

    public function emailExiste(string $email): bool
    {
        $st = $this->db->prepare("SELECT 1 FROM usuarios WHERE email = ?");
        $st->execute([$email]);
        return (bool) $st->fetchColumn();
    }

    public function actualizarPerfil(int $id, array $data): void
    {
        $st = $this->db->prepare(
            "UPDATE usuarios SET nombre = ?, empresa = ?, telefono = ?, rfc = ?,
                calle = ?, numero_ext = ?, numero_int = ?, colonia = ?,
                codigo_postal = ?, delegacion_municipio = ?, estado_direccion = ?, referencias = ?
             WHERE id = ?"
        );
        $st->execute([
            $data['nombre'],
            $data['empresa'] ?? null,
            $data['telefono'] ?? null,
            $data['rfc'] ?? null,
            $data['calle'] ?? null,
            $data['numero_ext'] ?? null,
            $data['numero_int'] ?? null,
            $data['colonia'] ?? null,
            $data['codigo_postal'] ?? null,
            $data['delegacion_municipio'] ?? null,
            $data['estado_direccion'] ?? null,
            $data['referencias'] ?? null,
            $id,
        ]);
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM usuarios WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function crear(array $data): int
    {
        $rolId = $data['rol_id'] ?? $this->rolIdPorSlug($data['rol_slug'] ?? 'cliente');

        $sql = "INSERT INTO usuarios (nombre, email, password, rol_id, empresa, telefono, rfc,
                                     calle, numero_ext, numero_int, colonia, codigo_postal,
                                     delegacion_municipio, estado_direccion, referencias,
                                     verificacion_token, verificacion_expira_en)
                VALUES (:nombre, :email, :password, :rol_id, :empresa, :telefono, :rfc,
                        :calle, :numero_ext, :numero_int, :colonia, :codigo_postal,
                        :delegacion_municipio, :estado_direccion, :referencias,
                        :verificacion_token, DATE_ADD(NOW(), INTERVAL :vig HOUR))";

        $this->db->prepare($sql)->execute([
            ':nombre'   => $data['nombre'],
            ':email'    => $data['email'],
            ':password' => self::hash($data['password']),
            ':rol_id'   => $rolId,
            ':empresa'  => $data['empresa'] ?? null,
            ':telefono' => $data['telefono'] ?? null,
            ':rfc'      => $data['rfc'] ?? null,
            ':calle'            => $data['calle'] ?? null,
            ':numero_ext'       => $data['numero_ext'] ?? null,
            ':numero_int'       => $data['numero_int'] ?? null,
            ':colonia'          => $data['colonia'] ?? null,
            ':codigo_postal'    => $data['codigo_postal'] ?? null,
            ':delegacion_municipio' => $data['delegacion_municipio'] ?? null,
            ':estado_direccion' => $data['estado_direccion'] ?? null,
            ':referencias'      => $data['referencias'] ?? null,
            ':verificacion_token' => $data['verificacion_token'] ?? null,
            ':vig'      => self::VERIFICACION_HORAS,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** Crea un usuario interno (staff): activo y aprobado por defecto. */
    public function crearInterno(array $data): int
    {
        $sql = "INSERT INTO usuarios (nombre, email, password, rol_id, clave_vendedor, comision, activo, aprobado)
                VALUES (:nombre, :email, :password, :rol_id, :clave_vendedor, :comision, 1, 1)";
        $this->db->prepare($sql)->execute([
            ':nombre'         => $data['nombre'],
            ':email'          => $data['email'],
            ':password'       => self::hash($data['password']),
            ':rol_id'         => (int) $data['rol_id'],
            ':clave_vendedor' => ($data['clave_vendedor'] ?? '') ?: null,
            ':comision'       => isset($data['comision']) && $data['comision'] !== '' ? (float) $data['comision'] : null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    private function rolIdPorSlug(string $slug): ?int
    {
        $st = $this->db->prepare("SELECT id FROM roles WHERE slug = ?");
        $st->execute([$slug]);
        $id = $st->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    /* --------------------------------- Admin: clientes --------------- */

    public function clientesPaginado(int $limit, int $offset, bool $soloPendientes = false, ?int $vendedorId = null): array
    {
        $where = "r.slug = 'cliente'"
            . ($soloPendientes ? ' AND u.aprobado = 0' : '')
            . ($vendedorId !== null ? ' AND u.vendedor_id = :vend' : '');
        // sin_acceso_portal: en una sola consulta (sin N+1), para pintar el pill en el listado.
        $sql = "SELECT u.*, lp.nombre AS lista_productos_nombre,
                       EXISTS(
                           SELECT 1 FROM usuario_permiso up JOIN permisos p ON p.id = up.permiso_id
                            WHERE up.usuario_id = u.id AND p.clave = 'portal.acceder' AND up.concedido = 0
                       ) AS sin_acceso_portal
                  FROM usuarios u JOIN roles r ON r.id = u.rol_id
                  LEFT JOIN listas_productos lp ON lp.id = u.lista_productos_id
                 WHERE {$where} ORDER BY u.created_at DESC LIMIT :lim OFFSET :off";
        $st = $this->db->prepare($sql);
        if ($vendedorId !== null) {
            $st->bindValue(':vend', $vendedorId, PDO::PARAM_INT);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function contarClientes(bool $soloPendientes = false, ?int $vendedorId = null): int
    {
        $where = "r.slug = 'cliente'"
            . ($soloPendientes ? ' AND u.aprobado = 0' : '')
            . ($vendedorId !== null ? ' AND u.vendedor_id = :vend' : '');
        $st = $this->db->prepare(
            "SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE {$where}"
        );
        if ($vendedorId !== null) {
            $st->bindValue(':vend', $vendedorId, PDO::PARAM_INT);
        }
        $st->execute();
        return (int) $st->fetchColumn();
    }

    /**
     * Clientes activos (NO se exige aprobado: un vendedor puede levantar un pedido
     * a nombre de un cliente aunque su cuenta siga pendiente de aprobación — esa
     * aprobación solo controla que el cliente levante pedidos POR SU CUENTA en el
     * portal) para el <select> de "crear pedido a nombre de un cliente" — acotado
     * por vendedor si aplica. Tope 300: es un selector de apoyo, no el listado
     * principal (para eso está la búsqueda).
     */
    public function clientesElegibles(?int $vendedorId = null, int $limit = 300): array
    {
        $where = "r.slug = 'cliente' AND u.activo = 1"
            . ($vendedorId !== null ? ' AND u.vendedor_id = :vend' : '');
        $sql = "SELECT u.id, u.nombre, u.empresa, u.aprobado FROM usuarios u JOIN roles r ON r.id = u.rol_id
                 WHERE {$where} ORDER BY u.nombre LIMIT :lim";
        $st = $this->db->prepare($sql);
        if ($vendedorId !== null) {
            $st->bindValue(':vend', $vendedorId, PDO::PARAM_INT);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /**
     * Clientes activos por nombre/empresa (no se exige aprobado, ver
     * clientesElegibles()), para el autocompletar de "crear pedido a nombre de un
     * cliente" (panel). Acotado por vendedor si aplica.
     */
    public function buscarClientes(string $q, int $limit = 15, ?int $vendedorId = null): array
    {
        $where = "r.slug = 'cliente' AND u.activo = 1 AND (u.nombre LIKE :qn OR u.empresa LIKE :qe)"
            . ($vendedorId !== null ? ' AND u.vendedor_id = :vend' : '');
        $sql = "SELECT u.id, u.nombre, u.empresa, u.aprobado FROM usuarios u JOIN roles r ON r.id = u.rol_id
                 WHERE {$where} ORDER BY u.nombre LIMIT :lim";
        $st = $this->db->prepare($sql);
        $like = '%' . $q . '%';
        $st->bindValue(':qn', $like);
        $st->bindValue(':qe', $like);
        if ($vendedorId !== null) {
            $st->bindValue(':vend', $vendedorId, PDO::PARAM_INT);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /** ¿El cliente indicado está asignado a este vendedor? (control de acceso). */
    public function esClienteDeVendedor(int $clienteId, int $vendedorId): bool
    {
        $st = $this->db->prepare("SELECT 1 FROM usuarios WHERE id = ? AND vendedor_id = ?");
        $st->execute([$clienteId, $vendedorId]);
        return (bool) $st->fetchColumn();
    }

    public function aprobar(int $id): void
    {
        $this->db->prepare("UPDATE usuarios SET aprobado = 1, activo = 1 WHERE id = ?")->execute([$id]);
    }

    /** ¿Este usuario tiene revocado (override) el acceso al portal? */
    public function sinAccesoPortal(int $id): bool
    {
        $st = $this->db->prepare(
            "SELECT 1 FROM usuario_permiso up JOIN permisos p ON p.id = up.permiso_id
              WHERE up.usuario_id = ? AND p.clave = 'portal.acceder' AND up.concedido = 0"
        );
        $st->execute([$id]);
        return (bool) $st->fetchColumn();
    }

    /**
     * Activa/desactiva el acceso de un cliente al portal, sin tocar el resto de
     * sus permisos: mismo mecanismo de override que ya usa 'ventas.solo_asignados'
     * (usuario_permiso.concedido = 0 revoca). Un cliente sin acceso al portal
     * sigue siendo elegible para que un vendedor levante pedidos a su nombre
     * (clienteEsElegible() solo mira aprobado/activo, no permisos).
     */
    public function setAccesoPortal(int $id, bool $acceso): void
    {
        $permisoId = $this->db->query("SELECT id FROM permisos WHERE clave = 'portal.acceder'")->fetchColumn();
        if ($permisoId === false) {
            return;
        }
        if ($acceso) {
            // Restaurar: quitar el override, vuelve al permiso normal del rol.
            $this->db->prepare("DELETE FROM usuario_permiso WHERE usuario_id = ? AND permiso_id = ?")
                ->execute([$id, (int) $permisoId]);
        } else {
            $this->db->prepare(
                "INSERT INTO usuario_permiso (usuario_id, permiso_id, concedido) VALUES (?, ?, 0)
                 ON DUPLICATE KEY UPDATE concedido = 0"
            )->execute([$id, (int) $permisoId]);
        }
    }

    /* --------------------------------- Verificación de correo -------- */

    /** Busca un cliente sin verificar por el hash de su token de verificación. */
    /** Horas de vigencia del enlace de verificación de correo. */
    public const VERIFICACION_HORAS = 48;

    /** Usuario por token de verificación VIGENTE (el enlace caduca). */
    public function porTokenVerificacion(string $tokenHash): ?array
    {
        $st = $this->db->prepare(
            "SELECT * FROM usuarios
              WHERE verificacion_token = ?
                AND email_verificado_en IS NULL
                AND verificacion_expira_en IS NOT NULL
                AND verificacion_expira_en > NOW()
              LIMIT 1"
        );
        $st->execute([$tokenHash]);
        return $st->fetch() ?: null;
    }

    /** Marca el correo como verificado y limpia el token. */
    public function marcarVerificado(int $id): void
    {
        $this->db->prepare(
            "UPDATE usuarios
                SET email_verificado_en = NOW(), verificacion_token = NULL, verificacion_expira_en = NULL
              WHERE id = ?"
        )->execute([$id]);
    }

    /** Regenera el token de verificación (para reenviar el correo). */
    public function setTokenVerificacion(int $id, string $tokenHash): void
    {
        $this->db->prepare(
            "UPDATE usuarios
                SET verificacion_token = ?, verificacion_expira_en = DATE_ADD(NOW(), INTERVAL ? HOUR)
              WHERE id = ?"
        )->execute([$tokenHash, self::VERIFICACION_HORAS, $id]);
    }

    public function setActivo(int $id, bool $activo): void
    {
        $this->db->prepare("UPDATE usuarios SET activo = ? WHERE id = ?")->execute([$activo ? 1 : 0, $id]);
    }

    /** Datos para SAE del cliente: su clave y el vendedor asignado. */
    public function setDatosSae(int $id, ?string $claveSae, ?int $vendedorId): void
    {
        $this->db->prepare("UPDATE usuarios SET clave_sae = ?, vendedor_id = ? WHERE id = ?")
                 ->execute([$claveSae ?: null, $vendedorId ?: null, $id]);
    }

    /** Lista de productos del cliente (Fase 7.3). Null = ve el catálogo completo. */
    public function setListaProductos(int $id, ?int $listaProductosId): void
    {
        $this->db->prepare("UPDATE usuarios SET lista_productos_id = ? WHERE id = ?")
                 ->execute([$listaProductosId ?: null, $id]);
    }

    /** Usuarios que pueden fungir como vendedores (tienen clave de vendedor). */
    public function vendedores(): array
    {
        return $this->db->query(
            "SELECT id, nombre, clave_vendedor, comision
               FROM usuarios
              WHERE clave_vendedor IS NOT NULL AND clave_vendedor <> '' AND activo = 1
              ORDER BY nombre"
        )->fetchAll();
    }

    /** Usuarios del rol Almacén/Logística activos (candidatos a repartidor, Fase 7.7). */
    public function repartidores(): array
    {
        return $this->db->query(
            "SELECT u.id, u.nombre FROM usuarios u JOIN roles r ON r.id = u.rol_id
              WHERE r.slug = 'almacen' AND u.activo = 1 ORDER BY u.nombre"
        )->fetchAll();
    }

    /* --------------------------------- Admin: usuarios internos ------ */

    public function internos(): array
    {
        return $this->db->query(
            "SELECT u.*, r.nombre AS rol_nombre, r.slug AS rol_slug
               FROM usuarios u JOIN roles r ON r.id = u.rol_id
              WHERE r.slug <> 'cliente' ORDER BY u.nombre"
        )->fetchAll();
    }

    public function actualizarInterno(int $id, array $data): void
    {
        $this->db->prepare(
            "UPDATE usuarios SET nombre = ?, rol_id = ?, clave_vendedor = ?, comision = ?, activo = ? WHERE id = ?"
        )->execute([
            $data['nombre'],
            (int) $data['rol_id'],
            $data['clave_vendedor'] ?: null,
            $data['comision'] !== null && $data['comision'] !== '' ? (float) $data['comision'] : null,
            !empty($data['activo']) ? 1 : 0,
            $id,
        ]);
    }

    public function cambiarPassword(int $id, string $password): void
    {
        $this->db->prepare("UPDATE usuarios SET password = ? WHERE id = ?")
                 ->execute([self::hash($password), $id]);
    }

    /* --------------------------------- Overrides de permisos --------- */

    /** @return array<int,int> permiso_id => concedido(0|1) */
    public function overrides(int $usuarioId): array
    {
        $st = $this->db->prepare("SELECT permiso_id, concedido FROM usuario_permiso WHERE usuario_id = ?");
        $st->execute([$usuarioId]);
        $out = [];
        foreach ($st->fetchAll() as $row) {
            $out[(int) $row['permiso_id']] = (int) $row['concedido'];
        }
        return $out;
    }

    /** Reemplaza los overrides del usuario. $conceder / $revocar son arrays de permiso_id. */
    public function guardarOverrides(int $usuarioId, array $conceder, array $revocar): void
    {
        $this->db->prepare("DELETE FROM usuario_permiso WHERE usuario_id = ?")->execute([$usuarioId]);
        $st = $this->db->prepare("INSERT INTO usuario_permiso (usuario_id, permiso_id, concedido) VALUES (?, ?, ?)");
        foreach ($conceder as $pid) {
            $st->execute([$usuarioId, (int) $pid, 1]);
        }
        foreach ($revocar as $pid) {
            $st->execute([$usuarioId, (int) $pid, 0]);
        }
    }

    public function marcarLogin(int $id): void
    {
        $this->db->prepare("UPDATE usuarios SET last_login_at = NOW() WHERE id = ?")->execute([$id]);
    }
}
