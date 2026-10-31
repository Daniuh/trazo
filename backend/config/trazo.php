<?php

declare(strict_types=1);

// Configuración propia de Trazo.

return [

    // Contraseña de los usuarios de demostración (solo fuera de producción). Cumple la política de 12+ caracteres.
    'demo_user_password' => env('DEMO_USER_PASSWORD', 'Trazo.Demo.2026'),

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
    ],

];
