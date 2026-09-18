<?php

return [
    'name'  => env('APP_NAME', 'Importadora RYM'),
    'env'   => env('APP_ENV', 'local'),
    // Falla "cerrado": si el .env no se subió/cargó (p. ej. olvidado en el FTP
    // de producción), mejor ocultar errores por defecto que exponer trazas.
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'url'   => env('APP_URL', 'http://localhost'),

    // Clave secreta para firmar tokens (captcha, etc.). Definir en el .env.
    'key'   => env('APP_KEY', ''),

    // SSL: se mantiene en false hasta contratar el certificado. En el .env de
    // producción se pondrá FORCE_HTTPS=true una vez instalado el SSL.
    'force_https' => filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN),

    // Proxy(es) de confianza para resolver la IP real tras un balanceador
    // (ver client_ip() en Helpers/functions.php). Vacío = nunca confiar en
    // X-Forwarded-For (seguro por defecto). IP exacta, CIDR o lista separada
    // por comas.
    'trusted_proxy_cidr' => env('TRUSTED_PROXY_CIDR', ''),

    'timezone' => env('APP_TIMEZONE', 'America/Mexico_City'),
    'locale'   => env('APP_LOCALE', 'es_MX'),

    // Cierre de sesión por inactividad (segundos). 0 = desactivado. 7200 = 2 h.
    'session_timeout' => (int) env('SESSION_TIMEOUT', 7200),

    // Google Analytics (GA4). Vacío = desactivado.
    'analytics_id' => env('GA_MEASUREMENT_ID', ''),

    // Tasa de IVA (%) para las cotizaciones.
    'iva' => (float) env('IVA_TASA', 16),

    // WhatsApp click-to-chat (número solo con dígitos, con lada país: 52 = México).
    'whatsapp' => [
        'numero'  => env('WHATSAPP_NUMERO', '525552979776'),
        'mensaje' => env('WHATSAPP_MENSAJE', 'Hola, me gustaría recibir información sobre sus productos.'),
    ],

    // Libreta de visitas: buzón opcional que recibe copia de cada registro (vacío = sin copia).
    'visitas' => [
        'copia_email' => env('VISITAS_COPIA_EMAIL', ''),
    ],

    // Bolsa de trabajo: buzón de RRHH que recibe las postulaciones (si vacío, usa MAIL_LEADS).
    'careers' => [
        'rrhh_email' => env('RRHH_EMAIL', ''),
    ],

    // Correo transaccional (ver App\Core\Mailer).
    'mail' => [
        'mailer'       => env('MAIL_MAILER', 'log'),   // smtp | mail | log
        'host'         => env('MAIL_HOST', ''),
        'port'         => (int) env('MAIL_PORT', 587),
        'username'     => env('MAIL_USERNAME', ''),
        'password'     => env('MAIL_PASSWORD', ''),
        'encryption'   => env('MAIL_ENCRYPTION', 'tls'), // tls | ssl | ''
        'from_address' => env('MAIL_FROM_ADDRESS', 'no-responder@importadorarym.com'),
        'from_name'    => env('MAIL_FROM_NAME', 'Importadora RYM'),
        'reply_to'     => env('MAIL_REPLY_TO', ''),
        'leads'        => env('MAIL_LEADS', ''),         // buzón interno de avisos
        'errores'      => env('MAIL_ERRORES', ''),       // buzón técnico: aviso ante un error 500 (vacío = desactivado)
    ],

    // Valores por defecto para la exportación a Aspel SAE (opcionales).
    'erp' => [
        'clave_vendedor'    => env('ERP_CLAVE_VENDEDOR', ''),
        'esquema_impuestos' => env('ERP_ESQUEMA_IMPUESTOS', ''),
        // Serie de folios de pedidos en SAE: la clave = serie + consecutivo,
        // longitud fija, número alineado a la derecha con blancos. Ej. "PA     101".
        'serie_pedidos'     => env('ERP_SERIE_PEDIDOS', 'PA'),
        'longitud_clave'    => (int) env('ERP_LONGITUD_CLAVE', 10),
    ],

    // Integraciones máquina-a-máquina (ver App\Controllers\Integraciones).
    'integraciones' => [
        // Token del endpoint de sincronización de existencias desde Aspel SAE
        // (vacío = endpoint desactivado/cerrado, ver docs/DESPLIEGUE.md §4.7).
        'sae_sync_token' => env('SAE_SYNC_TOKEN', ''),
    ],
];
