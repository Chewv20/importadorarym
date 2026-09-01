<?php

declare(strict_types=1);

/**
 * Bootstrap para los scripts de línea de comandos de base de datos
 * (migrate.php, seed.php, backup.php, import_catalogo.php). Reutiliza el
 * autoloader, helpers y configuración.
 */

/*
 * Guardia CLI (defensa en profundidad): estos scripts solo deben correr desde
 * la línea de comandos. Aunque /database está bloqueado por .htaccess, si en
 * producción falla AllowOverride/mod_rewrite quedarían accesibles por HTTP y
 * podrían volcar la BD, sembrar datos o correr migraciones. Este corte NO
 * depende de la configuración de Apache.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require APP_PATH . '/Helpers/functions.php';

App\Core\Env::load(ROOT_PATH . '/.env');
App\Core\Config::load(ROOT_PATH . '/config');

date_default_timezone_set(config('app.timezone', 'America/Mexico_City'));
