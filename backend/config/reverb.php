<?php

declare(strict_types=1);

// Servidor de websockets (Reverb). Escucha solo dentro de Docker; afuera se llega por el gateway en /ws.
// Acepta conexiones únicamente desde el dominio de la app, no deja que los navegadores se manden mensajes entre sí y
// limita el tamaño de cada mensaje. Documentado en docs/operacion/tiempo-real.md

return [

    'default' => 'reverb',

    'servers' => [

        'reverb' => [
            'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
            'port' => env('REVERB_SERVER_PORT', 8080),
            'path' => '',
            'hostname' => env('REVERB_HOST'),
            'options' => [
                'tls' => [],
            ],
            'max_request_size' => 10_000,
            'scaling' => [
                'enabled' => false,
            ],
            'pulse_ingest_interval' => 15,
            'telescope_ingest_interval' => 15,
        ],

    ],

    'apps' => [

        'provider' => 'config',

        'apps' => [
            [
                'key' => env('REVERB_APP_KEY'),
                'secret' => env('REVERB_APP_SECRET'),
                'app_id' => env('REVERB_APP_ID'),
                'options' => [
                    'host' => env('REVERB_HOST', 'reverb'),
                    'port' => env('REVERB_PORT', 8080),
                    'scheme' => env('REVERB_SCHEME', 'http'),
                    'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
                ],
                // Solo el dominio de la app (sin esquema ni puerto), p. ej. "localhost" o "trazo.daniuh.dev"
                'allowed_origins' => array_values(array_filter(explode(',', (string) env('REVERB_ALLOWED_ORIGINS', 'localhost')))),
                'ping_interval' => 60,
                'activity_timeout' => 30,
                'max_connections' => env('REVERB_MAX_CONNECTIONS'),
                // Los mensajes no llevan datos: nada legítimo se acerca a este tamaño
                'max_message_size' => 2_000,
                // Los navegadores solo escuchan; ninguno puede mandar eventos a otros
                'accept_client_events_from' => 'none',
                'rate_limiting' => [
                    'enabled' => true,
                    'max_attempts' => 60,
                    'decay_seconds' => 60,
                    'terminate_on_limit' => true,
                ],
            ],
        ],

    ],

];
