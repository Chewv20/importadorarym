<?php

declare(strict_types=1);

/**
 * Suite de pruebas mínima en PHP puro (sin Composer/PHPUnit).
 * Uso:  php tests/run.php
 * Devuelve código de salida 1 si alguna prueba falla (útil para cron/CI).
 */

/*
 * Guardia CLI (defensa en profundidad, mismo patrón que database/_bootstrap.php):
 * este script escribe y borra archivos bajo storage/logs y storage/cache al
 * probar Log/Cache. Aunque /tests está bloqueado por .htaccess, si en
 * producción falla AllowOverride/mod_rewrite quedaría accesible por HTTP.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');

spl_autoload_register(function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require APP_PATH . '/Helpers/functions.php';
App\Core\Env::load(ROOT_PATH . '/.env');
App\Core\Config::load(ROOT_PATH . '/config');

use App\Core\Upload;
use App\Core\SaeExport;
use App\Core\Log;
use App\Core\Cache;
use App\Core\CssMin;
use App\Models\Producto;

/* ------------------------------------------------ Mini framework -- */

$GLOBALS['_pass'] = 0;
$GLOBALS['_fail'] = 0;

function ok(string $nombre, bool $cond): void
{
    if ($cond) {
        $GLOBALS['_pass']++;
        // echo "  ok  {$nombre}\n";
    } else {
        $GLOBALS['_fail']++;
        echo "  FALLA  {$nombre}\n";
    }
}

function eq(string $nombre, $esperado, $obtenido): void
{
    $cond = $esperado === $obtenido;
    if (!$cond) {
        echo "  FALLA  {$nombre}  esperado=" . var_export($esperado, true)
            . " obtenido=" . var_export($obtenido, true) . "\n";
        $GLOBALS['_fail']++;
    } else {
        $GLOBALS['_pass']++;
    }
}

function grupo(string $titulo, callable $fn): void
{
    echo "\n# {$titulo}\n";
    $fn();
}

/* ------------------------------------------------------- Pruebas -- */

grupo('Validadores de texto', function () {
    ok('nombre válido con acentos', nombre_valido('José Pérez'));
    ok('nombre con apóstrofo/guion', nombre_valido("O'Connor-López"));
    ok('nombre rechaza dígitos', !nombre_valido('Juan123'));
    ok('nombre rechaza <script>', !nombre_valido('<script>'));
    ok('nombre rechaza vacío', !nombre_valido('   '));

    ok('teléfono 10 dígitos', telefono_valido('5512345678'));
    ok('teléfono con formato', telefono_valido('+52 (55) 1234-5678'));
    ok('teléfono rechaza letras', !telefono_valido('55ABC12'));
    ok('teléfono rechaza corto', !telefono_valido('12345'));

    ok('RFC moral válido', rfc_valido('IRY123456AB1'));
    ok('RFC físico válido', rfc_valido('PEPJ850101HG9'));
    ok('RFC rechaza basura', !rfc_valido('NOPE'));

    ok('empresa válida', empresa_valida('Tacos & Más S.A. de C.V.'));
    ok('empresa rechaza <', !empresa_valida('Foo <b>'));
    ok('empresa vacía permitida', empresa_valida(''));

    ok('dirección válida con # y acentos', direccion_valida('Av. San Lorenzo #279'));
    ok('dirección rechaza <', !direccion_valida('Calle <script>'));
    ok('dirección vacía permitida', direccion_valida(''));

    ok('CP válido de 5 dígitos', codigo_postal_valido('09850'));
    ok('CP rechaza letras', !codigo_postal_valido('0985A'));
    ok('CP rechaza corto', !codigo_postal_valido('985'));
    ok('CP rechaza largo', !codigo_postal_valido('098500'));
});

