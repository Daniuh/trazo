<?php

declare(strict_types=1);

// Configuración propia de Trazo.

return [

    // Contraseña de los usuarios de demostración (solo fuera de producción). Cumple la política de 12+ caracteres.
    'demo_user_password' => env('DEMO_USER_PASSWORD', 'Trazo.Demo.2026'),

    // Incidentes de demostración que se cargan fuera de producción (la prueba pide al menos 500)
    'seed_incidents' => (int) env('SEED_INCIDENTS', 600),

    'auth' => [
        // Una sesión sin uso durante este tiempo se cierra sola
        'idle_minutes' => (int) env('SESSION_IDLE_MINUTES', 60),

        // Bloqueo progresivo: fallos seguidos => segundos de espera. Documentado en docs/backend/auth.md
        'lockout' => [
            5 => 60,
            10 => 300,
            15 => 900,
            20 => 3600,
        ],

        // El contador de fallos vuelve a cero con un ingreso correcto o pasado este tiempo desde el primer fallo
        'failures_ttl_minutes' => 1440,

        // MFA. Documentado en docs/backend/auth.md
        'mfa' => [
            // Nombre que muestra la aplicación de autenticación junto al correo
            'issuer' => 'Trazo',
            // Vida del token de un ingreso a medias (contraseña correcta, código pendiente)
            'challenge_minutes' => 5,
            // Códigos fallidos en un mismo ingreso antes de cortarlo
            'max_attempts' => 5,
            'recovery_codes' => 8,
        ],

        // Recuperación de contraseña. Documentado en docs/backend/auth.md
        'password' => [
            // Vida del código que llega al correo y del pase para elegir la contraseña nueva
            'reset_code_minutes' => 15,
            'reset_ticket_minutes' => 10,
            // Intentos fallidos (código o segundo factor) antes de anular la solicitud
            'reset_max_attempts' => 5,
            // Have I Been Pwned: cuánto se espera antes de seguir solo con la lista local
            'breach_check_timeout' => 3,
            'common_list' => resource_path('security/common-passwords.txt'),
        ],
    ],

    // Documentado en docs/backend/dashboard.md
    'dashboard' => [
        // Zona horaria en la que se cuentan los días (las fechas se guardan en UTC)
        'timezone' => env('BUSINESS_TIMEZONE', 'America/Bogota'),
        // Cada cambio en un incidente invalida la caché; este tiempo cubre lo que cambia solo con la hora (vencidos)
        'cache_seconds' => (int) env('DASHBOARD_CACHE_SECONDS', 60),
        'list_limit' => 5,
        'due_soon_hours' => 48,
        'workload_limit' => 8,
    ],

    // Pantalla de sistema. Documentado en docs/backend/system.md
    'system' => [
        // Los eventos resueltos se borran pasado este tiempo
        'events_retention_days' => (int) env('SYSTEM_EVENTS_RETENTION_DAYS', 90),
    ],

    // Exportación de incidentes en CSV. Documentado en docs/backend/incidents.md
    'export' => [
        // Más filas que esto se rechazan: hay que acotar los filtros
        'max_rows' => (int) env('EXPORT_MAX_ROWS', 50000),
        // Filas que se leen de la base por cada bloque mientras se escribe el archivo
        'chunk_size' => 1000,
    ],

];
