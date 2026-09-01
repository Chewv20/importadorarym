<?php

namespace App\Core;

/**
 * Renderizador de vistas con soporte de layout.
 * Las vistas viven en app/Views y se referencian con "carpeta/archivo".
 */
class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        $content = static::capture($view, $data);

        if ($layout) {
            $data['content'] = $content;
            echo static::capture($layout, $data);
            return;
        }

        echo $content;
    }

    /**
     * Renderiza una vista/parcial y devuelve el HTML como string.
     */
    public static function capture(string $view, array $data = []): string
    {
        $file = APP_PATH . '/Views/' . str_replace('.', '/', $view) . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException("Vista no encontrada: {$view}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        return ob_get_clean();
    }
}