grupo('Dirección: texto y mapa (Fase 7.1)', function () {
    $completa = [
        'calle' => 'Av. San Lorenzo', 'numero_ext' => '279', 'numero_int' => '',
        'colonia' => 'Nave 27', 'codigo_postal' => '09850', 'delegacion_municipio' => 'Iztapalapa',
        'estado_direccion' => 'CDMX',
    ];
    eq('arma el texto completo', 'Av. San Lorenzo 279, Nave 27, C.P. 09850, Iztapalapa, CDMX',
        direccion_texto($completa));
    eq('sin calle no hay dirección', null, direccion_texto(['calle' => '']));
    eq('sin calle no hay mapa', null, direccion_maps_url(['calle' => '']));

    $url = direccion_maps_url($completa);
    ok('el mapa usa el dominio de Google (permitido por la CSP)', str_starts_with((string) $url, 'https://www.google.com/maps?q='));
    ok('el mapa va url-encoded (sin espacios crudos)', !str_contains((string) $url, ' '));
});

grupo('Política de contraseña', function () {
    eq('contraseña fuerte sin errores', [], password_errores('Admin1234!'));
    ok('rechaza sin mayúscula', in_array('La contraseña debe incluir una mayúscula.', password_errores('admin1234!'), true));
    ok('rechaza sin símbolo', count(password_errores('Admin12345')) > 0);
    ok('rechaza corta', count(password_errores('Ab1!')) > 0);
});

grupo('Captcha anti-bot (HMAC + tiempo)', function () {
    $ts  = time() - 5;
    $sig = hash_hmac('sha256', $ts . ':7', captcha_secret());
    $_POST = ['captcha' => '7', '_cap_ts' => $ts, '_cap_sig' => $sig];
    ok('acepta respuesta correcta con tiempo humano', captcha_valido());

    $_POST['captcha'] = '8';
    ok('rechaza respuesta incorrecta', !captcha_valido());

    $_POST = ['captcha' => '7', '_cap_ts' => time(), '_cap_sig' => hash_hmac('sha256', time() . ':7', captcha_secret())];
    ok('rechaza envío instantáneo (bot)', !captcha_valido());

    $_POST = ['captcha' => '7', '_cap_ts' => $ts, '_cap_sig' => str_repeat('0', 64)];
    ok('rechaza firma falsificada', !captcha_valido());
    $_POST = [];
});

grupo('Captcha: un solo uso (antirreplay)', function () {
    // Un bot resolvía la suma una vez y reenviaba la misma tripleta firmada
    // durante toda su vigencia; ahora el reto se consume al validarse.
    $ts  = time() - 5;
    $sig = hash_hmac('sha256', $ts . ':9', captcha_secret());
    $_POST = ['captcha' => '9', '_cap_ts' => $ts, '_cap_sig' => $sig];

    ok('acepta el primer envío', captcha_valido());
    ok('rechaza el reenvío del mismo reto', !captcha_valido());

    // Caducidad: un reto viejo no sirve aunque la firma sea correcta.
    $viejo = time() - (CAPTCHA_VIGENCIA + 60);
    $_POST = [
        'captcha'  => '4',
        '_cap_ts'  => $viejo,
        '_cap_sig' => hash_hmac('sha256', $viejo . ':4', captcha_secret()),
    ];
    ok('rechaza un reto caducado', !captcha_valido());
    $_POST = [];
});

grupo('URL externa segura (href de modales)', function () {
    ok('acepta https', url_http_valida('https://importadorarym.com/promo'));
    ok('acepta http', url_http_valida('http://ejemplo.mx'));
    // FILTER_VALIDATE_URL por sí solo dejaba pasar estos dos:
    ok('rechaza javascript:', !url_http_valida('javascript://%0aalert(document.cookie)'));
    ok('rechaza data:', !url_http_valida('data:text/html;base64,PHNjcmlwdD4='));
    ok('rechaza vacío', !url_http_valida(''));
    ok('rechaza texto suelto', !url_http_valida('no soy una url'));
});

grupo('Assets versionados (cache-busting)', function () {
    // Sin ?v=, public/.htaccess sirve el CSS viejo hasta 7 días tras desplegar.
    $rel = 'assets/css/app.css';
    ok('un asset existente lleva ?v=', (bool) preg_match('/\?v=\d+$/', asset($rel)));
    eq('la versión es el mtime', '?v=' . filemtime(PUBLIC_PATH . '/' . $rel),
        substr(asset($rel), strpos(asset($rel), '?')));
    ok('un archivo inexistente no lleva versión', !str_contains(asset('assets/css/no-existe.css'), '?v='));
});

