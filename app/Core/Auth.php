<?php

namespace App\Core;

use App\Models\Usuario;

/**
 * Autenticación de sesión para el portal de clientes.
 */
class Auth
{
    private static ?array $cache = null;
    private static ?array $perms = null;

    /**
     * Hash de referencia para gastar el mismo tiempo cuando el correo no existe.
     * Sin esto, la respuesta inmediata (sin bcrypt) delata qué correos están
     * registrados pese al mensaje de error neutro. Usa el mismo coste que
     * Usuario::HASH_OPTS, así ambos caminos tardan lo mismo.
     */
    private const HASH_DUMMY = '$2y$12$O5tGURHXAHtMjrgs87Ni7.M8I01xSHf09WHtbYwngYC72vMxeQT5G';

    /**
     * Duración mínima de un intento de autenticación, en microsegundos.
     * Nivela el tiempo de respuesta pase lo que pase: correo inexistente, hash
     * con un coste antiguo o cuenta al día. Sin este piso, la duración del
     * intento revela qué correos están registrados.
     */
    private const PISO_MICROS = 400000; // 400 ms

    /** Verifica credenciales; devuelve el usuario o false. */
    public static function attempt(string $email, string $password): array|false
    {
        $inicio = microtime(true);
        $model  = new Usuario();
        $user   = $model->buscarPorEmail($email);

        if (!$user) {
            password_verify($password, self::HASH_DUMMY);
            self::nivelarTiempo($inicio);
            return false;
        }
        if (!password_verify($password, $user['password'])) {
            self::nivelarTiempo($inicio);
            return false;
        }

        // Migración transparente de hashes con un coste antiguo: mantiene todas
        // las contraseñas al día sin pedir nada al usuario.
        if (password_needs_rehash($user['password'], Usuario::HASH_ALGO, Usuario::HASH_OPTS)) {
            $model->cambiarPassword((int) $user['id'], $password);
            // La huella de sesión se calcula sobre el hash: hay que reflejar el nuevo.
            $user['password'] = (string) ($model->find((int) $user['id'])['password'] ?? $user['password']);
        }

        self::nivelarTiempo($inicio);
        return $user;
    }

    /** Espera lo que falte para que el intento dure siempre lo mismo. */
    private static function nivelarTiempo(float $inicio): void
    {
        $restante = self::PISO_MICROS - (int) ((microtime(true) - $inicio) * 1000000);
        if ($restante > 0) {
            usleep($restante);
        }
    }

    /** Inicia sesión para el usuario dado (regenera el id de sesión). */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        // Huella del hash de contraseña: si el password cambia (reset o cambio
        // desde el panel) la huella deja de coincidir y la sesión se invalida.
        $_SESSION['_pwfp'] = self::pwFingerprint((string) ($user['password'] ?? ''));
        $_SESSION['_last'] = time();
        self::$cache = null;
        self::$perms = null;
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id'], $_SESSION['_pwfp'], $_SESSION['_last']);
        self::$cache = null;
        self::$perms = null;
        session_regenerate_id(true);
    }

    /**
     * Aplica las políticas de sesión en cada request (llamar tras session_start):
     *   - Cierra la sesión tras N segundos de inactividad (config app.session_timeout).
     *   - Refresca la marca de actividad.
     * La invalidación por cambio de contraseña se verifica en user().
     */
    public static function enforce(): void
    {
        if (!self::check()) {
            return;
        }
        $timeout = (int) config('app.session_timeout', 7200);
        $now  = time();
        $last = (int) ($_SESSION['_last'] ?? 0);

        if ($timeout > 0 && $last > 0 && ($now - $last) > $timeout) {
            self::logout();
            flash('portal_error', 'Tu sesión expiró por inactividad. Vuelve a iniciar sesión.');
            return;
        }
        $_SESSION['_last'] = $now;
    }

    /** Huella corta del hash de contraseña, firmada con la clave de la app. */
    private static function pwFingerprint(string $passwordHash): string
    {
        $key = (string) config('app.key', '');
        return substr(hash_hmac('sha256', $passwordHash, $key), 0, 32);
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    /** Usuario autenticado con su rol (cargado de BD, cacheado por request). */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        if (self::$cache === null) {
            $row = (new Usuario())->autenticado((int) $_SESSION['user_id']) ?: null;

            if ($row !== null) {
                $fp = self::pwFingerprint((string) ($row['password'] ?? ''));
                if (isset($_SESSION['_pwfp'])) {
                    // Contraseña cambiada desde que se inició la sesión: invalidar.
                    if (!hash_equals($_SESSION['_pwfp'], $fp)) {
                        self::logout();
                        return null;
                    }
                } else {
                    // Sesión previa a esta política: adopta la huella actual.
                    $_SESSION['_pwfp'] = $fp;
                }
                // No exponer el hash de contraseña al resto de la app ni a las vistas.
                unset($row['password']);
            }

            self::$cache = $row;
        }
        return self::$cache;
    }

    /** Claves de permiso efectivas del usuario (rol + overrides). */
    public static function permissions(): array
    {
        $user = self::user();
        if (!$user) {
            return [];
        }
        if (self::$perms === null) {
            $rolId = isset($user['rol_id']) && $user['rol_id'] !== null ? (int) $user['rol_id'] : null;
            self::$perms = (new Usuario())->permisosEfectivos((int) $user['id'], $rolId);
        }
        return self::$perms;
    }

    public static function can(string $clave): bool
    {
        return in_array($clave, self::permissions(), true);
    }

    public static function hasRole(string $slug): bool
    {
        $user = self::user();
        return $user !== null && ($user['rol_slug'] ?? null) === $slug;
    }

    /** Corta la ejecución con 403 si el usuario no tiene el permiso. */
    public static function authorize(string $clave): void
    {
        if (!self::can($clave)) {
            http_response_code(403);
            if (!headers_sent()) {
                header('Content-Type: text/plain; charset=utf-8');
            }
            exit('403 · No tienes permiso para realizar esta acción.');
        }
    }
}
