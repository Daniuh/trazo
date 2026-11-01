<?php

declare(strict_types=1);

// Sanctum solo con tokens Bearer: sin cookies ni sesiones de navegador (la API es stateless).
// La inactividad máxima se controla aparte, en config/trazo.php. Documentado en docs/backend/auth.md

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;

return [

    // Ningún dominio usa autenticación por cookie
    'stateful' => [],

    // Sin guard de sesión: solo cuenta el token Bearer
    'guard' => [],

    // Duración máxima de una sesión: 8 horas, una jornada laboral
    'expiration' => (int) env('SESSION_MAX_MINUTES', 480),

    // El prefijo permite que los escáneres de secretos reconozcan un token filtrado
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'trazo_'),

    'middleware' => [
        'authenticate_session' => AuthenticateSession::class,
        'encrypt_cookies' => EncryptCookies::class,
        'validate_csrf_token' => ValidateCsrfToken::class,
    ],

];