grupo('Rotación de logs', function () {
    $log = ROOT_PATH . '/storage/logs/test-rotacion.log';
    @unlink($log);
    @unlink($log . '.1');

    file_put_contents($log, str_repeat('x', 100));
    ok('no rota si está por debajo del máximo', !Log::rotar($log, 1024));

    file_put_contents($log, str_repeat('x', 2048));
    ok('rota al superar el máximo', Log::rotar($log, 1024));
    ok('deja el histórico en .1', is_file($log . '.1'));
    ok('el log activo queda liberado', !is_file($log));

    @unlink($log);
    @unlink($log . '.1');
});

grupo('Minificador de CSS', function () {
    /* Lo básico: comentarios y espacio sobrante fuera. */
    eq('quita comentarios y espacios', '.a{color:red}', CssMin::minificar("/* hola */\n.a {\n  color: red;\n}\n"));
    eq('quita el ; final del bloque', '.a{color:red}', CssMin::minificar('.a { color: red; }'));
    eq('colapsa !important', '.a{color:red!important}', CssMin::minificar('.a { color: red !important; }'));

    /* calc() necesita los espacios alrededor de - y +: quitarlos lo rompe. */
    eq('conserva los espacios de calc()', '.a{width:calc(100% - 10px)}',
        CssMin::minificar('.a { width: calc(100% - 10px); }'));
    eq('conserva calc() con variable', '.b{width:calc(var(--pct) * 1%)}',
        CssMin::minificar('.b { width: calc(var(--pct) * 1%); }'));

    /* El combinador de hermano adyacente también depende del espacio. */
    ok('conserva el combinador +', str_contains(CssMin::minificar('.a + .b { color: red; }'), '.a + .b'));

    /* Media queries. */
    eq('compacta la media query', '@media (min-width:700px){.a{color:red}}',
        CssMin::minificar('@media (min-width: 700px) { .a { color: red; } }'));

    /* Lo más delicado: dentro de url() y de las cadenas, ; y , son DATOS.
       Si la limpieza estructural los tocara, el data URI quedaría inservible. */
    $dataUri = '.i{background:url(data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=)}';
    eq('no toca el interior de un data URI', $dataUri,
        CssMin::minificar('.i { background: url(data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=); }'));

    eq('no toca el interior de una cadena', '.c::after{content:"a; b, c"}',
        CssMin::minificar('.c::after { content: "a; b, c"; }'));

    eq('conserva url() entre comillas', ".f{src:url('../fonts/x.woff2') format('woff2')}",
        CssMin::minificar(".f { src: url('../fonts/x.woff2') format('woff2'); }"));

    /* Un comentario entre dos selectores no debe fusionarlos. */
    ok('un comentario separa tokens', !str_contains(CssMin::minificar('.a /* x */ .b { color: red }'), '.a.b'));

    /* Las llaves siguen balanceadas en los bundles reales ya generados. */
    foreach (glob(PUBLIC_PATH . '/assets/css/*.bundle.min.css') ?: [] as $f) {
        $css = file_get_contents($f);
        eq('llaves balanceadas en ' . basename($f),
            substr_count($css, '{'), substr_count($css, '}'));
    }
});

