<?php

/**
 * Último recurso: solo se ejecuta si mod_rewrite NO está disponible.
 *
 * Con mod_rewrite activo (lo normal), el .htaccess de la raíz sirve /public por
 * reescritura interna y este archivo nunca llega a ejecutarse. Esto aplica
 * también en producción real (Rackspace Cloud Sites): ese hosting no permite
 * fijar el DocumentRoot en una subcarpeta, así que el mecanismo de la raíz
 * (este archivo + .htaccess) es el que efectivamente se usa ahí, no un caso
 * exclusivo de desarrollo local.
 *
 * Sin mod_rewrite el sitio no puede funcionar —las rutas como /productos las
 * resuelve el front controller, no archivos reales—, así que esto solo lleva a
 * la portada para que el problema sea evidente en vez de un 404 a secas.
 */
header('Location: public/');
exit;
