<?php

declare(strict_types=1);

// CORS cerrado: la SPA y la API viven en el mismo origen (detrás del gateway), así que ningún otro sitio necesita leer
// las respuestas. Sin rutas aquí, Laravel no agrega Access-Control-Allow-Origin. Documentado en docs/seguridad/cabeceras.md

return [

    'paths' => [],

    'allowed_methods' => [],

    'allowed_origins' => [],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