grupo('Caché de datos del sitio', function () {
    $k = 'test.cache.' . bin2hex(random_bytes(3));
    Cache::olvidar($k);

    /* remember() ejecuta el productor una sola vez. */
    $veces = 0;
    $prod = function () use (&$veces) { $veces++; return ['a' => 1, 'b' => 'ñá']; };
    $v1 = Cache::remember($k, $prod);
    $v2 = Cache::remember($k, $prod);
    eq('devuelve el mismo valor', $v1, $v2);
    eq('el productor corre una sola vez', 1, $veces);
    eq('conserva el contenido (con acentos)', 'ñá', $v2['b']);

    /* Invalidar obliga a regenerar. */
    Cache::olvidar($k);
    Cache::remember($k, $prod);
    eq('tras olvidar(), el productor vuelve a correr', 2, $veces);

    /* Un valor null cacheado NO debe confundirse con "no hay caché":
       es justo el caso del sitio sin modal promocional activo. */
    $kn = 'test.cache.null.' . bin2hex(random_bytes(3));
    Cache::olvidar($kn);
    $vecesNull = 0;
    $prodNull = function () use (&$vecesNull) { $vecesNull++; return null; };
    Cache::remember($kn, $prodNull);
    Cache::remember($kn, $prodNull);
    eq('cachea un null sin re-consultar', 1, $vecesNull);

    /* TTL vencido = fallo de lectura. */
    $kt = 'test.cache.ttl.' . bin2hex(random_bytes(3));
    Cache::put($kt, 'viejo', -10);
    eq('una entrada expirada no se sirve', null, Cache::get($kt));

    /* Archivo corrupto: se trata como fallo y se regenera, no revienta. */
    $kc = 'test.cache.corrupto.' . bin2hex(random_bytes(3));
    Cache::put($kc, 'bueno', null);
    file_put_contents(ROOT_PATH . '/storage/cache/data/' . $kc . '.php', '<?php return "sin cerrar');
    eq('un archivo corrupto no se sirve', null, Cache::get($kc));

    foreach ([$k, $kn, $kt, $kc] as $clave) {
        Cache::olvidar($clave);
    }
});

grupo('Router: el path no se decodifica antes de enrutar', function () {
    // Antes se hacía urldecode() de la URI completa: un %2F se convertía en
    // separador de segmentos y cambiaba qué ruta coincidía. Ahora el path llega
    // codificado y solo se decodifican los parámetros ya delimitados.
    $capturado = null;

    $r = new App\Core\Router();
    $r->get('/x/{slug}', function (string $slug) use (&$capturado) { $capturado = $slug; });

    $r->dispatch('GET', '/x/caf%C3%A9');
    eq('decodifica el parámetro capturado', 'café', $capturado);

    $capturado = null;
    $r->dispatch('GET', '/x/b%2Fc');
    eq('%2F queda dentro del parámetro, no parte el path', 'b/c', $capturado);
});

grupo('Esquema de impuestos SAE (por artículo)', function () {
    /* Normalización de lo capturado: entero positivo o "sin capturar". */
    eq('acepta un entero',            7,    Producto::esquemaImpuestos('7'));
    eq('tolera espacios',             12,   Producto::esquemaImpuestos('  12 '));
    eq('vacío = sin capturar',        null, Producto::esquemaImpuestos(''));
    eq('cero = sin capturar',         null, Producto::esquemaImpuestos('0'));
    eq('texto = sin capturar',        null, Producto::esquemaImpuestos('abc'));
    eq('negativo = sin capturar',     null, Producto::esquemaImpuestos('-3'));
    eq('decimal = sin capturar',      null, Producto::esquemaImpuestos('3.5'));

    /* La columna del layout de SAE sigue en su sitio. */
    $col = array_search('Clave de esquema de impuestos', SaeExport::HEADERS, true);
    eq('la columna es la 13 del layout', 12, $col);

    $pedido = [
        'created_at'        => '2026-07-28 10:00:00',
        'cliente_clave_sae' => 'CLI001',
        'cliente_nombre'    => 'Cliente',
        'referencia_cliente' => '',
        'notas'             => '',
    ];
    $item = static fn ($esq) => [[
        'nombre' => 'Producto X', 'clave_sae' => 'ART001',
        'esquema_impuestos' => $esq, 'cantidad' => 2, 'precio_unitario' => 10.0,
    ]];

    /* El esquema del artículo llega a la fila exportada. */
    $r = SaeExport::filasDePedido($pedido, $item(4), 'PA       1');
    eq('el esquema del artículo va en la fila', 4, $r['filas'][0][$col] ?? null);
    eq('y no genera errores', [], $r['errores']);

    /* Sin esquema propio ni general, la exportación se bloquea: SAE importaría
       la partida sin impuestos, que es justo lo que se quiere evitar. */
    $r2 = SaeExport::filasDePedido($pedido, $item(null), 'PA       1');
    ok('sin esquema, bloquea la exportación',
        (bool) array_filter($r2['errores'], static fn ($e) => str_contains($e, 'esquema de impuestos')));
});

