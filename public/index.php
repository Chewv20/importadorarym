<?php

declare(strict_types=1);

/* ---------------------------------------------------------------------------
 | Front Controller — punto de entrada único de la aplicación.
 |
 | Lo ideal es que el DocumentRoot apunte aquí. En este despliegue real
 | (Rackspace Cloud Sites) eso no es posible — el hosting no permite fijar el
 | DocumentRoot en una subcarpeta —, así que en producción se llega a este
 | archivo por la reescritura interna del .htaccess de la raíz del proyecto,
 | igual que en desarrollo local. El cálculo de BASE_PATH de más abajo ya
 | contempla ese caso.
 * ------------------------------------------------------------------------- */

/* Rutas base */
define('PUBLIC_PATH', __DIR__);
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

/* Registrar errores en nuestro propio log DESDE EL PRIMER MOMENTO, antes de
   cargar Env/Config — un fatal muy temprano (autoloader, Env::load,
   Config::load) antes solo quedaba en el log nativo de PHP/Apache del
   servidor, invisible para el visor de errores del panel (que lee
   storage/logs/php-error.log). No depende de app.debug: escribir al log no
   estorba en desarrollo, solo lo que sí depende de app.debug es si además se
   muestra en pantalla (ver más abajo). */
ini_set('log_errors', '1');
ini_set('error_log', ROOT_PATH . '/storage/logs/php-error.log');

/* Autoloader PSR-4 para el namespace App\ */
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

/* Helpers globales */
require APP_PATH . '/Helpers/functions.php';

/* Entorno + configuración */
App\Core\Env::load(ROOT_PATH . '/.env');
App\Core\Config::load(ROOT_PATH . '/config');

/* Zona horaria */
date_default_timezone_set(config('app.timezone', 'America/Mexico_City'));

/* Manejo de errores según entorno */
if (config('app.debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);

    /* Rotación de logs con muestreo (~1 de cada 200 peticiones): evita que un
       error recurrente llene el disco, sin pagar un stat en cada request. */
    if (random_int(1, 200) === 1) {
        App\Core\Log::mantenimiento();
        App\Core\RateLimiter::gc();
    }
}

/* Renderiza la página 500 (o texto plano si el propio render falla). */
$render500 = static function (\Throwable $e): void {
    error_log('[500] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    if (config('app.debug')) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo "500 — " . $e->getMessage() . "\n\n" . $e->getFile() . ':' . $e->getLine()
                . "\n\n" . $e->getTraceAsString();
        }
        return;
    }
    // Aviso por correo al buzón técnico (MAIL_ERRORES) — solo en producción,
    // nunca en desarrollo. No bloquea ni condiciona el render de la página al
    // visitante: corre después y nunca lanza (ver App\Core\ErrorAlert).
    App\Core\ErrorAlert::notificar($e);
    if (headers_sent()) {
        return;
    }
    http_response_code(500);
    try {
        App\Core\View::render('errors/500', ['title' => 'Error interno', 'robots' => 'noindex']);
    } catch (\Throwable $inner) {
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Error interno del servidor. Intenta más tarde.';
    }
};

/* Red de seguridad para errores fatales que no pasan por el try/catch. */
register_shutdown_function(static function () use ($render500): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        $render500(new \ErrorException($err['message'], 0, $err['type'], $err['file'], $err['line']));
    }
});

/* Base path: prefijo común de todas las URLs del sitio.
   - Producción (DocumentRoot = /public):        SCRIPT_NAME = /index.php          -> ""
   - Local en subcarpeta (/importadorarym/):     SCRIPT_NAME = /importadorarym/public/index.php
                                                                                   -> "/importadorarym"
   En local, el .htaccess de la raíz sirve /public por reescritura interna sin que
   aparezca en la URL, así que ese segmento se descarta del prefijo: si no, todos
   los enlaces y assets volverían a apuntar a /public. */
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if (substr($scriptDir, -7) === '/public') {
    $scriptDir = substr($scriptDir, 0, -7);
}
define('BASE_PATH', ($scriptDir === '/' || $scriptDir === '.') ? '' : rtrim($scriptDir, '/'));

/* SSL / HTTPS
   Mientras no haya certificado, FORCE_HTTPS=false y el sitio corre en HTTP.
   En producción con SSL: FORCE_HTTPS=true -> redirige y activa HSTS. */
if (config('app.force_https') && !is_https()) {
    $target = 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
    header('Location: ' . $target, true, 301);
    exit;
}
if (config('app.force_https') && is_https()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

/* Content-Security-Policy con nonce para los scripts inline propios.
   Permite Google Analytics, Google Fonts y el iframe de Google Maps. */
define('CSP_NONCE', base64_encode(random_bytes(16)));
$csp = "default-src 'self'; "
    . "base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; "
    . "img-src 'self' data: https://www.google-analytics.com https://*.google-analytics.com https://*.googletagmanager.com; "
    . "script-src 'self' 'nonce-" . CSP_NONCE . "' https://www.googletagmanager.com; "
    // Las hojas y los <style> se restringen a origen propio + nonce; 'unsafe-inline'
    // queda acotado a los ATRIBUTOS style, que ya solo llevan enteros calculados
    // (las barras de los tableros, vía --pct/--px).
    // Sin dominios de Google Fonts: la tipografía se sirve desde este mismo origen.
    . "style-src 'self' 'unsafe-inline'; "
    . "style-src-elem 'self' 'nonce-" . CSP_NONCE . "'; "
    . "style-src-attr 'unsafe-inline'; "
    . "font-src 'self'; "
    . "connect-src 'self' https://www.google-analytics.com https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com; "
    . "frame-src https://www.google.com;";
header('Content-Security-Policy: ' . $csp);
header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
header('X-Content-Type-Options: nosniff');
/* También en public/.htaccess, pero allí dependen de mod_headers: emitirlas
   desde PHP garantiza que estén presentes en cualquier servidor. */
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cross-Origin-Opener-Policy: same-origin');

/* Sesión con cookies seguras (secure se activa solo si ya hay HTTPS) */
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => BASE_PATH !== '' ? BASE_PATH : '/',
    'httponly' => true,
    'secure'   => is_https(),
    'samesite' => 'Lax',
]);
session_start();

/* Políticas de sesión: timeout por inactividad + invalidación por cambio de
   contraseña (esta última se verifica al cargar el usuario en Auth::user()). */
App\Core\Auth::enforce();

/* Enrutamiento */
$router = new App\Core\Router();
require ROOT_PATH . '/routes/web.php';

/* URI relativa a BASE_PATH.
   El path NO se decodifica aquí: hacerlo convertiría un %2F en separador de
   segmentos y cambiaría qué ruta coincide (útil para eludir reglas de servidor).
   El Router decodifica solo los parámetros que captura. */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
if (BASE_PATH !== '' && strpos($uri, BASE_PATH) === 0) {
    $uri = substr($uri, strlen(BASE_PATH));
}
/* Tolerancia: el .htaccess redirige /public/... a la URL sin él, pero si se
   llega igualmente por ahí (acceso directo al front controller), no debe
   convertirse en un 404. */
if (strpos($uri, '/public/') === 0 || $uri === '/public') {
    $uri = substr($uri, 7);
}
$uri = $uri === '' ? '/' : $uri;

/* Ruta actual (sin query) — usada por canonical_url() y og:url. */
define('REQUEST_PATH', $uri);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $uri);
} catch (\Throwable $e) {
    $render500($e);
}
