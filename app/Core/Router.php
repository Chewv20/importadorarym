<?php

namespace App\Core;

/**
 * Enrutador simple. Soporta rutas GET/POST con parámetros dinámicos {id}
 * y acciones como [Controlador::class, 'metodo'], "Controlador@metodo"
 * o una función anónima (callable).
 */
class Router
{
    protected array $routes = [];

    public function get(string $path, $action): void
    {
        $this->add('GET', $path, $action);
    }

    public function post(string $path, $action): void
    {
        $this->add('POST', $path, $action);
    }

    protected function add(string $method, string $path, $action): void
    {
        $path = '/' . trim($path, '/');
        $this->routes[$method][$path] = $action;
    }

    public function dispatch(string $method, string $uri)
    {
        $uri    = '/' . trim($uri, '/');
        $method = strtoupper($method);

        foreach ($this->routes[$method] ?? [] as $route => $action) {
            $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', $route);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                // El path llega SIN decodificar (para que %2F no altere el match);
                // se decodifica aquí, ya con los segmentos delimitados.
                $matches = array_map('rawurldecode', $matches);
                return $this->callAction($action, $matches);
            }
        }

        return $this->notFound();
    }

    protected function callAction($action, array $params)
    {
        if (is_array($action)) {
            [$class, $method] = $action;
            return call_user_func_array([new $class(), $method], $params);
        }

        if (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action);
            $class = 'App\\Controllers\\' . $class;
            return call_user_func_array([new $class(), $method], $params);
        }

        if (is_callable($action)) {
            return call_user_func_array($action, $params);
        }

        throw new \InvalidArgumentException('Acción de ruta no válida.');
    }

    protected function notFound(): void
    {
        http_response_code(404);
        View::render('errors/404', ['title' => 'Página no encontrada']);
    }
}