grupo('Clave de documento SAE', function () {
    // Serie PA, longitud 10 → "PA" + número alineado a la derecha con blancos.
    $clave = SaeExport::claveDocumento(101);
    eq('longitud fija 10', 10, mb_strlen($clave));
    ok('empieza con la serie PA', strpos($clave, 'PA') === 0);
    eq('folio alineado a la derecha con blancos', 'PA' . str_pad('101', 8, ' ', STR_PAD_LEFT), $clave);
});

grupo('Upload: guarda contra path traversal', function () {
    // borrar() solo debe tocar rutas dentro de uploads/ y sin "..".
    $fuera = ROOT_PATH . '/public/robots_test_no_borrar.txt';
    file_put_contents($fuera, 'x');
    Upload::borrar('../robots_test_no_borrar.txt');
    ok('no borra fuera de uploads/', is_file($fuera));
    Upload::borrar('uploads/../robots_test_no_borrar.txt');
    ok('no borra con .. dentro de uploads/', is_file($fuera));
    @unlink($fuera);
});

grupo('Slug', function () {
    eq('acentos y espacios', 'vaso-de-papel', slugify('Vaso de Papel'));
    eq('símbolos colapsados', 'a-b', slugify('a & b'));
});

grupo('ip_in_cidr: IP exacta o CIDR IPv4', function () {
    // IPs de ejemplo en rangos reservados para documentación (RFC 5737), no de un
    // proyecto real, para que estas pruebas no queden atadas a infraestructura concreta.
    ok('IP exacta coincide', ip_in_cidr('198.51.100.7', '198.51.100.7'));
    ok('IP exacta distinta no coincide', !ip_in_cidr('198.51.100.8', '198.51.100.7'));
    ok('dentro del /24', ip_in_cidr('198.51.100.113', '198.51.100.0/24'));
    ok('dentro del /24 (otro host)', ip_in_cidr('198.51.100.200', '198.51.100.0/24'));
    ok('fuera del /24', !ip_in_cidr('198.51.101.1', '198.51.100.0/24'));
    ok('/32 equivale a IP exacta', ip_in_cidr('10.0.0.5', '10.0.0.5/32'));
    ok('/0 cubre cualquier IP', ip_in_cidr('8.8.8.8', '0.0.0.0/0'));
    ok('bits fuera de rango no truena', !ip_in_cidr('1.2.3.4', '1.2.3.4/33'));
    ok('IP inválida no truena', !ip_in_cidr('no-es-ip', '10.0.0.0/8'));
});

grupo('resolve_client_ip: X-Forwarded-For solo tras proxy de confianza', function () {
    // Proxy de confianza (p. ej. el servidor de Rackspace), con XFF: usa la IP del visitante.
    eq('confía en XFF cuando REMOTE_ADDR está en la lista', '189.203.10.20',
        resolve_client_ip('98.129.229.200', '189.203.10.20, 98.129.229.200', ['98.129.229.200']));

    // Sin proxy configurado (TRUSTED_PROXY_CIDR vacío): nunca confía en la cabecera,
    // aunque venga presente y con forma válida — es el comportamiento seguro por defecto.
    eq('sin CIDR configurado ignora XFF', '203.0.113.9',
        resolve_client_ip('203.0.113.9', '9.9.9.9', []));

    // REMOTE_ADDR fuera de la lista de confianza: no es el proxy real, así que la
    // cabecera pudo haberla puesto cualquiera — se ignora.
    eq('REMOTE_ADDR fuera de la lista ignora XFF', '198.51.100.1',
        resolve_client_ip('198.51.100.1', '9.9.9.9', ['98.129.229.200']));

    // Proxy de confianza pero sin cabecera: usa REMOTE_ADDR.
    eq('proxy confiable sin XFF usa REMOTE_ADDR', '98.129.229.200',
        resolve_client_ip('98.129.229.200', '', ['98.129.229.200']));

    // Cabecera con valor no-IP (falsificada/corrupta): no se usa.
    eq('XFF inválida cae a REMOTE_ADDR', '98.129.229.200',
        resolve_client_ip('98.129.229.200', 'no-es-ip', ['98.129.229.200']));

    // CIDR /24 también funciona (por si el hosting rota entre varias IPs del bloque).
    ok('CIDR /24 también es válido como proxy de confianza',
        resolve_client_ip('98.129.229.5', '189.203.10.20', ['98.129.229.0/24']) === '189.203.10.20');
});

