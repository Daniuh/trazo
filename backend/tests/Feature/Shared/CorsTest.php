<?php

declare(strict_types=1);

// CORS cerrado: la SPA vive en el mismo origen, así que ningún otro sitio recibe permiso para leer las respuestas
// (lo marcó el escaneo de ZAP con la configuración por defecto de Laravel). Documentado en docs/seguridad/cabeceras.md

it('no da permiso a otros orígenes, ni en la consulta ni en la verificación previa', function (): void {
    $this->getJson('/api/v1/catalogs', ['Origin' => 'https://malo.example'])
        ->assertHeaderMissing('Access-Control-Allow-Origin');

    $this->call('OPTIONS', '/api/v1/incidents', server: [
        'HTTP_ORIGIN' => 'https://malo.example',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'DELETE',
    ])->assertHeaderMissing('Access-Control-Allow-Origin');
});
