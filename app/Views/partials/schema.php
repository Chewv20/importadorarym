<?php
/**
 * Datos estructurados (schema.org) del negocio, para SEO local y
 * resultados enriquecidos de Google.
 */
$schema = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Store',
    'name'        => 'Importadora RYM S.A. de C.V.',
    'description' => 'Fabricación, impresión y distribución de insumos y empaques para la industria alimentaria.',
    'image'       => asset_url('assets/img/og-image.jpg'),
    'logo'        => asset_url('assets/img/icons/icon-512.png'),
    'url'         => rtrim((string) config('app.url'), '/') . '/',
    'telephone'   => '+525556121612',
    'email'       => 'cotizaciones@importadorarym.com',
    'priceRange'  => '$$',
    'address'     => [
        '@type'           => 'PostalAddress',
        'streetAddress'   => 'Av. San Lorenzo 279, Nave 27, Col. San Nicolás Tolentino',
        'addressLocality' => 'Iztapalapa',
        'addressRegion'   => 'Ciudad de México',
        'postalCode'      => '09850',
        'addressCountry'  => 'MX',
    ],
    'geo' => [
        '@type'     => 'GeoCoordinates',
        'latitude'  => 19.3293018,
        'longitude' => -99.0802377,
    ],
    'openingHoursSpecification' => [
        '@type'     => 'OpeningHoursSpecification',
        'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
        'opens'     => '09:00',
        'closes'    => '18:00',
    ],
];
?>
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