grupo('Piezas por presentación (Fase 7.10)', function () {
    eq('sin restricción devuelve la solicitada', 750, Producto::cantidadValida(750, null));
    eq('sin restricción respeta el mínimo de 1', 1, Producto::cantidadValida(0, null));
    eq('exacto no cambia', 1000, Producto::cantidadValida(1000, 1000));
    eq('redondea hacia arriba', 1000, Producto::cantidadValida(750, 1000));
    eq('redondea al siguiente múltiplo', 2000, Producto::cantidadValida(1500, 1000));
    eq('mínimo una presentación completa, nunca 0', 1000, Producto::cantidadValida(1, 1000));
    eq('piezas <= 0 se trata como sin restricción', 5, Producto::cantidadValida(5, 0));

    eq('normaliza entero positivo', 1000, Producto::piezasPorPresentacion('1000'));
    eq('vacío es sin restricción', null, Producto::piezasPorPresentacion(''));
    eq('cero es sin restricción', null, Producto::piezasPorPresentacion('0'));
    eq('no numérico es sin restricción', null, Producto::piezasPorPresentacion('abc'));
    eq('null es sin restricción', null, Producto::piezasPorPresentacion(null));
});

grupo('Mínimo de piezas independiente de la presentación (7.10, ajuste)', function () {
    // Caso del negocio: presentación de 50, mínimo de 100 (2 paquetes) — pedir
    // 1 pieza debe resultar en 100, no en 50 (el mínimo manda sobre el redondeo).
    eq('presentación 50 + mínimo 100: pedir 1 da 100 (2 paquetes)', 100, Producto::cantidadValida(1, 50, 100));
    eq('presentación 50 + mínimo 100: pedir 50 (1 paquete) sube al mínimo', 100, Producto::cantidadValida(50, 50, 100));
    eq('presentación 50 + mínimo 100: pedir 120 redondea a 150 (por encima del mínimo)', 150, Producto::cantidadValida(120, 50, 100));
    eq('presentación 50 + mínimo 100: pedir 100 exacto no cambia', 100, Producto::cantidadValida(100, 50, 100));

    // Mínimo sin presentación (producto sin lote, pero con piso de piezas).
    eq('solo mínimo, sin presentación: pedir 10 sube a 100', 100, Producto::cantidadValida(10, null, 100));
    eq('solo mínimo, sin presentación: pedir 150 no cambia', 150, Producto::cantidadValida(150, null, 100));

    // El mínimo capturado mal (no múltiplo de la presentación) se corrige solo.
    eq('mínimo no múltiplo de la presentación se redondea (90 -> 100 con lote 50)', 100, Producto::cantidadValida(1, 50, 90));

    // Sin mínimo (null o 0), el comportamiento es igual al de antes (solo presentación).
    eq('mínimo null no afecta', 50, Producto::cantidadValida(1, 50, null));
    eq('mínimo 0 no afecta', 50, Producto::cantidadValida(1, 50, 0));

    eq('normaliza entero positivo', 100, Producto::piezasMinimas('100'));
    eq('vacío es sin mínimo', null, Producto::piezasMinimas(''));
    eq('cero es sin mínimo', null, Producto::piezasMinimas('0'));

    eq('loteDesdeFila extrae ambos campos', [50, 100],
        Producto::loteDesdeFila(['piezas_por_presentacion' => '50', 'piezas_minimas' => '100']));
    eq('loteDesdeFila con campos ausentes da [null,null]', [null, null], Producto::loteDesdeFila([]));
});

/* -------------------------------------------------------- Resumen -- */

$total = $GLOBALS['_pass'] + $GLOBALS['_fail'];
echo "\n----------------------------------------\n";
echo "Pruebas: {$total}  ·  OK: {$GLOBALS['_pass']}  ·  Fallidas: {$GLOBALS['_fail']}\n";
exit($GLOBALS['_fail'] > 0 ? 1 : 0);
