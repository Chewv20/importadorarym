<?php

use App\Core\Config;
use App\Core\View;

/**
 * Funciones de ayuda globales.
 */

/** Segundos que vive un reto anti-bot antes de caducar (y de dejar de ser reutilizable). */
if (!defined('CAPTCHA_VIGENCIA')) {
    define('CAPTCHA_VIGENCIA', 1800);
}

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return $value;
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('is_https')) {
    function is_https(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (($_SERVER['SERVER_PORT'] ?? null) == 443) {
            return true;
        }
        // Detrás de proxy / balanceador (útil al instalar SSL en producción):
        // mismo criterio de confianza que client_ip() (TRUSTED_PROXY_CIDR).
        // Sin esto, cualquiera podía enviar X-Forwarded-Proto: https directo al
        // origen (sin pasar por el balanceador) y evitar la redirección a HTTPS.
        $remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $confia = false;
        foreach (trusted_proxy_cidrs() as $cidr) {
            if ($cidr !== '' && ip_in_cidr($remote, $cidr)) {
                $confia = true;
                break;
            }
        }
        if ($confia && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
            return true;
        }
        return false;
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $base = defined('BASE_PATH') ? BASE_PATH : '';
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return base_url($path);
    }
}

if (!function_exists('asset')) {
    /**
     * URL de un archivo de public/ con marca de versión (?v=mtime).
     *
     * public/.htaccess cachea CSS/JS 7 días; sin esta marca, tras un despliegue
     * los navegadores que ya visitaron el sitio siguen sirviendo la versión
     * vieja durante una semana. Al cambiar el archivo cambia la URL.
     */
    function asset(string $path): string
    {
        static $versiones = [];

        if (!array_key_exists($path, $versiones)) {
            $abs = (defined('PUBLIC_PATH') ? PUBLIC_PATH : '') . '/' . ltrim($path, '/');
            $mt  = @filemtime($abs);
            $versiones[$path] = $mt !== false ? (string) $mt : null;
        }

        $url = base_url($path);
        if ($versiones[$path] === null) {
            return $url;
        }
        return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . $versiones[$path];
    }
}

if (!function_exists('css_bundle')) {
    /**
     * Hojas de estilo de una sección ('site', 'portal', 'admin', 'kiosco').
     * En producción, un único archivo minificado; en desarrollo, los sueltos.
     * La composición se declara en App\Core\Assets::BUNDLES.
     */
    function css_bundle(string $nombre): string
    {
        return \App\Core\Assets::css($nombre);
    }
}

