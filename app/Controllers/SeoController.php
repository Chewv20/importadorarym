<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Categoria;
use App\Models\Vacante;

/**
 * robots.txt y sitemap.xml dinámicos. Las URLs salen de APP_URL (el dominio
 * canónico), así que no hay dominio escrito a mano; fuera de producción el
 * robots.txt bloquea todo para que un entorno de pruebas nunca se indexe.
 */
class SeoController extends Controller
{
    /** Páginas públicas fijas del sitio (las dinámicas salen de la BD). */
    private const PAGINAS = [
        '/',
        '/productos',
        '/personalizacion',
        '/nosotros',
        '/reciclaje',
        '/contacto',
        '/bolsa-de-trabajo',
        '/bolsa-de-trabajo/solicitud',
        '/aviso-de-privacidad',
    ];

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');

        if (config('app.env') !== 'production') {
            echo "User-agent: *\nDisallow: /\n";
            return;
        }

        // Áreas privadas o sin contenido para buscadores. Las páginas de login
        // y recuperación ya llevan además <meta name="robots" content="noindex">.
        echo "User-agent: *\n"
            . "Disallow: /portal/\n"
            . "Disallow: /admin/\n"
            . "Disallow: /checador\n"
            . "Disallow: /reparto\n"
            . "Disallow: /codigo-postal/\n"
            . "Disallow: /cotizar\n"
            . "\n"
            . 'Sitemap: ' . $this->absoluta('/sitemap.xml') . "\n";
    }

    public function sitemap(): void
    {
        $rutas = self::PAGINAS;
        foreach ((new Categoria())->activas() as $cat) {
            $rutas[] = '/productos/' . rawurlencode($cat['slug']);
        }
        foreach ((new Vacante())->abiertas() as $vac) {
            $rutas[] = '/bolsa-de-trabajo/' . rawurlencode($vac['slug']);
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($rutas as $ruta) {
            echo '  <url><loc>' . e($this->absoluta($ruta)) . "</loc></url>\n";
        }
        echo "</urlset>\n";
    }

    private function absoluta(string $ruta): string
    {
        return rtrim((string) config('app.url'), '/') . $ruta;
    }
}