if (!function_exists('asset_url')) {
    /** URL ABSOLUTA de un asset (para og:image, JSON-LD, correos). */
    function asset_url(string $path): string
    {
        return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('canonical_url')) {
    /** URL canónica absoluta de la página actual. */
    function canonical_url(): string
    {
        $path = defined('REQUEST_PATH') ? REQUEST_PATH : '/';
        return rtrim((string) config('app.url'), '/') . $path;
    }
}

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('view')) {
    function view(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        View::render($view, $data, $layout);
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = '')
    {
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(?string $token): bool
    {
        return !empty($_SESSION['_csrf']) && is_string($token)
            && hash_equals($_SESSION['_csrf'], $token);
    }
}

if (!function_exists('flash')) {
    /** Guarda (set) o recupera-y-borra (get) un mensaje flash de sesión. */
    function flash(string $key, ?string $message = null)
    {
        if ($message !== null) {
            $_SESSION['_flash'][$key] = $message;
            return null;
        }
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}

if (!function_exists('csp_nonce')) {
    /** Nonce de Content-Security-Policy para scripts inline. */
    function csp_nonce(): string
    {
        return defined('CSP_NONCE') ? CSP_NONCE : '';
    }
}

if (!function_exists('url_http_valida')) {
    /**
     * URL externa segura para emitir en un href.
     *
     * FILTER_VALIDATE_URL por sí solo acepta esquemas ejecutables
     * ("javascript://%0aalert(1)" lo pasa), así que además se exige http/https.
     */
    function url_http_valida(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $esquema = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($esquema, ['http', 'https'], true);
    }
}

if (!function_exists('nombre_valido')) {
    /** Nombre: letras (con acentos), espacios y . - ' — sin dígitos ni símbolos raros. */
    function nombre_valido(string $v): bool
    {
        $v = trim($v);
        return $v !== '' && (bool) preg_match('/^[\p{L}\p{M}][\p{L}\p{M} .\x27\-]{1,119}$/u', $v);
    }
}

if (!function_exists('telefono_valido')) {
    /** Teléfono: solo dígitos y símbolos telefónicos (+ - espacio ()), con al menos 7 dígitos. */
    function telefono_valido(string $v): bool
    {
        $v = trim($v);
        return (bool) preg_match('/^[0-9+\-\s()]{7,30}$/', $v)
            && preg_match_all('/\d/', $v) >= 7;
    }
}

if (!function_exists('rfc_valido')) {
    /** RFC mexicano (persona física 13 o moral 12 caracteres). */
    function rfc_valido(string $v): bool
    {
        $v = strtoupper(trim($v));
        return (bool) preg_match('/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/u', $v);
    }
}

if (!function_exists('empresa_valida')) {
    /** Empresa: letras, números, espacios y símbolos comerciales comunes; sin < > { } ni control. */
    function empresa_valida(string $v): bool
    {
        $v = trim($v);
        return $v === '' || (bool) preg_match('/^[\p{L}\p{M}0-9 .,&\x27()\-\/]{1,150}$/u', $v);
    }
}

if (!function_exists('direccion_valida')) {
    /** Calle/colonia/delegación-municipio/estado: letras, números, espacios y puntuación común de domicilios. */
    function direccion_valida(string $v): bool
    {
        $v = trim($v);
        return $v === '' || (bool) preg_match('/^[\p{L}\p{M}0-9 .,°#\x27\-\/]{1,150}$/u', $v);
    }
}

if (!function_exists('codigo_postal_valido')) {
    /** Código postal mexicano: exactamente 5 dígitos. */
    function codigo_postal_valido(string $v): bool
    {
        return (bool) preg_match('/^\d{5}$/', trim($v));
    }
}

if (!function_exists('direccion_texto')) {
    /** Arma la dirección del usuario en una sola línea, o null si no capturó calle. */
    function direccion_texto(array $u): ?string
    {
        $calle = trim((string) ($u['calle'] ?? ''));
        if ($calle === '') {
            return null;
        }
        $partes = [trim($calle . ' ' . trim((string) ($u['numero_ext'] ?? '')))];
        if (!empty($u['numero_int']))       $partes[] = 'Int. ' . $u['numero_int'];
        if (!empty($u['colonia']))          $partes[] = $u['colonia'];
        if (!empty($u['codigo_postal']))    $partes[] = 'C.P. ' . $u['codigo_postal'];
        if (!empty($u['delegacion_municipio'])) $partes[] = $u['delegacion_municipio'];
        if (!empty($u['estado_direccion']))     $partes[] = $u['estado_direccion'];
        return implode(', ', $partes);
    }
}

if (!function_exists('direccion_maps_url')) {
    /**
     * Iframe de Google Maps (embed, sin API key ni facturación — mismo patrón que
     * pages/contacto.php) para que el cliente confirme visualmente su dirección.
     */
    function direccion_maps_url(array $u): ?string
    {
        $texto = direccion_texto($u);
        return $texto !== null
            ? 'https://www.google.com/maps?q=' . rawurlencode($texto) . '&z=16&output=embed'
            : null;
    }
}

if (!function_exists('password_errores')) {
    /** Devuelve la lista de reglas de contraseña que NO se cumplen. */
    function password_errores(string $pass): array
    {
        $e = [];
        if (mb_strlen($pass) < 8)              $e[] = 'La contraseña debe tener al menos 8 caracteres.';
        if (mb_strlen($pass) > 100)            $e[] = 'La contraseña es demasiado larga (máx. 100).';
        if (!preg_match('/[A-Z]/', $pass))     $e[] = 'La contraseña debe incluir una mayúscula.';
        if (!preg_match('/[a-z]/', $pass))     $e[] = 'La contraseña debe incluir una minúscula.';
        if (!preg_match('/\d/', $pass))        $e[] = 'La contraseña debe incluir un número.';
        if (!preg_match('/[^A-Za-z0-9]/', $pass)) $e[] = 'La contraseña debe incluir un símbolo (ej. ! @ # $).';
        return $e;
    }
}

if (!function_exists('slugify')) {
    /** Convierte un texto en un slug URL-friendly (sin acentos, en minúsculas). */
    function slugify(string $text): string
    {
        $text = trim($text);
        // Transliteración explícita de acentos (robusta en Windows y Linux; el
        // iconv de Windows convierte "é" en "'e" y ensucia el slug).
        $text = strtr($text, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'ç' => 'c', 'Ç' => 'C',
        ]);
        if (function_exists('iconv')) {
            $conv = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($conv !== false) {
                $text = $conv;
            }
        }
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        return trim($text, '-') ?: 'item';
    }
}

if (!function_exists('str_clean')) {
    /** Recorta espacios y limita la longitud de una entrada de texto. */
    function str_clean($value, int $max): string
    {
        return mb_substr(trim((string) $value), 0, $max);
    }
}

if (!function_exists('honeypot_field')) {
    /** Campo trampa (oculto) para detectar bots en formularios públicos. */
    function honeypot_field(): string
    {
        return '<div class="hp" aria-hidden="true">'
            . '<label>No llenar este campo'
            . '<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>';
    }
}

if (!function_exists('honeypot_tripped')) {
    function honeypot_tripped(): bool
    {
        return trim((string) ($_POST['website'] ?? '')) !== '';
    }
}

if (!function_exists('ip_in_cidr')) {
    /** ¿$ip cae dentro de $cidr? Acepta IP exacta o notación CIDR IPv4 ("x.x.x.x/bits"). */
    function ip_in_cidr(string $ip, string $cidr): bool
    {
        // strpos() en vez de str_contains() (PHP 8.0+): is_https() llama a esta
        // función antes de la redirección a HTTPS en public/index.php — debe poder
        // correr incluso si algún vhost del hosting quedó en una versión de PHP
        // más vieja que la del resto del sitio.
        if (strpos($cidr, '/') === false) {
            return $ip === $cidr;
        }

        [$rango, $bits] = explode('/', $cidr, 2);
        $bits = (int) $bits;
        $ipLong = ip2long($ip);
        $rangoLong = ip2long($rango);
        if ($ipLong === false || $rangoLong === false || $bits < 0 || $bits > 32) {
            return false;
        }

        $mask = $bits === 0 ? 0 : (~0 << (32 - $bits));
        return ($ipLong & $mask) === ($rangoLong & $mask);
    }
}

if (!function_exists('trusted_proxy_cidrs')) {
    /** Lista de proxies de confianza desde TRUSTED_PROXY_CIDR (.env), separados por coma. */
    function trusted_proxy_cidrs(): array
    {
        static $lista = null;
        if ($lista === null) {
            $raw = (string) config('app.trusted_proxy_cidr', '');
            $lista = array_values(array_filter(array_map('trim', explode(',', $raw))));
        }
        return $lista;
    }
}

if (!function_exists('resolve_client_ip')) {
    /**
     * Lógica pura (testable sin globals): dada la IP directa de la conexión,
     * la cabecera X-Forwarded-For cruda y la lista de proxies de confianza,
     * resuelve la IP real del visitante. Solo confía en X-Forwarded-For
     * cuando $remote cae en alguno de $cidrsConfiables; si no, o si la
     * cabecera falta/trae un valor inválido, devuelve $remote tal cual.
     */
    function resolve_client_ip(string $remote, string $forwardedFor, array $cidrsConfiables): string
    {
        if ($cidrsConfiables === []) {
            return $remote;
        }

        $confia = false;
        foreach ($cidrsConfiables as $cidr) {
            if ($cidr !== '' && ip_in_cidr($remote, $cidr)) {
                $confia = true;
                break;
            }
        }
        if (!$confia) {
            return $remote;
        }

        $forwardedFor = trim($forwardedFor);
        if ($forwardedFor === '') {
            return $remote;
        }

        // "cliente, proxy1, proxy2, …" — el primer valor es el visitante original.
        $primero = trim(explode(',', $forwardedFor)[0]);
        return filter_var($primero, FILTER_VALIDATE_IP) ? $primero : $remote;
    }
}

if (!function_exists('client_ip')) {
    /**
     * IP real del visitante. Detrás del balanceador de Rackspace Cloud Sites
     * (o cualquier proxy de confianza declarado en TRUSTED_PROXY_CIDR) resuelve
     * X-Forwarded-For; si no hay proxy configurado, o REMOTE_ADDR no pertenece
     * a ese rango, usa REMOTE_ADDR tal cual (comportamiento seguro por defecto:
     * sin configurar nada, nunca se confía en una cabecera falsificable).
     */
    function client_ip(): string
    {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $xff    = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        return resolve_client_ip($remote, $xff, trusted_proxy_cidrs());
    }
}

if (!function_exists('captcha_secret')) {
    /** Clave para firmar el reto del captcha (con respaldo si no hay APP_KEY). */
    function captcha_secret(): string
    {
        $k = (string) config('app.key', '');
        return $k !== '' ? $k : 'rym-captcha-' . php_uname('n');
    }
}

if (!function_exists('captcha_field')) {
    /**
     * Reto anti-bot: suma aritmética simple firmada con HMAC (sin estado de
     * sesión, compatible con PWA) + marca de tiempo para descartar envíos
     * instantáneos de bots. Complementa al honeypot.
     */
    function captcha_field(): string
    {
        $a   = random_int(1, 9);
        $b   = random_int(1, 9);
        $ts  = time();
        // La firma cubre el tiempo y la respuesta esperada; validar recalcula
        // el HMAC con la respuesta del usuario, así no se guarda nada en sesión.
        $sig = hash_hmac('sha256', $ts . ':' . ($a + $b), captcha_secret());

        return '<div class="field captcha">'
            . '<label for="captcha">Comprobación anti-bot: ¿cuánto es '
            . $a . ' + ' . $b . '? *</label>'
            . '<input type="text" id="captcha" name="captcha" inputmode="numeric"'
            . ' autocomplete="off" required>'
            . '<input type="hidden" name="_cap_ts" value="' . $ts . '">'
            . '<input type="hidden" name="_cap_sig" value="' . e($sig) . '">'
            . '</div>';
    }
}

if (!function_exists('captcha_valido')) {
    /**
     * Verifica el reto del captcha del POST: respuesta correcta, tiempo humano
     * y **un solo uso**. Sin lo último, un bot resolvía la suma una vez y
     * reenviaba la misma tripleta (ts, sig, respuesta) durante toda su vigencia.
     */
    function captcha_valido(): bool
    {
        $resp = trim((string) ($_POST['captcha'] ?? ''));
        $ts   = (int) ($_POST['_cap_ts'] ?? 0);
        $sig  = (string) ($_POST['_cap_sig'] ?? '');

        if ($resp === '' || !ctype_digit($resp) || $ts <= 0 || $sig === '') {
            return false;
        }
        // Muy rápido = bot; muy viejo = reto expirado (30 min cubre de sobra
        // un formulario largo, como la postulación con CV).
        $edad = time() - $ts;
        if ($edad < 2 || $edad > CAPTCHA_VIGENCIA) {
            return false;
        }
        $esperado = hash_hmac('sha256', $ts . ':' . $resp, captcha_secret());
        if (!hash_equals($esperado, $sig)) {
            return false;
        }
        // Consumo: el mismo reto firmado no vuelve a valer.
        return \App\Core\RateLimiter::attempt('captcha:' . hash('sha256', $sig), 1, CAPTCHA_VIGENCIA);
    }
}

if (!function_exists('can')) {
    /** ¿El usuario autenticado tiene el permiso indicado? Para usar en vistas. */
    function can(string $clave): bool
    {
        return \App\Core\Auth::can($clave);
    }
}

if (!function_exists('whatsapp_url')) {
    /**
     * Enlace click-to-chat de WhatsApp (wa.me) con mensaje opcional prellenado.
     * El número sale de config('app.whatsapp.numero'); si no se pasa mensaje,
     * usa el genérico de configuración.
     */
    function whatsapp_url(?string $mensaje = null): string
    {
        $num = preg_replace('/\D/', '', (string) config('app.whatsapp.numero', ''));
        $url = 'https://wa.me/' . $num;
        $msg = $mensaje ?? (string) config('app.whatsapp.mensaje', '');
        if ($msg !== '') {
            $url .= '?text=' . rawurlencode($msg);
        }
        return $url;
    }
}

if (!function_exists('nav_active')) {
    /** Devuelve 'is-active' si $name coincide con la página actual. */
    function nav_active(string $name, string $current): string
    {
        return $name === $current ? 'is-active' : '';
    }
}
